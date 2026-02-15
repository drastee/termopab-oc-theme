<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class RunningLineSection extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$text_by_lang = $setting['text'] ?? [];
		$language_id = (int)$this->config->get('config_language_id');

		$data['text'] = $text_by_lang[$language_id] ?? '';
		if ($data['text'] === '' && !empty($text_by_lang)) {
			$data['text'] = (string)reset($text_by_lang);
		}
		if ($data['text'] === '') {
			$data['text'] = 'ЗРОБИМО НАЙКРАЩЕ ПИВО РАЗОМ';
		}

		// Статичное изображение из сборки (npm run build:theme → catalog/view/image/cap.webp)
		$base = rtrim((string)$this->config->get('config_url'), '/');
		$data['image'] = $base . '/extension/termopab/catalog/view/image/cap.webp';

		return $this->load->view('extension/termopab/module/running_line_section', $data);
	}
}
