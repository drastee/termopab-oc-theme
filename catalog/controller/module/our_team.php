<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class OurTeam extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');

		$language_id = (int)$this->config->get('config_language_id');
		$base_url = rtrim((string)$this->config->get('config_url'), '/');

		$title = $setting['title'] ?? [];
		$data['title'] = $title[$language_id] ?? '';
		if ($data['title'] === '' && !empty($title)) {
			$data['title'] = (string)reset($title);
		}

		$founder_name = $setting['founder_name'] ?? [];
		$data['founder_name'] = $founder_name[$language_id] ?? '';
		if ($data['founder_name'] === '' && !empty($founder_name)) {
			$data['founder_name'] = (string)reset($founder_name);
		}

		$founder_role = $setting['founder_role'] ?? [];
		$data['founder_role'] = $founder_role[$language_id] ?? '';
		if ($data['founder_role'] === '' && !empty($founder_role)) {
			$data['founder_role'] = (string)reset($founder_role);
		}

		$text = $setting['text'] ?? [];
		$data['text'] = $text[$language_id] ?? '';
		if ($data['text'] === '' && !empty($text)) {
			$data['text'] = (string)reset($text);
		}
		// Якщо в БД збережено entity-encoded теги (&lt; &gt;) — декодуємо для виводу як HTML
		if ($data['text'] !== '') {
			$data['text'] = html_entity_decode($data['text'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
		}
		// Якщо текст без HTML (старий формат) — перетворюємо переноси на <br>
		if ($data['text'] !== '' && strpos($data['text'], '<') === false) {
			$data['text'] = nl2br(htmlspecialchars($data['text'], ENT_QUOTES, 'UTF-8'), false);
		}

		$button_text = $setting['button_text'] ?? [];
		$data['button_text'] = $button_text[$language_id] ?? '';
		if ($data['button_text'] === '' && !empty($button_text)) {
			$data['button_text'] = (string)reset($button_text);
		}

		$button_url = $setting['button_url'] ?? [];
		if (!is_array($button_url)) {
			$data['button_url'] = trim((string)$button_url) ?: '#';
		} else {
			$data['button_url'] = trim($button_url[$language_id] ?? '') ?: '#';
			if ($data['button_url'] === '#' && !empty($button_url)) {
				$data['button_url'] = trim((string)reset($button_url)) ?: '#';
			}
		}

		$owner_photo = trim((string)($setting['owner_photo'] ?? ''));
		if ($owner_photo && is_file(DIR_IMAGE . html_entity_decode($owner_photo, ENT_QUOTES, 'UTF-8'))) {
			$data['owner_photo_url'] = $base_url . '/image/' . str_replace(' ', '%20', $owner_photo);
		} else {
			$data['owner_photo_url'] = '';
		}

		$extra_image = trim((string)($setting['extra_image'] ?? ''));
		if ($extra_image === '' && !empty($setting['extra_images']) && is_array($setting['extra_images'])) {
			$first = reset($setting['extra_images']);
			$extra_image = is_string($first) ? trim($first) : trim((string)($first['image'] ?? ''));
		}
		if ($extra_image !== '' && is_file(DIR_IMAGE . html_entity_decode($extra_image, ENT_QUOTES, 'UTF-8'))) {
			$data['extra_image_url'] = $base_url . '/image/' . str_replace(' ', '%20', $extra_image);
		} else {
			$data['extra_image_url'] = '';
		}

		return $this->load->view('extension/termopab/module/our_team', $data);
	}
}
