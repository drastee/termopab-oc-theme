<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class History extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');

		$language_id = (int)$this->config->get('config_language_id');
		$this->load->language('extension/termopab/module/history');

		$title = $setting['title'] ?? [];
		$data['title'] = $title[$language_id] ?? '';
		if ($data['title'] === '' && !empty($title)) {
			$data['title'] = (string)reset($title);
		}
		if ($data['title'] === '') {
			$data['title'] = $this->language->get('heading_title');
		}

		$slides_raw = $setting['slides'] ?? ($setting['slide'] ?? []);
		$data['history'] = [];
		foreach ($slides_raw as $slide) {
			$content = $slide['content'][$language_id] ?? '';
			if ($content === '' && !empty($slide['content'])) {
				$content = (string)reset($slide['content']);
			}
			if ($content !== '') {
				$content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			}
			$year = trim((string)($slide['year'] ?? ''));
			if ($year !== '' || $content !== '') {
				$data['history'][] = [
					'year'    => $year,
					'content' => $content,
				];
			}
		}

		if (empty($data['history'])) {
			return '';
		}

		return $this->load->view('extension/termopab/module/history', $data);
	}
}
