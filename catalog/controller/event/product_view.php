<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Event;

/**
 * Event: inject view_360, view_360_url, button_cart for product page main-media block.
 * Trigger: catalog/view/product/product/before
 */
class ProductView extends \Opencart\System\Engine\Controller {

	public function onProductViewBefore(string &$route, array &$data, string &$code = '', string &$output = ''): void {
		$product_id = (int)($data['product_id'] ?? 0);
		if ($product_id <= 0) {
			return;
		}

		$this->load->model('catalog/product');
		$product_info = $this->model_catalog_product->getProduct($product_id);
		if (!$product_info) {
			return;
		}

		$data['view_360'] = trim((string)($product_info['view_360'] ?? ''));
		$data['view_360_url'] = '';
		if ($data['view_360'] !== '') {
			$data['view_360_url'] = $this->url->link('extension/termopab/tool/glb.serve', 'code=' . urlencode($data['view_360']));
		}

		// main_video, main_video_poster — завантажуємо окремим запитом (getProduct може не повертати кастомні колонки)
		$media_cols = [];
		foreach (['main_video', 'main_video_poster'] as $col) {
			if ($this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE '" . $this->db->escape($col) . "'")->num_rows > 0) {
				$media_cols[] = "`" . $col . "`";
			}
		}
		$data['main_video'] = '';
		$data['main_video_poster'] = '';
		if (!empty($media_cols)) {
			$cols_sql = implode(', ', $media_cols);
			$q = $this->db->query("SELECT `master_id`, " . $cols_sql . " FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . (int)$product_id . "'");
			if ($q->num_rows && isset($q->row)) {
				$data['main_video'] = trim((string)($q->row['main_video'] ?? ''));
				$data['main_video_poster'] = trim((string)($q->row['main_video_poster'] ?? ''));
				// Якщо варіант — беремо з мастера, якщо у варіанта порожньо
				$master_id = (int)($q->row['master_id'] ?? 0);
				if ($master_id > 0 && ($data['main_video'] === '' || $data['main_video_poster'] === '')) {
					$mq = $this->db->query("SELECT " . $cols_sql . " FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . (int)$master_id . "'");
					if ($mq->num_rows && isset($mq->row)) {
						if ($data['main_video'] === '') {
							$data['main_video'] = trim((string)($mq->row['main_video'] ?? ''));
						}
						if ($data['main_video_poster'] === '') {
							$data['main_video_poster'] = trim((string)($mq->row['main_video_poster'] ?? ''));
						}
					}
				}
			}
		}

		$data['main_video_url'] = '';
		if ($data['main_video'] !== '') {
			$video = trim($data['main_video']);
			// tool/upload повертає code (хеш), filemanager — шлях catalog/xxx
			if (str_contains($video, '/')) {
				$video_path = str_replace(['../', '..\\'], '', $video);
				$data['main_video_url'] = rtrim((string)$this->config->get('config_url'), '/') . '/image/' . ltrim($video_path, '/');
			} else {
				$data['main_video_url'] = $this->url->link('extension/termopab/tool/video.serve', 'code=' . urlencode($video));
			}
		}

		$data['main_video_poster_url'] = '';
		if ($data['main_video_poster'] !== '') {
			$poster_path = trim(str_replace(['../', '..\\'], '', $data['main_video_poster']));
			$data['main_video_poster_url'] = rtrim((string)$this->config->get('config_url'), '/') . '/image/' . ltrim($poster_path, '/');
		}

		$this->load->language('common/default');
		if (!isset($data['button_cart']) || $data['button_cart'] === '') {
			$data['button_cart'] = $this->language->get('button_cart');
		}

		// Доп. фото для окремого блоку на сторінці товару
		$data['product_extra_images'] = [];
		if ($this->db->query("SHOW TABLES LIKE '" . $this->db->escape(DB_PREFIX . 'product_extra_image') . "'")->num_rows > 0) {
			$q = $this->db->query("SELECT `image`, `sort_order` FROM `" . DB_PREFIX . "product_extra_image` WHERE `product_id` = '" . (int)$product_id . "' ORDER BY `sort_order` ASC");
			if ($q->num_rows) {
				$this->load->model('tool/image');
				foreach ($q->rows as $row) {
					$thumb = $row['image'] && is_file(DIR_IMAGE . html_entity_decode($row['image'], ENT_QUOTES, 'UTF-8'))
						? $this->model_tool_image->resize($row['image'], (int)$this->config->get('config_image_thumb_width'), (int)$this->config->get('config_image_thumb_height'))
						: $this->model_tool_image->resize('no_image.png', (int)$this->config->get('config_image_thumb_width'), (int)$this->config->get('config_image_thumb_height'));
					$popup = $row['image'] && is_file(DIR_IMAGE . html_entity_decode($row['image'], ENT_QUOTES, 'UTF-8'))
						? $this->model_tool_image->resize($row['image'], (int)$this->config->get('config_image_popup_width'), (int)$this->config->get('config_image_popup_height'))
						: '';
					$data['product_extra_images'][] = [
						'image'  => $row['image'],
						'thumb'  => $thumb,
						'popup'  => $popup,
						'sort_order' => (int)$row['sort_order'],
					];
				}
			}
		}
	}
}
