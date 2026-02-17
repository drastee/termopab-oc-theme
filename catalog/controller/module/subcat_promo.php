<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class SubcatPromo extends \Opencart\System\Engine\Controller {

	public function index(array $setting): string {
		$language_id = (int)$this->config->get('config_language_id');

		$title = $setting['title'][$language_id] ?? '';
		if ($title === '' && !empty($setting['title'])) {
			$title = (string)reset($setting['title']);
		}
		$data['title'] = html_entity_decode($title, ENT_QUOTES, 'UTF-8');

		$text = $setting['text'][$language_id] ?? '';
		if ($text === '' && !empty($setting['text'])) {
			$text = (string)reset($setting['text']);
		}
		$data['text'] = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

		$base = rtrim((string)$this->config->get('config_url'), '/');
		$image_path = isset($setting['image']) ? html_entity_decode(trim($setting['image']), ENT_QUOTES, 'UTF-8') : '';
		$data['image'] = $image_path ? $base . '/image/' . $image_path : $base . '/image/no_image.png';
		$image_hover_path = isset($setting['image_hover']) ? html_entity_decode(trim($setting['image_hover']), ENT_QUOTES, 'UTF-8') : '';
		$data['image_hover'] = $image_hover_path ? $base . '/image/' . $image_hover_path : $data['image'];

		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		return $this->load->view('extension/termopab/module/subcat_promo', $data);
	}
}
