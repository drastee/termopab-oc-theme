<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Event;

/**
 * Event: override error/not_found template and inject translations.
 * Trigger: view/error/not_found/before
 */
class NotFound extends \Opencart\System\Engine\Controller {

	public function onBefore(string &$route, array &$data, string &$code = '', string &$output = ''): void {
		$this->load->language('extension/termopab/error/not_found');

		$data['text_not_found_message'] = $this->language->get('text_not_found_message');
		$data['text_not_found_go_home'] = $this->language->get('text_not_found_go_home');
		$data['text_not_found_home_label'] = $this->language->get('text_not_found_home_label');
		$data['text_not_found_image_alt'] = $this->language->get('text_not_found_image_alt');

		$base = rtrim((string)$this->config->get('config_url'), '/');
		$data['not_found_image'] = $base . '/extension/termopab/catalog/view/image/404.webp';

		$route = 'extension/termopab/error/not_found';
	}
}
