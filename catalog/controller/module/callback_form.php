<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

/**
 * CTA Hop 2 (Termopab) – same data as CTA Hop (title lines, social block, intro, form), questions-style layout (welder-with-tank image).
 */
class CallbackForm extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$language_id = (int)$this->config->get('config_language_id');

		$title_lines = $setting['title_lines'] ?? [];
		$lines = $title_lines[$language_id] ?? [];
		if (empty($lines) && !empty($title_lines)) {
			$lines = (array)reset($title_lines);
		}
		$data['title_first'] = '';
		$data['title_rest'] = [];
		if (!empty($lines)) {
			$data['title_first'] = $lines[0];
			$data['title_rest'] = array_slice($lines, 1);
		}
		if ($data['title_first'] === '' && empty($data['title_rest'])) {
			$this->load->language('extension/termopab/module/callback_form');
			$data['title_first'] = $this->language->get('default_title_first');
			$data['title_rest'] = [$this->language->get('default_title_rest')];
		}

		$data['social_block_enabled'] = isset($setting['social_block_enabled']) ? (int)$setting['social_block_enabled'] : 1;

		$intro_text = $setting['intro_text'] ?? [];
		$data['intro_text'] = trim($intro_text[$language_id] ?? '');
		if ($data['intro_text'] === '' && !empty($intro_text)) {
			$data['intro_text'] = trim((string)reset($intro_text));
		}

		$data['social_links'] = [];
		if ($data['social_block_enabled']) {
			$social_visible = $setting['social_visible'] ?? [];
			$all_social = [
				'instagram' => trim((string)$this->config->get('theme_termopab_social_instagram')),
				'whatsapp'  => trim((string)$this->config->get('theme_termopab_social_whatsapp')),
				'telegram'  => trim((string)$this->config->get('theme_termopab_social_telegram')),
				'facebook'  => trim((string)$this->config->get('theme_termopab_social_facebook')),
				'youtube'   => trim((string)$this->config->get('theme_termopab_social_youtube')),
			];
			foreach ($all_social as $key => $url) {
				$show = isset($social_visible[$key]) ? (int)$social_visible[$key] : 1;
				if ($show && $url !== '') {
					$data['social_links'][$key] = $url;
				}
			}
		}

		$this->load->language('extension/termopab/module/callback_form');
		$data['form_label_name'] = $this->language->get('form_label_name');
		$data['form_placeholder_name'] = $this->language->get('form_placeholder_name');
		$data['form_label_phone'] = $this->language->get('form_label_phone');
		$data['form_placeholder_phone'] = $this->language->get('form_placeholder_phone');
		$data['form_button_text'] = $this->language->get('form_button_text');
		$data['form_agreement'] = $this->language->get('form_agreement');
		$data['form_id'] = 'cta2-form';
		$data['form_class'] = '';
		$data['form_action'] = $this->url->link('extension/termopab/common/callback.save', 'language=' . $this->config->get('config_language'));

		$base = rtrim((string)$this->config->get('config_url'), '/');
		$view_base = $base . '/extension/termopab/catalog/view/';
		$data['image_src'] = $view_base . 'image/welder-with-tank.webp';
		$data['video_src'] = $view_base . 'image/explosion.webm';

		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		return $this->load->view('extension/termopab/module/callback_form', $data);
	}
}
