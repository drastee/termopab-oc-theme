<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Common;

class Contacts extends \Opencart\System\Engine\Controller {

	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		$this->load->language('extension/termopab/common/contacts');

		$language_id = (int)$this->config->get('config_language_id');

		$contacts_title = $this->config->get('theme_termopab_contacts_title');
		$data['contacts_title'] = (is_array($contacts_title) && isset($contacts_title[$language_id])) ? (string)$contacts_title[$language_id] : '';
		$contacts_address_1 = $this->config->get('theme_termopab_contacts_address_1');
		$data['contacts_address_1'] = (is_array($contacts_address_1) && isset($contacts_address_1[$language_id])) ? (string)$contacts_address_1[$language_id] : '';
		$contacts_address_2 = $this->config->get('theme_termopab_contacts_address_2');
		$data['contacts_address_2'] = (is_array($contacts_address_2) && isset($contacts_address_2[$language_id])) ? (string)$contacts_address_2[$language_id] : '';
		$map_iframe_raw = (string)$this->config->get('theme_termopab_contacts_map_iframe');
		$data['contacts_map_iframe'] = html_entity_decode($map_iframe_raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

		if ($data['contacts_title'] !== '') {
			$this->document->setTitle($data['contacts_title']);
		} else {
			$this->document->setTitle($this->language->get('heading_title'));
		}

		$telephone = $this->config->get('theme_termopab_telephone');
		$data['telephones'] = is_string($telephone)
			? array_filter(array_map('trim', explode("\n", $telephone)))
			: [];
		$email = $this->config->get('theme_termopab_email');
		$data['email'] = is_string($email) ? $email : '';

		$schedule = $this->config->get('theme_termopab_schedule');
		$schedule_text = (is_array($schedule) && isset($schedule[$language_id])) ? $schedule[$language_id] : '';
		if ($schedule_text) {
			$lines = array_filter(array_map('trim', explode("\n", (string)$schedule_text)));
			$data['schedule'] = implode('', array_map(function ($line) {
				return '<p>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
			}, $lines));
		} else {
			$data['schedule'] = '';
		}

		$social_keys = ['instagram', 'whatsapp', 'telegram', 'facebook', 'youtube'];
		$all_social_links = [
			'instagram' => trim((string)$this->config->get('theme_termopab_social_instagram')),
			'whatsapp'  => trim((string)$this->config->get('theme_termopab_social_whatsapp')),
			'telegram'  => trim((string)$this->config->get('theme_termopab_social_telegram')),
			'facebook'  => trim((string)$this->config->get('theme_termopab_social_facebook')),
			'youtube'   => trim((string)$this->config->get('theme_termopab_social_youtube')),
		];
		$footer_social_links = [];
		foreach ($social_keys as $key) {
			$url = $all_social_links[$key] ?? '';
			$url = $url !== '' ? $url : '#';
			$footer_val = $this->config->get('theme_termopab_footer_social_' . $key);
			if ($footer_val !== 0 && $footer_val !== '0') {
				$footer_social_links[$key] = $url;
			}
		}
		$data['footer_social_links'] = $footer_social_links;

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/common/contacts', 'language=' . $this->config->get('config_language'))
		];

		$data['header'] = $this->load->controller('common/header');
		$data['content_top'] = $this->load->controller('extension/termopab/common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/common/contacts', $data));
	}
}
