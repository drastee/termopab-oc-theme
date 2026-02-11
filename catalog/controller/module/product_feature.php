<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class ProductFeature extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$slides_raw = $setting['slides'] ?? [];
		$width = (int)($setting['width'] ?? 0) ?: (int)$this->config->get('config_image_product_width') ?: 600;
		$height = (int)($setting['height'] ?? 0) ?: (int)$this->config->get('config_image_product_height') ?: 600;
		$language_id = (int)$this->config->get('config_language_id');

		$this->load->model('tool/image');

		$data['slides'] = [];
		foreach ($slides_raw as $slide) {
			$title = $slide['title'][$language_id] ?? '';
			if ($title === '' && !empty($slide['title'])) {
				$title = (string)reset($slide['title']);
			}
			$text_before = $slide['text_before'][$language_id] ?? '';
			if ($text_before === '' && !empty($slide['text_before'])) {
				$text_before = (string)reset($slide['text_before']);
			}
			$text_after = $slide['text_after'][$language_id] ?? '';
			if ($text_after === '' && !empty($slide['text_after'])) {
				$text_after = (string)reset($slide['text_after']);
			}
			$button_text = $slide['button_text'][$language_id] ?? '';
			if ($button_text === '' && !empty($slide['button_text'])) {
				$button_text = (string)reset($slide['button_text']);
			}
			$href = $slide['href'][$language_id] ?? $slide['href'] ?? '';
			$href = is_string($href) ? trim($href) : '';
			if ($href !== '' && strpos($href, 'http') !== 0 && strpos($href, '/') !== 0) {
				$href = '/' . $href;
			}

			$img = trim($slide['image'] ?? '');
			$img_hover = trim($slide['image_hover'] ?? '');
			if ($img && is_file(DIR_IMAGE . html_entity_decode($img, ENT_QUOTES, 'UTF-8'))) {
				$image_url = $this->model_tool_image->resize($img, $width, $height);
			} else {
				$image_url = $this->model_tool_image->resize('placeholder.png', $width, $height);
			}
			if ($img_hover && is_file(DIR_IMAGE . html_entity_decode($img_hover, ENT_QUOTES, 'UTF-8'))) {
				$image_hover_url = $this->model_tool_image->resize($img_hover, $width, $height);
			} else {
				$image_hover_url = $image_url;
			}

			$data['slides'][] = [
				'title'          => $title,
				'text_before'    => $text_before,
				'text_after'     => $text_after,
				'text_learn_more'=> $button_text !== '' ? $button_text : null,
				'href'           => $href !== '' ? $href : null,
				'image'          => $image_url,
				'image_hover'    => $image_hover_url,
			];
		}

		if (empty($data['slides'])) {
			return '';
		}

		return $this->load->view('extension/termopab/module/product_feature', $data);
	}
}
