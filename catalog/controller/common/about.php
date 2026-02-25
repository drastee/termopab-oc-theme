<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Common;

/**
 * Сторінка «Про нас». Маршрут: extension/termopab/common/about
 * Макет такий самий як у common/home — можна наповнювати блоками в Дизайн → Макети.
 */
class About extends \Opencart\System\Engine\Controller {

	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		$this->load->language('extension/termopab/common/about');

		$language_id = (int)$this->config->get('config_language_id');
		$meta_title = $this->config->get('theme_termopab_about_meta_title');
		if (is_array($meta_title) && isset($meta_title[$language_id]) && (string)$meta_title[$language_id] !== '') {
			$this->document->setTitle((string)$meta_title[$language_id]);
		} else {
			$this->document->setTitle($this->language->get('heading_title'));
		}
		$meta_description = $this->config->get('theme_termopab_about_meta_description');
		if (is_array($meta_description) && isset($meta_description[$language_id]) && (string)$meta_description[$language_id] !== '') {
			$this->document->setDescription((string)$meta_description[$language_id]);
		}
		$meta_keyword = $this->config->get('theme_termopab_about_meta_keyword');
		if (is_array($meta_keyword) && isset($meta_keyword[$language_id]) && (string)$meta_keyword[$language_id] !== '') {
			$this->document->setKeywords((string)$meta_keyword[$language_id]);
		}

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/common/about', 'language=' . $this->config->get('config_language'))
		];

		$data['header'] = $this->load->controller('common/header');
		$data['content_top'] = $this->load->controller('extension/termopab/common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/common/about', $data));
	}
}
