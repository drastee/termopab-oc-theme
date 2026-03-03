<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Event;

class AdminLinks extends \Opencart\System\Engine\Controller {

	private function getCatalogUrl(): string {
		$is_https = !empty($this->request->server['HTTPS']) && $this->request->server['HTTPS'] !== 'off';
		$url = $is_https ? (string)$this->config->get('config_ssl') : (string)$this->config->get('config_url');
		if (!$url && defined('HTTP_CATALOG')) {
			$url = HTTP_CATALOG;
		}
		if (!$url) {
			$url = str_replace('/admin/', '/', $is_https ? HTTPS_SERVER : HTTP_SERVER);
		}
		return rtrim($url, '/') . '/';
	}

	public function onProductListAfter(string &$route, array &$args, string &$output): void {
		if ($output === '') {
			return;
		}

		$language = (string)$this->config->get('config_language');
		$catalog_url = $this->getCatalogUrl();

		$output = (string)preg_replace_callback(
			'/<a\s+href="([^"]*product_id=([0-9]+)[^"]*)"([^>]*)>\s*<i\s+class="fa-solid\s+fa-pencil"[^>]*><\/i>\s*<\/a>/uis',
			function ($m) use ($language, $catalog_url) {
				$id = (int)$m[2];
				$url = $catalog_url . 'index.php?route=product/product&product_id=' . $id;
				
				if ($language) {
					$url .= '&language=' . rawurlencode($language);
				}

				$view_btn = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" data-bs-toggle="tooltip" title="View" class="btn btn-info"><i class="fa-solid fa-eye"></i></a>';
				
				return $view_btn . ' ' . $m[0];
			},
			$output
		);
	}

	public function onCategoryListAfter(string &$route, array &$args, string &$output): void {
		if ($output === '') {
			return;
		}

		$language = (string)$this->config->get('config_language');
		$catalog_url = $this->getCatalogUrl();

		$output = (string)preg_replace_callback(
			'/<a\s+href="([^"]*category_id=([0-9]+)[^"]*)"([^>]*)>\s*<i\s+class="fa-solid\s+fa-pencil"[^>]*><\/i>\s*<\/a>/uis',
			function ($m) use ($language, $catalog_url) {
				$id = (int)$m[2];
				$url = $catalog_url . 'index.php?route=product/category&path=' . $id;
				
				if ($language) {
					$url .= '&language=' . rawurlencode($language);
				}

				$view_btn = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" data-bs-toggle="tooltip" title="View" class="btn btn-info"><i class="fa-solid fa-eye"></i></a>';
				
				return $view_btn . ' ' . $m[0];
			},
			$output
		);
	}

	public function onInformationListAfter(string &$route, array &$args, string &$output): void {
		if ($output === '') {
			return;
		}

		$language = (string)$this->config->get('config_language');
		$catalog_url = $this->getCatalogUrl();

		$output = (string)preg_replace_callback(
			'/<a\s+href="([^"]*information_id=([0-9]+)[^"]*)"([^>]*)>\s*<i\s+class="fa-solid\s+fa-pencil"[^>]*><\/i>\s*<\/a>/uis',
			function ($m) use ($language, $catalog_url) {
				$id = (int)$m[2];
				$url = $catalog_url . 'index.php?route=information/information&information_id=' . $id;
				
				if ($language) {
					$url .= '&language=' . rawurlencode($language);
				}

				$view_btn = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" data-bs-toggle="tooltip" title="View" class="btn btn-info"><i class="fa-solid fa-eye"></i></a>';
				
				return $view_btn . ' ' . $m[0];
			},
			$output
		);
	}
}
