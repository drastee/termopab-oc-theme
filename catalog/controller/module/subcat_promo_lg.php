<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class SubcatPromoLg extends \Opencart\System\Engine\Controller {

	public function index(array $setting): string {
		$language_id = (int)$this->config->get('config_language_id');

		$text_start = $setting['text_start'][$language_id] ?? '';
		if ($text_start === '' && !empty($setting['text_start'])) {
			$text_start = (string)reset($setting['text_start']);
		}
		$data['text_start'] = html_entity_decode($text_start, ENT_QUOTES, 'UTF-8');

		$text_end = $setting['text_end'][$language_id] ?? '';
		if ($text_end === '' && !empty($setting['text_end'])) {
			$text_end = (string)reset($setting['text_end']);
		}
		$data['text_end'] = html_entity_decode($text_end, ENT_QUOTES, 'UTF-8');

		$base = rtrim((string)$this->config->get('config_url'), '/');
		$view_base = $base . '/extension/termopab/catalog/view/';
		$image_path = isset($setting['image']) ? html_entity_decode(trim($setting['image']), ENT_QUOTES, 'UTF-8') : '';
		$data['image'] = $image_path ? $base . '/image/' . $image_path : $base . '/image/no_image.png';
		$image_hover_path = isset($setting['image_hover']) ? html_entity_decode(trim($setting['image_hover']), ENT_QUOTES, 'UTF-8') : '';
		$data['image_hover'] = $image_hover_path ? $base . '/image/' . $image_hover_path : $data['image'];
		$data['silhouette'] = $view_base . 'image/logo-silhouette3.webp';

		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		return $this->load->view('extension/termopab/module/subcat_promo_lg', $data);
	}
}
