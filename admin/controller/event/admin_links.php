<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Event;

class AdminLinks extends \Opencart\System\Engine\Controller {

	private function getCatalogUrl(): string {
		$is_https = !empty($this->request->server['HTTPS']) && $this->request->server['HTTPS'] !== 'off';
		$url = '';
		$catalog_subdir = '';

		if (defined('DIR_CATALOG') && is_string(DIR_CATALOG) && rtrim(str_replace('\\', '/', DIR_CATALOG), '/') !== '') {
			$dir_catalog = rtrim(str_replace('\\', '/', DIR_CATALOG), '/') . '/';
			if (str_ends_with($dir_catalog, '/catalog/')) {
				$catalog_subdir = 'catalog/';
			}
		}

		if ($is_https && defined('HTTPS_CATALOG') && HTTPS_CATALOG) {
			$url = HTTPS_CATALOG;
		} elseif (defined('HTTP_CATALOG') && HTTP_CATALOG) {
			$url = HTTP_CATALOG;
		}

		if ($url && $catalog_subdir) {
			$path = (string)(parse_url($url, PHP_URL_PATH) ?? '');
			if (!str_ends_with($path, '/' . $catalog_subdir) && !str_ends_with($path, $catalog_subdir)) {
				$url = rtrim($url, '/') . '/' . $catalog_subdir;
			}
		}

		if (!$url) {
			$url = $is_https ? (string)$this->config->get('config_ssl') : (string)$this->config->get('config_url');
		}
		if (!$url) {
			$url = str_replace('/admin/', '/', $is_https ? HTTPS_SERVER : HTTP_SERVER);
		}
		return rtrim($url, '/') . '/';
	}

	/**
	 * Tries to fetch the SEO URL for a specific key/value pair.
	 */
	private function getSeoUrl(string $key, string $value, int $store_id = 0, int $language_id = 0): string {
		$query = "SELECT `keyword` FROM `" . DB_PREFIX . "seo_url` WHERE `key` = '" . $this->db->escape($key) . "' AND `value` = '" . $this->db->escape($value) . "'";
		$query .= " AND `store_id` = '" . (int)$store_id . "'";
		if ($language_id > 0) {
			$query .= " AND `language_id` = '" . (int)$language_id . "'";
		}
		$query .= " LIMIT 1";
		$result = $this->db->query($query);
		
		if ($result->num_rows) {
			return (string)$result->row['keyword'];
		}
		
		return '';
	}

	/**
	 * Dynamically builds the SEO-friendly URL by resolving both route and parameters.
	 */
	private function buildCatalogUrl(string $route, array $params, int $store_id, int $language_id): string {
		$catalog_url = $this->getCatalogUrl();
		$keywords = [];
		$query_parts = [];
		$can_build_seo = true;
		$route_keyword = '';

		foreach ($params as $key => $value) {
			if ($key === 'path') {
				$kw = $this->getSeoUrl('path', (string)$value, $store_id, $language_id);
				if ($kw) {
					$keywords[] = $kw;
				} else {
					$can_build_seo = false;
				}
			} elseif ($key === 'product_id' || $key === 'information_id') {
				$kw = $this->getSeoUrl($key, (string)$value, $store_id, $language_id);
				if ($kw) {
					$keywords[] = $kw;
				} else {
					$can_build_seo = false;
				}
			} else {
				$query_parts[] = $key . '=' . $value;
			}
		}

		// Always include required parameters in the fallback URL
		$required_query_parts = [];
		foreach ($params as $key => $value) {
			if ($key === 'path' || $key === 'product_id' || $key === 'information_id') {
				$required_query_parts[] = $key . '=' . (string)$value;
			}
		}

		// Fallback to standard URL if we can't build a complete SEO URL
		if (!$can_build_seo || empty($keywords)) {
			$url = $catalog_url . 'index.php?route=' . $route;
			$all_query_parts = array_merge($required_query_parts, $query_parts);
			if ($all_query_parts) {
				$url .= '&' . implode('&', $all_query_parts);
			}
			return $url;
		}

		// Build SEO URL
		$url = $catalog_url . implode('/', $keywords);
		
		// Add remaining non-SEO query parameters
		if ($query_parts) {
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
		$store_id = (int)$this->config->get('config_store_id');

		$output = (string)preg_replace_callback(
			'/<a\s+href="([^"]*product_id=([0-9]+)[^"]*)"([^>]*)>\s*<i\s+class="fa-solid\s+fa-pencil"[^>]*><\/i>\s*<\/a>/uis',
			function ($m) use ($language, $language_id, $store_id) {
				$params = ['product_id' => (int)$m[2]];
				if ($language) {
					$params['language'] = rawurlencode($language);
				}
				$url = $this->buildCatalogUrl('product/product', $params, $store_id, $language_id);

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
		$store_id = (int)$this->config->get('config_store_id');
		$this->load->model('catalog/category');

		$output = (string)preg_replace_callback(
			'/<a\s+href="([^"]*category_id=([0-9]+)[^"]*)"([^>]*)>\s*<i\s+class="fa-solid\s+fa-pencil"[^>]*><\/i>\s*<\/a>/uis',
			function ($m) use ($language, $language_id, $store_id) {
				$path = $this->model_catalog_category->getPath((int)$m[2]);
				$params = ['path' => $path ?: (int)$m[2]];
				if ($language) {
					$params['language'] = rawurlencode($language);
				}
				$url = $this->buildCatalogUrl('product/category', $params, $store_id, $language_id);

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
		$store_id = (int)$this->config->get('config_store_id');

		$output = (string)preg_replace_callback(
			'/<a\s+href="([^"]*information_id=([0-9]+)[^"]*)"([^>]*)>\s*<i\s+class="fa-solid\s+fa-pencil"[^>]*><\/i>\s*<\/a>/uis',
			function ($m) use ($language, $language_id, $store_id) {
				$params = ['information_id' => (int)$m[2]];
				if ($language) {
					$params['language'] = rawurlencode($language);
				}
				$url = $this->buildCatalogUrl('information/information', $params, $store_id, $language_id);

				$view_btn = '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" data-bs-toggle="tooltip" title="View" class="btn btn-info"><i class="fa-solid fa-eye"></i></a>';
				
				return $view_btn . ' ' . $m[0];
			},
			$output
		);
	}
}
