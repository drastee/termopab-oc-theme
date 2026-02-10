<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class ProductsPreview extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$this->load->language('extension/termopab/theme/termopab');

		$data['text_learn_more'] = $this->language->get('text_learn_more');
		$data['products'] = [];

		$items = $setting['items'] ?? [];
		$width = (int)($setting['width'] ?? 0) ?: (int)$this->config->get('config_image_product_width') ?: 300;
		$height = (int)($setting['height'] ?? 0) ?: (int)$this->config->get('config_image_product_height') ?: 300;

		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		foreach ($items as $item) {
			$product_id = (int)($item['product_id'] ?? 0);
			if (!$product_id) continue;

			$product_info = $this->model_catalog_product->getProduct($product_id);
			if (!$product_info) continue;

			if ($product_info['image']) {
				$thumb = $this->model_tool_image->resize(
					html_entity_decode($product_info['image'], ENT_QUOTES, 'UTF-8'),
					$width,
					$height
				);
			} else {
				$thumb = $this->model_tool_image->resize('placeholder.png', $width, $height);
			}

			$thumb_hover = $thumb;
			$images = $this->model_catalog_product->getImages($product_info['product_id']);
			if (!empty($images[0]['image'])) {
				$thumb_hover = $this->model_tool_image->resize(
					html_entity_decode($images[0]['image'], ENT_QUOTES, 'UTF-8'),
					$width,
					$height
				);
			}

			$language_id = (int)$this->config->get('config_language_id');
			$custom_desc = $item['description'][$language_id] ?? '';
			if ($custom_desc !== '') {
				$description_short = $custom_desc;
				$description = $custom_desc;
			} else {
				$desc_len = (int)$this->config->get('config_product_description_length') ?: 200;
				$description = trim(strip_tags(html_entity_decode($product_info['description'], ENT_QUOTES, 'UTF-8')));
				$description_short = oc_substr($description, 0, $desc_len) . (oc_strlen($description) > $desc_len ? '..' : '');
			}

			$attribute_ids = $item['attribute'] ?? [];
			$params = [];
			if (!empty($attribute_ids)) {
				$attribute_groups = $this->model_catalog_product->getAttributes($product_info['product_id']);
				foreach ($attribute_groups as $group) {
					foreach ($group['attribute'] ?? [] as $attr) {
						if (in_array((int)$attr['attribute_id'], array_map('intval', $attribute_ids))) {
							$params[] = [
								'label' => $attr['name'],
								'value' => $attr['text']
							];
						}
					}
				}
			}

			$data['products'][] = [
				'image'        => $thumb,
				'image_hover'  => $thumb_hover,
				'title'        => $product_info['name'],
				'params'       => $params,
				'text'         => $description_short,
				'link'         => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $product_info['product_id']),
			];
		}

		if (empty($data['products'])) {
			return '';
		}

		return $this->load->view('extension/termopab/module/products_preview', $data);
	}
}
