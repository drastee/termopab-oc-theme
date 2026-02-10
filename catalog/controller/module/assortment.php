<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class Assortment extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$title = $setting['title'] ?? [];
		$slides_raw = $setting['slides'] ?? [];

		$language_id = (int)$this->config->get('config_language_id');
		$data['title'] = $title[$language_id] ?? '';
		if (empty($data['title']) && !empty($title)) {
			$data['title'] = (string)reset($title);
		}
		if (empty($data['title'])) {
			$data['title'] = 'Наш асортимент';
		}

		$base = rtrim((string)$this->config->get('config_url'), '/');
		$image_base = $base . '/image/';

		$this->load->model('tool/image');

		$data['slides'] = [];
		foreach ($slides_raw as $slide) {
			$slide_title = $slide['title'][$language_id] ?? '';
			if (empty($slide_title) && !empty($slide['title'])) {
				$slide_title = (string)reset($slide['title']);
			}
			$button_text = $slide['button_text'][$language_id] ?? '';
			if (empty($button_text) && !empty($slide['button_text'])) {
				$button_text = (string)reset($slide['button_text']);
			}
			if (empty($button_text)) {
				$button_text = 'Детальніше';
			}
			$link = $slide['link'][$language_id] ?? '';
			if ($link === '' && !empty($slide['link'])) {
				$link = is_array($slide['link']) ? (string)reset($slide['link']) : trim($slide['link'] ?? '');
			}
			$description = $slide['description'][$language_id] ?? '';
			if (empty($description) && !empty($slide['description'])) {
				$description = (string)reset($slide['description']);
			}
			// Mark as safe HTML so it renders unescaped (CKEditor output)
			$description = $description !== '' ? new \Twig\Markup($description, 'UTF-8') : '';

			$img = trim($slide['image'] ?? '');
			$img_hover = trim($slide['image_hover'] ?? '');
			$image_url = '';
			$image_hover_url = '';
			if ($img) {
				$path = str_starts_with($img, 'catalog/') ? $img : 'catalog/' . ltrim($img, '/');
				$image_url = $image_base . ltrim($path, '/');
			}
			if ($img_hover) {
				$path = str_starts_with($img_hover, 'catalog/') ? $img_hover : 'catalog/' . ltrim($img_hover, '/');
				$image_hover_url = $image_base . ltrim($path, '/');
			}

			$data['slides'][] = [
				'title'         => $slide_title,
				'button_text'   => $button_text,
				'link'          => trim($link),
				'description'   => $description,
				'image_url'     => $image_url,
				'image_hover_url' => $image_hover_url ?: $image_url,
			];
		}

		if (empty($data['slides'])) {
			return '';
		}

		$data['total'] = count($data['slides']);

		return $this->load->view('extension/termopab/module/assortment', $data);
	}
}
