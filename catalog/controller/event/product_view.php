<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Event;

/**
 * Event: inject view_360, view_360_url, button_cart for product page main-media block.
 * Trigger: catalog/view/product/product/before
 */
class ProductView extends \Opencart\System\Engine\Controller {

	public function onProductViewBefore(string &$route, array &$data, string &$code = '', string &$output = ''): void {
		$product_id = (int)($data['product_id'] ?? 0);
		if ($product_id <= 0) {
			return;
		}

		$this->load->model('catalog/product');
		$product_info = $this->model_catalog_product->getProduct($product_id);
		if (!$product_info) {
			return;
		}

		$data['view_360'] = trim((string)($product_info['view_360'] ?? ''));
		$data['view_360_url'] = '';
		if ($data['view_360'] !== '') {
			$data['view_360_url'] = $this->url->link('extension/termopab/tool/glb.serve', 'code=' . urlencode($data['view_360']));
		}

		$this->load->language('common/default');
		if (!isset($data['button_cart']) || $data['button_cart'] === '') {
			$data['button_cart'] = $this->language->get('button_cart');
		}
	}
}
