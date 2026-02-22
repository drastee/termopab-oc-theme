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

		// description — основний опис товару (product_info або прямий запит)
		$data['description'] = trim((string)($product_info['description'] ?? ''));
		$lang_id = (int)$this->config->get('config_language_id');
		if ($data['description'] === '') {
			$dq = $this->db->query("SELECT `description` FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = '" . (int)$product_id . "' AND `language_id` = '" . $lang_id . "'");
			if ($dq->num_rows && !empty(trim((string)($dq->row['description'] ?? '')))) {
				$data['description'] = trim((string)$dq->row['description']);
			} else {
				$dq = $this->db->query("SELECT `description` FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = '" . (int)$product_id . "' AND `description` IS NOT NULL AND TRIM(`description`) != '' LIMIT 1");
				if ($dq->num_rows && !empty(trim((string)($dq->row['description'] ?? '')))) {
					$data['description'] = trim((string)$dq->row['description']);
				}
			}
		}
		$master_id = (int)($product_info['master_id'] ?? 0);
		if ($master_id > 0 && $data['description'] === '') {
			$dq = $this->db->query("SELECT `description` FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = '" . (int)$master_id . "' AND `language_id` = '" . $lang_id . "'");
			if ($dq->num_rows && !empty(trim((string)($dq->row['description'] ?? '')))) {
				$data['description'] = trim((string)$dq->row['description']);
			}
		}
		$data['description'] = html_entity_decode($data['description'], ENT_QUOTES, 'UTF-8');

		// Міграція: додати колонки якщо немає (до getProduct, щоб вони були в результаті)
		foreach (['short_description', 'description_characteristics'] as $col) {
			if ($this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_description` LIKE '" . $this->db->escape($col) . "'")->num_rows === 0) {
				try {
					$this->db->query("ALTER TABLE `" . DB_PREFIX . "product_description` ADD COLUMN `" . $this->db->escape($col) . "` text DEFAULT NULL");
				} catch (\Throwable $e) {
				}
			}
		}

		// short_description, description_characteristics — з product_info (getProduct джойнить product_description) або прямий запит
		$data['short_description'] = trim((string)($product_info['short_description'] ?? ''));
		$data['description_characteristics'] = trim((string)($product_info['description_characteristics'] ?? ''));

		// Fallback: прямий запит якщо getProduct не повернув (напр. кеш або мова)
		if ($data['description_characteristics'] === '' && $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_description` LIKE 'description_characteristics'")->num_rows > 0) {
			$dq = $this->db->query("SELECT `description_characteristics` FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = '" . (int)$product_id . "' AND `language_id` = '" . $lang_id . "'");
			if ($dq->num_rows && !empty(trim((string)($dq->row['description_characteristics'] ?? '')))) {
				$data['description_characteristics'] = trim((string)$dq->row['description_characteristics']);
			} else {
				// Fallback: будь-яка мова, якщо поточна порожня
				$dq = $this->db->query("SELECT `description_characteristics` FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = '" . (int)$product_id . "' AND `description_characteristics` IS NOT NULL AND TRIM(`description_characteristics`) != '' LIMIT 1");
				if ($dq->num_rows && !empty(trim((string)($dq->row['description_characteristics'] ?? '')))) {
					$data['description_characteristics'] = trim((string)$dq->row['description_characteristics']);
				}
			}
		}
		if ($data['short_description'] === '' && $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_description` LIKE 'short_description'")->num_rows > 0) {
			$dq = $this->db->query("SELECT `short_description` FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = '" . (int)$product_id . "' AND `language_id` = '" . $lang_id . "'");
			if ($dq->num_rows && !empty(trim((string)($dq->row['short_description'] ?? '')))) {
				$data['short_description'] = trim((string)$dq->row['short_description']);
			} else {
				$dq = $this->db->query("SELECT `short_description` FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = '" . (int)$product_id . "' AND `short_description` IS NOT NULL AND TRIM(`short_description`) != '' LIMIT 1");
				if ($dq->num_rows && !empty(trim((string)($dq->row['short_description'] ?? '')))) {
					$data['short_description'] = trim((string)$dq->row['short_description']);
				}
			}
		}

		// Fallback з мастера для варіантів (якщо пусто)
		$master_id = (int)($product_info['master_id'] ?? 0);
		if ($master_id > 0) {
			if ($data['description_characteristics'] === '' && $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_description` LIKE 'description_characteristics'")->num_rows > 0) {
				$mdesc = $this->db->query("SELECT `description_characteristics` FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = '" . (int)$master_id . "' AND `language_id` = '" . $lang_id . "'");
				if ($mdesc->num_rows && isset($mdesc->row['description_characteristics'])) {
					$data['description_characteristics'] = trim((string)$mdesc->row['description_characteristics']);
				}
			}
			if ($data['short_description'] === '' && $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product_description` LIKE 'short_description'")->num_rows > 0) {
				$mdesc = $this->db->query("SELECT `short_description` FROM `" . DB_PREFIX . "product_description` WHERE `product_id` = '" . (int)$master_id . "' AND `language_id` = '" . $lang_id . "'");
				if ($mdesc->num_rows && isset($mdesc->row['short_description'])) {
					$data['short_description'] = trim((string)$mdesc->row['short_description']);
				}
			}
		}

		$data['short_description'] = html_entity_decode($data['short_description'], ENT_QUOTES, 'UTF-8');
		$data['description_characteristics'] = html_entity_decode($data['description_characteristics'], ENT_QUOTES, 'UTF-8');

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
		$data['cart_add'] = $this->url->link('checkout/cart.add', 'language=' . $this->config->get('config_language'));
		$data['cart_info'] = $this->url->link('common/cart.info', 'language=' . $this->config->get('config_language'));

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

		// video_review — друге відео (iframe) для вкладки «Огляд» — HTML для виводу без екранування
		$data['video_review'] = '';
		if ($this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "product` LIKE 'video_review'")->num_rows > 0) {
			$vq = $this->db->query("SELECT `master_id`, `video_review` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . (int)$product_id . "'");
			if ($vq->num_rows && isset($vq->row)) {
				$raw = trim((string)($vq->row['video_review'] ?? ''));
				$master_id = (int)($vq->row['master_id'] ?? 0);
				if ($master_id > 0 && $raw === '') {
					$mvq = $this->db->query("SELECT `video_review` FROM `" . DB_PREFIX . "product` WHERE `product_id` = '" . (int)$master_id . "'");
					if ($mvq->num_rows && isset($mvq->row)) {
						$raw = trim((string)($mvq->row['video_review'] ?? ''));
					}
				}
				$data['video_review'] = html_entity_decode($raw, ENT_QUOTES, 'UTF-8');
			}
		}

		// Текст доставки та обміну з налаштувань теми (по мові) — HTML для виводу без екранування
		$language_id = (int)$this->config->get('config_language_id');
		$delivery_raw = $this->config->get('theme_termopab_delivery_payment');
		$exchange_raw = $this->config->get('theme_termopab_exchange_return');
		$data['characteristics_delivery_payment'] = '';
		$data['characteristics_exchange_return'] = '';
		if (is_array($delivery_raw) && isset($delivery_raw[$language_id])) {
			$data['characteristics_delivery_payment'] = html_entity_decode((string)$delivery_raw[$language_id], ENT_QUOTES, 'UTF-8');
		} elseif (is_array($delivery_raw) && !empty($delivery_raw)) {
			$data['characteristics_delivery_payment'] = html_entity_decode((string)reset($delivery_raw), ENT_QUOTES, 'UTF-8');
		}
		if (is_array($exchange_raw) && isset($exchange_raw[$language_id])) {
			$data['characteristics_exchange_return'] = html_entity_decode((string)$exchange_raw[$language_id], ENT_QUOTES, 'UTF-8');
		} elseif (is_array($exchange_raw) && !empty($exchange_raw)) {
			$data['characteristics_exchange_return'] = html_entity_decode((string)reset($exchange_raw), ENT_QUOTES, 'UTF-8');
		}

		// Плоский список атрибутів (назва — значення) без груп
		$data['product_attributes_flat'] = [];
		$attribute_groups = $data['attribute_groups'] ?? [];
		foreach ($attribute_groups as $group) {
			foreach ($group['attribute'] ?? [] as $attr) {
				if (!empty($attr['name']) && isset($attr['text'])) {
					$data['product_attributes_flat'][] = ['name' => $attr['name'], 'text' => $attr['text']];
				}
			}
		}

		// Підписи вкладок для блоку характеристик
		$this->load->language('extension/termopab/product/product');
		$data['text_char_section_first'] = $this->language->get('text_char_section_first');
		$data['text_char_section_second'] = $this->language->get('text_char_section_second');
		$data['tab_char_description'] = $this->language->get('tab_char_description');
		$data['tab_char_characteristics'] = $this->language->get('tab_char_characteristics');
		$data['tab_char_review'] = $this->language->get('tab_char_review');
		$data['tab_char_delivery_payment'] = $this->language->get('tab_char_delivery_payment');
		$data['tab_char_exchange_return'] = $this->language->get('tab_char_exchange_return');
		$data['text_reviews'] = $this->language->get('text_reviews');
		$data['text_no_description'] = $this->language->get('text_no_description');
		$data['text_no_attributes'] = $this->language->get('text_no_attributes');
		$data['text_no_review'] = $this->language->get('text_no_review');
		$data['text_no_content'] = $this->language->get('text_no_content');
		$data['text_learn_more'] = $this->language->get('text_learn_more');
		$data['text_expand'] = $this->language->get('text_expand');
		$data['text_collapse'] = $this->language->get('text_collapse');

		// CTA Hop 2 block (заголовок + форма з перекладами)
		$data['cta_hop_2_title_1'] = $this->language->get('cta_hop_2_title_1');
		$data['cta_hop_2_title_2'] = $this->language->get('cta_hop_2_title_2');
		$this->load->language('extension/termopab/module/cta_hop');
		$data['cta_hop_2_form_label_name'] = $this->language->get('form_label_name');
		$data['cta_hop_2_form_placeholder_name'] = $this->language->get('form_placeholder_name');
		$data['cta_hop_2_form_label_phone'] = $this->language->get('form_label_phone');
		$data['cta_hop_2_form_placeholder_phone'] = $this->language->get('form_placeholder_phone');
		$data['cta_hop_2_form_button_text'] = $this->language->get('form_button_text');
		$data['cta_hop_2_form_agreement'] = $this->language->get('form_agreement');
		$view_base = rtrim((string)$this->config->get('config_url'), '/') . '/extension/termopab/catalog/view/';
		$data['cta_hop_2_image'] = $view_base . 'image/hop2.webp';
		$data['cta_hop_2_video'] = $view_base . 'image/explosion.webm';

		// Рекомендовані товари (product_related з адмінки) — формат для products-preview
		$data['products_related_preview'] = [];
		$related_results = $this->model_catalog_product->getRelated($product_id);
		if (!empty($related_results)) {
			$this->load->model('tool/image');
			$desc_len = (int)$this->config->get('config_product_description_length') ?: 120;
			foreach ($related_results as $rel) {
				$rid = (int)($rel['product_id'] ?? 0);
				if ($rid <= 0) {
					continue;
				}
				$img = !empty($rel['image']) && is_file(DIR_IMAGE . html_entity_decode($rel['image'], ENT_QUOTES, 'UTF-8'))
					? $rel['image'] : 'placeholder.png';
				$thumb_w = (int)$this->config->get('config_image_product_width') ?: 300;
				$thumb_h = (int)$this->config->get('config_image_product_height') ?: 300;
				$image_url = $this->model_tool_image->resize($img, $thumb_w, $thumb_h);
				$images = $this->model_catalog_product->getImages($rid);
				$image_hover = $image_url;
				if (!empty($images) && !empty($images[0]['image']) && is_file(DIR_IMAGE . html_entity_decode($images[0]['image'], ENT_QUOTES, 'UTF-8'))) {
					$image_hover = $this->model_tool_image->resize($images[0]['image'], $thumb_w, $thumb_h);
				}
				$attrs = $this->model_catalog_product->getAttributes($rid);
				$params = [];
				foreach ($attrs as $grp) {
					foreach ($grp['attribute'] ?? [] as $a) {
						if (!empty($a['name']) && isset($a['text'])) {
							$params[] = ['label' => $a['name'], 'value' => $a['text']];
						}
					}
				}
				$text = trim((string)($rel['short_description'] ?? ''));
				if ($text === '' && !empty($rel['description'])) {
					$text = trim(strip_tags(html_entity_decode($rel['description'], ENT_QUOTES, 'UTF-8')));
					if (oc_strlen($text) > $desc_len) {
						$text = oc_substr($text, 0, $desc_len) . '..';
					}
				}
				$data['products_related_preview'][] = [
					'image'       => $image_url,
					'image_hover' => $image_hover,
					'title'       => (string)($rel['name'] ?? ''),
					'params'      => $params,
					'text'        => $text,
					'link'        => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $rid),
				];
			}
		}
	}
}
