<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class CertificatesSlider extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		if (empty($setting['status'])) {
			return '';
		}

		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');

		$language_id = (int)$this->config->get('config_language_id');

		$title_by_lang = $setting['title'] ?? [];
		if (!is_array($title_by_lang)) {
			$title_by_lang = $title_by_lang !== '' ? [$language_id => $title_by_lang] : [];
		}
		$data['title'] = $title_by_lang[$language_id] ?? '';
		if ($data['title'] === '' && !empty($title_by_lang)) {
			$data['title'] = (string)reset($title_by_lang);
		}

		$rows = $setting['certificate_image'] ?? [];
		if (!is_array($rows)) {
			$rows = [];
		}

		if (empty($rows)) {
			return '';
		}

		$this->load->model('tool/image');

		$base_url = rtrim((string)$this->config->get('config_url'), '/');

		$data['images'] = [];
		$max_thumb_w = 800;
		$max_thumb_h = 800;

		foreach ($rows as $row) {
			$img = trim((string)($row['image'] ?? ''));
			if ($img === '') {
				continue;
			}
			$path = html_entity_decode($img, ENT_QUOTES, 'UTF-8');
			$full_path = DIR_IMAGE . $path;
			if (!is_file($full_path)) {
				continue;
			}

			$href = $base_url . '/image/' . str_replace(' ', '%20', $img);

			$info = @getimagesize($full_path);
			if ($info && isset($info[0], $info[1])) {
				$w = (int)$info[0];
				$h = (int)$info[1];
				$scale_thumb = min($max_thumb_w / $w, $max_thumb_h / $h, 1.0);
				$thumb_w = max(1, (int)round($w * $scale_thumb));
				$thumb_h = max(1, (int)round($h * $scale_thumb));
				$src = $this->model_tool_image->resize($img, $thumb_w, $thumb_h);
			} else {
				$src = $this->model_tool_image->resize($img, $max_thumb_w, $max_thumb_h);
			}

			$data['images'][] = [
				'src' => $src,
				'href' => $href,
			];
		}

		if (empty($data['images'])) {
			return '';
		}

		return $this->load->view('extension/termopab/module/certificates_slider', $data);
	}
}
