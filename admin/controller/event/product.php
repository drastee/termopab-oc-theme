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
	 * Change view route to use our product_form template with 360-view and video tabs.
	 * Also inject view_360, main_video, video_review into $data.
	 */
	public function onProductFormViewBefore(string &$route, array &$data, string &$code = '', string &$output = ''): void {
		if ($route === 'catalog/product_form') {
			$route = 'extension/termopab/catalog/product_form';

			$product_id = (int)($data['product_id'] ?? $this->request->get['product_id'] ?? 0);
			$data['view_360'] = '';
			$data['main_video'] = '';
			$data['video_review'] = '';
			if ($product_id) {
				$cols = [];
				foreach (['view_360', 'main_video', 'video_review'] as $col) {
					if ($this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE '" . $this->db->escape($col) . "'")->num_rows > 0) {
						$cols[] = "`" . $col . "`";
					}
				}
				if (!empty($cols)) {
					$q = $this->db->query("SELECT " . implode(', ', $cols) . " FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . (int)$product_id . "'");
					if ($q->num_rows && isset($q->row)) {
						foreach (['view_360', 'main_video', 'video_review'] as $col) {
							if (isset($q->row[$col])) {
								$data[$col] = $q->row[$col] ?? '';
							}
						}
					}
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
		$cols = ['view_360', 'main_video', 'video_review'];
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
	}
}
