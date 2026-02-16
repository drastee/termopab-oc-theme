<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class CatPromoSection extends \Opencart\System\Engine\Controller {

	public function index(array $setting): string {
		$language_id = (int)$this->config->get('config_language_id');

		$data['title'] = $setting['title'][$language_id] ?? '';
		if ($data['title'] === '' && !empty($setting['title'])) {
			$data['title'] = (string)reset($setting['title']);
		}

		$data['link_url'] = $setting['link_url'][$language_id] ?? '';
		if ($data['link_url'] === '' && !empty($setting['link_url'])) {
			$data['link_url'] = (string)reset($setting['link_url']);
		}

		$data['link_text'] = $setting['link_text'][$language_id] ?? '';
		if ($data['link_text'] === '' && !empty($setting['link_text'])) {
			$data['link_text'] = (string)reset($setting['link_text']);
		}
		if ($data['link_text'] === '') {
			$data['link_text'] = 'Детальніше';
		}

		$desc = $setting['description'][$language_id] ?? '';
		if ($desc === '' && !empty($setting['description'])) {
			$desc = (string)reset($setting['description']);
		}
		$data['description'] = html_entity_decode($desc, ENT_QUOTES, 'UTF-8');

		$data['type'] = !empty($setting['layout_type']) && $setting['layout_type'] === 'cat-promo--reverse'
			? 'cat-promo--reverse'
			: '';

		$base = rtrim((string)$this->config->get('config_url'), '/');
		$image_path = isset($setting['image']) ? html_entity_decode(trim($setting['image']), ENT_QUOTES, 'UTF-8') : '';
		$image_hover_path = isset($setting['image_hover']) ? html_entity_decode(trim($setting['image_hover']), ENT_QUOTES, 'UTF-8') : '';
		$data['image'] = $image_path ? $base . '/image/' . $image_path : $base . '/image/no_image.png';
		$data['image_hover'] = $image_hover_path ? $base . '/image/' . $image_hover_path : $data['image'];

		return $this->load->view('extension/termopab/module/cat_promo_section', $data);
	}
}
