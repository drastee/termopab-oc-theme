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

	/**
	 * Tries to fetch the SEO URL for a specific key/value pair.
	 */
	private function getSeoUrl(string $key, string $value, int $language_id = 0): string {
		$query = "SELECT `keyword` FROM `" . DB_PREFIX . "seo_url` WHERE `key` = '" . $this->db->escape($key) . "' AND `value` = '" . $this->db->escape($value) . "'";
		if ($language_id > 0) {
			$query .= " AND `language_id` = '" . (int)$language_id . "'";
		}
		
		// Limit to store_id 0 (default storefront) or fallback to any
		$query .= " ORDER BY `store_id` ASC LIMIT 1";
		
		$result = $this->db->query($query);
		
		if ($result->num_rows) {
			return (string)$result->row['keyword'];
		}
		
		return '';
	}

	/**
	 * Dynamically builds the SEO-friendly URL by resolving both route and parameters.
	 */
	private function buildCatalogUrl(string $route, array $params, int $language_id): string {
		$catalog_url = $this->getCatalogUrl();
		$keywords = [];
		$query_parts = [];

		// Try to resolve route keyword (e.g. route=product/product -> 'product')
		$route_keyword = $this->getSeoUrl('route', $route, $language_id);
		if ($route_keyword) {
			$keywords[] = $route_keyword;
		}

		foreach ($params as $key => $value) {
			if ($key === 'path') {
				$categories = explode('_', (string)$value);
				foreach ($categories as $category) {
					$kw = $this->getSeoUrl('category_id', (string)$category, $language_id);
					if ($kw) {
						$keywords[] = $kw;
					} else {
						$query_parts[] = 'path=' . $value;
					}
				}
			} elseif ($key === 'product_id' || $key === 'information_id') {
				$kw = $this->getSeoUrl($key, (string)$value, $language_id);
				if ($kw) {
					$keywords[] = $kw;
				} else {
					$query_parts[] = $key . '=' . $value;
				}
			} else {
				$query_parts[] = $key . '=' . $value;
			}
		}

		// Fallback to standard URL if no keywords at all
		if (empty($keywords)) {
			$url = $catalog_url . 'index.php?route=' . $route;
			if ($query_parts) {
				$url .= '&' . implode('&', $query_parts);
			}
			return $url;
		}

		// Build SEO URL
		$url = $catalog_url . implode('/', $keywords);
		
		// Add remaining non-SEO query parameters
		if ($query_parts) {
			if (!$route_keyword && empty($keywords)) {
				array_unshift($query_parts, 'route=' . $route);
			}
			$url .= '?' . implode('&', $query_parts);
		}

		return $url;
	}

	public function onProductListAfter(string &$route, array &$args, string &$output): void {
		if ($output === '') {
			return;
		}

		$language = (string)$this->config->get('config_language');
		$language_id = (int)$this->config->get('config_language_id');

		$output = (string)preg_replace_callback(
			'/<a\s+href="([^"]*product_id=([0-9]+)[^"]*)"([^>]*)>\s*<i\s+class="fa-solid\s+fa-pencil"[^>]*><\/i>\s*<\/a>/uis',
			function ($m) use ($language, $language_id) {
				$params = ['product_id' => (int)$m[2]];
				if ($language) {
					$params['language'] = rawurlencode($language);
				}
				$url = $this->buildCatalogUrl('product/product', $params, $language_id);

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
		$language_id = (int)$this->config->get('config_language_id');

		$output = (string)preg_replace_callback(
			'/<a\s+href="([^"]*category_id=([0-9]+)[^"]*)"([^>]*)>\s*<i\s+class="fa-solid\s+fa-pencil"[^>]*><\/i>\s*<\/a>/uis',
			function ($m) use ($language, $language_id) {
				$params = ['path' => (int)$m[2]];
				if ($language) {
					$params['language'] = rawurlencode($language);
				}
				$url = $this->buildCatalogUrl('product/category', $params, $language_id);

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
		$language_id = (int)$this->config->get('config_language_id');

		$output = (string)preg_replace_callback(
			'/<a\s+href="([^"]*information_id=([0-9]+)[^"]*)"([^>]*)>\s*<i\s+class="fa-solid\s+fa-pencil"[^>]*><\/i>\s*<\/a>/uis',
			function ($m) use ($language, $language_id) {
				$params = ['information_id' => (int)$m[2]];
				if ($language) {
					$params['language'] = rawurlencode($language);
				}
				$url = $this->buildCatalogUrl('information/information', $params, $language_id);

				$view_btn = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" data-bs-toggle="tooltip" title="View" class="btn btn-info"><i class="fa-solid fa-eye"></i></a>';
				
				return $view_btn . ' ' . $m[0];
			},
			$output
		);
	}
}
