<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class Gallery extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');

		$data['images'] = [];
		$gallery_images = $setting['gallery_image'] ?? [];
		if (!is_array($gallery_images)) {
			$gallery_images = [];
		}

		// Для галереи — только высокое качество: крупные миниатюры (до 1600×1200), попап — оригинал
		$max_thumb_w = 1600;
		$max_thumb_h = 1200;
		$base_url = rtrim((string)$this->config->get('config_url'), '/');

		$this->load->model('tool/image');

		foreach ($gallery_images as $row) {
			$img = trim((string)($row['image'] ?? ''));
			if ($img === '') {
				continue;
			}
			$path = html_entity_decode($img, ENT_QUOTES, 'UTF-8');
			$full_path = DIR_IMAGE . $path;
			if (!is_file($full_path)) {
				continue;
			}
			$info = @getimagesize($full_path);
			if (!$info || !isset($info[0], $info[1])) {
				$data['images'][] = [
					'src'  => $this->model_tool_image->resize($img, $max_thumb_w, $max_thumb_h),
					'href' => $base_url . '/image/' . str_replace(' ', '%20', $img),
				];
				continue;
			}
			$w = (int)$info[0];
			$h = (int)$info[1];
			$scale_thumb = min($max_thumb_w / $w, $max_thumb_h / $h, 1.0);
			$thumb_w = max(1, (int)round($w * $scale_thumb));
			$thumb_h = max(1, (int)round($h * $scale_thumb));
			// src — ресайз для сетки (без обрезки), href — оригинал для лайтбокса (максимальное качество)
			$data['images'][] = [
				'src'  => $this->model_tool_image->resize($img, $thumb_w, $thumb_h),
				'href' => $base_url . '/image/' . str_replace(' ', '%20', $img),
			];
		}

		$data['marquee_enabled'] = isset($setting['marquee_enabled']) ? (int)$setting['marquee_enabled'] : 1;
		$marquee_by_lang = $setting['marquee_text'] ?? [];
		$language_id = (int)$this->config->get('config_language_id');
		$data['marquee_text'] = $marquee_by_lang[$language_id] ?? '';
		if ($data['marquee_text'] === '' && !empty($marquee_by_lang)) {
			$data['marquee_text'] = (string)reset($marquee_by_lang);
		}
		if ($data['marquee_text'] === '') {
			$data['marquee_text'] = 'ЗРОБИМО НАЙКРАЩЕ ПИВО РАЗОМ';
		}

		return $this->load->view('extension/termopab/module/gallery', $data);
	}
}
