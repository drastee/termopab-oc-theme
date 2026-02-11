<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class ContactBar extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$title_by_lang = $setting['title'] ?? [];
		$language_id = (int)$this->config->get('config_language_id');

		$data['title'] = $title_by_lang[$language_id] ?? '';
		if ($data['title'] === '' && !empty($title_by_lang)) {
			$data['title'] = (string)reset($title_by_lang);
		}
		if ($data['title'] === '') {
			$data['title'] = 'Телефонуйте';
		}

		$telephone = $this->config->get('theme_termopab_telephone');
		$data['telephones'] = is_string($telephone)
			? array_filter(array_map('trim', explode("\n", $telephone)))
			: [];

		$base = rtrim((string)$this->config->get('config_url'), '/');
		$data['spikelet_url'] = $base . '/extension/termopab/catalog/view/image/spikelet.webp';

		return $this->load->view('extension/termopab/module/contact_bar', $data);
	}
}
