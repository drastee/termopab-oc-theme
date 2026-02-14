<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

/**
 * CTA Barrel (without form) – title, subtitle, barrel image, social links from theme.
 */
class CtaBarrel extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$language_id = (int)$this->config->get('config_language_id');

		$title = $setting['title'] ?? [];
		$subtitle = $setting['subtitle'] ?? [];
		if (!is_array($title)) {
			$title = $title !== '' ? [$language_id => $title] : [];
		}
		if (!is_array($subtitle)) {
			$subtitle = $subtitle !== '' ? [$language_id => $subtitle] : [];
		}
		$data['title'] = trim($title[$language_id] ?? '');
		$data['subtitle'] = trim($subtitle[$language_id] ?? '');
		if ($data['title'] === '' && !empty($title)) {
			$data['title'] = trim((string)reset($title));
		}
		if ($data['subtitle'] === '' && !empty($subtitle)) {
			$data['subtitle'] = trim((string)reset($subtitle));
		}
		// Convert newlines to <br> for display
		$data['subtitle'] = nl2br($data['subtitle'], false);

		$social_visible = $setting['social_visible'] ?? [];
		$all_social = [
			'instagram' => trim((string)$this->config->get('theme_termopab_social_instagram')),
			'whatsapp'  => trim((string)$this->config->get('theme_termopab_social_whatsapp')),
			'telegram'  => trim((string)$this->config->get('theme_termopab_social_telegram')),
			'facebook'  => trim((string)$this->config->get('theme_termopab_social_facebook')),
			'youtube'   => trim((string)$this->config->get('theme_termopab_social_youtube')),
		];
		$data['social_links'] = [];
		foreach ($all_social as $key => $url) {
			$show = isset($social_visible[$key]) ? (int)$social_visible[$key] : 1;
			if ($show && $url !== '') {
				$data['social_links'][$key] = $url;
			}
		}

		$base = rtrim((string)$this->config->get('config_url'), '/');
		$data['image_src'] = $base . '/extension/termopab/catalog/view/image/barrel.webp';

		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		return $this->load->view('extension/termopab/module/cta_barrel', $data);
	}
}
