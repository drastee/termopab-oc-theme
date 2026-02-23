<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class About extends \Opencart\System\Engine\Controller {
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
			$data['title_first'] = html_entity_decode((string)$lines[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
			$rest = array_slice($lines, 1);
			$data['title_rest'] = array_map(function ($line) {
				return html_entity_decode((string)$line, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			}, $rest);
		}
		if ($data['title_first'] === '' && empty($data['title_rest'])) {
			$data['title_first'] = 'Компанія Термопаб';
			$data['title_rest'] = ['це історія про якість', 'та довіру наших клієнтів'];
		}

		$description = $setting['description'] ?? [];
		$data['description'] = $description[$language_id] ?? '';
		if ($data['description'] === '' && !empty($description)) {
			$data['description'] = (string)reset($description);
		}

		$button_text = $setting['button_text'] ?? [];
		$data['button_text'] = $button_text[$language_id] ?? '';
		if ($data['button_text'] === '' && !empty($button_text)) {
			$data['button_text'] = (string)reset($button_text);
		}

		$data['button_url'] = trim($setting['button_url'] ?? '') ?: '#';

		$facts_raw = $setting['facts'] ?? [];
		$data['facts'] = [];
		foreach ($facts_raw as $f) {
			$title = $f['title'][$language_id] ?? '';
			if ($title === '' && !empty($f['title'])) {
				$title = (string)reset($f['title']);
			}
			$text = $f['text'][$language_id] ?? '';
			if ($text === '' && !empty($f['text'])) {
				$text = (string)reset($f['text']);
			}
			$url = trim($f['url'] ?? '') ?: '#';
			$data['facts'][] = [
				'title' => $title,
				'text'  => $text,
				'url'   => $url,
			];
		}

		return $this->load->view('extension/termopab/module/about', $data);
	}
}
