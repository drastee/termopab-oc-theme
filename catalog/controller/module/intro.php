<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class Intro extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$title = $setting['title'] ?? [];
		$text = $setting['text'] ?? [];

		$language_id = (int)$this->config->get('config_language_id');
		$data['title'] = $title[$language_id] ?? '';
		$data['text'] = $text[$language_id] ?? '';

		if (empty($data['title']) && !empty($title)) {
			$data['title'] = (string)reset($title);
		}
		if (empty($data['text']) && !empty($text)) {
			$data['text'] = (string)reset($text);
		}

		if (empty($data['title'])) {
			$data['title'] = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry';
		}
		if (empty($data['text'])) {
			$data['text'] = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s.';
		}

		// Logo path - from theme build (catalog/view/image/)
		$base = rtrim((string)$this->config->get('config_url'), '/');
		$data['logo_url'] = $base . '/extension/termopab/catalog/view/image/logo-large.webp';
		$data['logo_shadow_url'] = $base . '/extension/termopab/catalog/view/image/logo-large-shadow.webp';

		return $this->load->view('extension/termopab/module/intro', $data);
	}
}
