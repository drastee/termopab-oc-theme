<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Event;

/**
 * Event: redirect catalog/product controller and product_form view to extension.
 * Trigger: admin/controller/catalog/product (with wildcard)/before
 * Trigger: admin/view/catalog/product_form/before
 * Trigger: admin/model/catalog/product.addProduct/after
 * Trigger: admin/model/catalog/product.editProduct/after
 */
class Product extends \Opencart\System\Engine\Controller {

	/**
	 * Change route to extension/termopab/catalog/product so our override controller loads.
	 */
	public function onAdminProductBefore(string &$route, array &$args): void {
		if (strpos($route, 'catalog/product') === 0) {
			$route = 'extension/termopab/' . $route;
		}
	}

	/**
	 * Change view route to use our product_form template with 360-view, video and extra images tabs.
	 * Also inject view_360, main_video, video_review, product_extra_images into $data.
	 */
	public function onProductFormViewBefore(string &$route, array &$data, string &$code = '', string &$output = ''): void {
		if ($route === 'catalog/product_form') {
			$route = 'extension/termopab/catalog/product_form';

			$product_id = (int)($data['product_id'] ?? $this->request->get['product_id'] ?? 0);
			$data['view_360'] = '';
			$data['main_video'] = '';
			$data['main_video_poster'] = '';
			$data['video_review'] = '';
			$data['tab_extra_images'] = 'Доп. фото';
			$data['product_extra_images'] = [];
			$data['extra_image_row'] = 0;

			if ($product_id) {
				$cols = [];
				foreach (['view_360', 'main_video', 'main_video_poster', 'video_review'] as $col) {
					if ($this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE '" . $this->db->escape($col) . "'")->num_rows > 0) {
						$cols[] = "`" . $col . "`";
					}
				}
				if (!empty($cols)) {
					$q = $this->db->query("SELECT " . implode(', ', $cols) . " FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . (int)$product_id . "'");
					if ($q->num_rows && isset($q->row)) {
						foreach (['view_360', 'main_video', 'main_video_poster', 'video_review'] as $col) {
							if (isset($q->row[$col])) {
								$data[$col] = $q->row[$col] ?? '';
							}
						}
						if (!empty($data['main_video_poster'])) {
							$this->load->model('tool/image');
							$img = html_entity_decode($data['main_video_poster'], ENT_QUOTES, 'UTF-8');
							$data['main_video_poster_thumb'] = ($img && is_file(DIR_IMAGE . $img))
								? $this->model_tool_image->resize($data['main_video_poster'], (int)$this->config->get('config_image_default_width'), (int)$this->config->get('config_image_default_height'))
								: ($data['placeholder'] ?? '');
						}
					}
				}

				// Доп. фото: load from tp_product_extra_image
				$table = DB_PREFIX . 'product_extra_image';
				if ($this->db->query("SHOW TABLES LIKE '" . $this->db->escape($table) . "'")->num_rows > 0) {
					$this->load->model('tool/image');
					$placeholder = $this->model_tool_image->resize('no_image.png', (int)$this->config->get('config_image_default_width'), (int)$this->config->get('config_image_default_height'));
					$q = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_extra_image` WHERE `product_id` = '" . (int)$product_id . "' ORDER BY `sort_order` ASC");
					foreach ($q->rows as $row) {
						$thumb = $row['image'] && is_file(DIR_IMAGE . html_entity_decode($row['image'], ENT_QUOTES, 'UTF-8'))
							? $this->model_tool_image->resize($row['image'], (int)$this->config->get('config_image_default_width'), (int)$this->config->get('config_image_default_height'))
							: $placeholder;
						$data['product_extra_images'][] = [
							'image'      => $row['image'],
							'sort_order' => (int)$row['sort_order'],
							'thumb'      => $thumb,
						];
					}
					$data['extra_image_row'] = count($data['product_extra_images']);
				}
			}
		}
	}

	/**
	 * Save view_360, main_video, video_review after addProduct.
	 */
	public function onAddProductAfter(string &$route, array &$args, mixed &$output): void {
		$data = $args[0] ?? [];
		if ($output && is_numeric($output)) {
			$this->saveProductMedia((int)$output, $data);
		}
	}

	/**
	 * Save view_360, main_video, video_review after editProduct.
	 */
	public function onEditProductAfter(string &$route, array &$args, mixed &$output): void {
		$product_id = (int)($args[0] ?? 0);
		$data = $args[1] ?? [];
		if ($product_id) {
			$this->saveProductMedia($product_id, $data);
		}
	}

	private function saveProductMedia(int $product_id, array $data): void {
		$cols = ['view_360', 'main_video', 'main_video_poster', 'video_review'];
		$set = [];
		foreach ($cols as $col) {
			if (!array_key_exists($col, $data)) {
				continue;
			}
			if ($this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE '" . $this->db->escape($col) . "'")->num_rows > 0) {
				$set[] = "`" . $col . "` = '" . $this->db->escape((string)$data[$col]) . "'";
			}
		}
		if (!empty($set)) {
			$this->db->query("UPDATE `" . DB_PREFIX . "product` SET " . implode(', ', $set) . " WHERE `product_id` = '" . (int)$product_id . "'");
		}

		// Доп. фото: save to tp_product_extra_image
		if ($this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . 'product_extra_image') . "'")->num_rows === 0) {
			return;
		}
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_extra_image` WHERE `product_id` = '" . (int)$product_id . "'");
		if (!empty($data['product_extra_image']) && is_array($data['product_extra_image'])) {
			foreach ($data['product_extra_image'] as $row) {
				$image = isset($row['image']) ? trim((string)$row['image']) : '';
				if ($image === '') {
					continue;
				}
				$sort = (int)($row['sort_order'] ?? 0);
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_extra_image` SET `product_id` = '" . (int)$product_id . "', `image` = '" . $this->db->escape($image) . "', `sort_order` = '" . $sort . "'");
			}
		}
	}
}
