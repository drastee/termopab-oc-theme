<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Checkout;

/**
 * Checkout page: same form and logic as modal cart (single form, modal_cart.saveAddresses).
 * Route override: checkout/checkout → extension/termopab/checkout/checkout.
 */
class Checkout extends \Opencart\System\Engine\Controller {

	public function index(): void {
		if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout')) || !$this->cart->hasMinimum()) {
			$this->response->redirect($this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'), true));
			return;
		}

		$this->load->language('checkout/checkout');
		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$home_raw = $this->language->get('text_home');
		$data['breadcrumb_home_name'] = trim(strip_tags($home_raw ?? '')) ?: 'Головна';
		$data['breadcrumbs'][] = [
			'text' => $home_raw,
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_cart'),
			'href' => $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'))
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('checkout/checkout', 'language=' . $this->config->get('config_language'))
		];

		$data['heading_title'] = $this->language->get('heading_title');

		$this->event->trigger('extension/termopab/checkout/form_data', [&$data]);

		$data['column_left'] = $this->load->controller('common/column_left');
		$data['column_right'] = $this->load->controller('common/column_right');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/termopab/checkout/checkout', $data));
	}
}
