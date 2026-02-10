<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class Hero extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$video = trim($setting['video'] ?? '');
		$poster = trim($setting['poster'] ?? '');
		$title_lines = $setting['title_lines'] ?? [];

		$language_id = (int)$this->config->get('config_language_id');
		$lines = $title_lines[$language_id] ?? [];
		if (empty($lines) && !empty($title_lines)) {
			$lines = (array)reset($title_lines);
		}
		if (empty($lines)) {
			$lines = ['Зробимо', 'найкраще пиво', 'разом'];
		}

		$base = rtrim((string)$this->config->get('config_url'), '/');
		$image_base = $base . '/image/';

		$data['video_url'] = '';
		if ($video) {
			$video_path = str_starts_with($video, 'catalog/') ? $video : 'catalog/' . ltrim($video, '/');
			$data['video_url'] = $image_base . ltrim($video_path, '/');
		}

		$data['poster_url'] = '';
		if ($poster) {
			$poster_path = str_starts_with($poster, 'catalog/') ? $poster : 'catalog/' . ltrim($poster, '/');
			$data['poster_url'] = $image_base . ltrim($poster_path, '/');
		}

		$data['title_lines'] = $lines;

		return $this->load->view('extension/termopab/module/hero', $data);
	}
}
