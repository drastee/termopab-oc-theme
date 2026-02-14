<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class HeroSecondary extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$image_desktop = trim($setting['image_desktop'] ?? '');
		$image_mobile  = trim($setting['image_mobile'] ?? '');
		$title_arr     = $setting['title'] ?? [];
		$subtitle_arr  = $setting['subtitle'] ?? [];

		$language_id = (int)$this->config->get('config_language_id');
		$title = $title_arr[$language_id] ?? '';
		if ($title === '' && !empty($title_arr)) {
			$title = (string)reset($title_arr);
		}

		$subtitle = $subtitle_arr[$language_id] ?? '';
		if ($subtitle === '' && !empty($subtitle_arr)) {
			$subtitle = (string)reset($subtitle_arr);
		}

		$base = rtrim((string)$this->config->get('config_url'), '/');
		$image_base = $base . '/image/';

		$data['background_image_desktop'] = '';
		if ($image_desktop) {
			$path = str_starts_with($image_desktop, 'catalog/') ? $image_desktop : 'catalog/' . ltrim($image_desktop, '/');
			$data['background_image_desktop'] = $image_base . $path;
		}

		$data['background_image_mobile'] = '';
		if ($image_mobile) {
			$path = str_starts_with($image_mobile, 'catalog/') ? $image_mobile : 'catalog/' . ltrim($image_mobile, '/');
			$data['background_image_mobile'] = $image_base . $path;
		}

		// Fallback: if mobile is empty, use desktop
		if ($data['background_image_mobile'] === '' && $data['background_image_desktop']) {
			$data['background_image_mobile'] = $data['background_image_desktop'];
		}
		if ($data['background_image_desktop'] === '' && $data['background_image_mobile']) {
			$data['background_image_desktop'] = $data['background_image_mobile'];
		}

		$data['title']    = $title;
		$data['subtitle'] = $subtitle;

		$class_parts = [];
		if (($setting['height_mode'] ?? '') === 'large') {
			$class_parts[] = 'large';
		}
		$custom = trim($setting['custom_class'] ?? '');
		if ($custom !== '') {
			$class_parts[] = $custom;
		}
		$data['class'] = implode(' ', $class_parts);

		return $this->load->view('extension/termopab/module/hero_secondary', $data);
	}
}
