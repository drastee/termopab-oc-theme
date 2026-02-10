<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Startup;

class Termopab extends \Opencart\System\Engine\Controller {
	public function index(): void {
		if ($this->config->get('theme_termopab_status')) {
			$this->addDesignPath();
			$this->event->register('view/*/before', new \Opencart\System\Engine\Action('extension/termopab/startup/termopab.event'));
		}
	}

	/**
	 * Add src/html path to Twig loader for @termopab namespace
	 */
	private function addDesignPath(): void {
		$template = $this->registry->get('template');
		$reflection = new \ReflectionClass($template);
		$adaptorProp = $reflection->getProperty('adaptor');
		$adaptorProp->setAccessible(true);
		$adaptor = $adaptorProp->getValue($template);

		$adaptorReflection = new \ReflectionClass($adaptor);
		$loaderProp = $adaptorReflection->getProperty('loader');
		$loaderProp->setAccessible(true);
		$loader = $loaderProp->getValue($adaptor);

		$loader->addPath(DIR_OPENCART . 'extension/termopab/src/html', 'termopab');
		$loader->addPath(DIR_OPENCART . 'extension/termopab/src/html/components', 'components');
		$loader->addPath(DIR_OPENCART . 'extension/termopab/catalog/view/image', 'assets');
	}

	public function event(string &$route, array &$data, string &$code, string &$output): void {
		$override = [
			'common/header',
			'common/footer',
			'common/home',
			'common/currency',
			'common/language',
		];
		if (in_array($route, $override)) {
			$route = 'extension/termopab/' . $route;
		}

		if (in_array($route, ['extension/termopab/common/header', 'extension/termopab/common/footer'])) {
			$this->addThemeData($data);
		}
	}

	/**
	 * Add theme settings to view data for header/footer
	 */
	private function addThemeData(array &$data): void {
		$language_id = (int)$this->config->get('config_language_id');

		$brand = $this->config->get('theme_termopab_brand');
		$data['brand'] = (is_array($brand) && isset($brand[$language_id])) ? $brand[$language_id] : '';

		$address = $this->config->get('theme_termopab_address');
		$data['address'] = (is_array($address) && isset($address[$language_id])) ? $address[$language_id] : '';

		$telephone = $this->config->get('theme_termopab_telephone');
		$data['telephones'] = is_string($telephone)
			? array_filter(array_map('trim', explode("\n", $telephone)))
			: [];

		if (!isset($data['telephone'])) {
			$data['telephone'] = $this->config->get('config_telephone');
		}
		if (!isset($data['name'])) {
			$data['name'] = $this->config->get('config_name');
		}

		$email = $this->config->get('theme_termopab_email');
		$data['email'] = is_string($email) ? $email : '';

		$schedule = $this->config->get('theme_termopab_schedule');
		$schedule_text = (is_array($schedule) && isset($schedule[$language_id])) ? $schedule[$language_id] : '';
		if ($schedule_text) {
			$lines = array_filter(array_map('trim', explode("\n", $schedule_text)));
			$data['schedule'] = implode('', array_map(function ($line) {
				return '<p>' . htmlspecialchars($line, ENT_QUOTES, 'UTF-8') . '</p>';
			}, $lines));
		} else {
			$data['schedule'] = '';
		}

		$worknote = $this->config->get('theme_termopab_worknote');
		$data['worknote'] = (is_array($worknote) && isset($worknote[$language_id])) ? $worknote[$language_id] : '';

		$this->load->language('extension/termopab/theme/termopab');
		$data['text_call_me'] = $this->language->get('text_call_me');

		$data['menu_columns'] = $this->buildMenuColumns($language_id);
		$data['footer_menu'] = $this->buildFooterMenu($language_id);
	}

	/**
	 * Build menu columns from theme settings
	 * All columns use unified structure: type = information | category | custom
	 */
	private function buildMenuColumns(int $language_id): array {
		$image_base = $this->config->get('config_url') . 'image/';
		$columns = [1 => [], 2 => [], 3 => []];

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');
		$this->load->model('catalog/information');

		foreach ([1, 2, 3] as $col_num) {
			$key = 'theme_termopab_menu_column' . $col_num;
			$items = $this->config->get($key);
			if (is_string($items)) {
				$items = json_decode($items, true);
			}
			// Migrate old column1 format: [id, id, id]
			if ($col_num === 1 && is_array($items) && !empty($items) && is_numeric($items[0])) {
				$migrated = [];
				foreach ($items as $cid) {
					$cid = (int)$cid;
					if ($cid > 0) {
						$migrated[] = ['type' => 'category', 'category_id' => $cid];
					}
				}
				$items = $migrated;
			}
			if (!is_array($items)) continue;

			foreach ($items as $item) {
				$type = $item['type'] ?? 'information';

				if ($type === 'category' && !empty($item['category_id'])) {
					$category_id = (int)$item['category_id'];
					if ($category_id <= 0) continue;
					$category = $this->model_catalog_category->getCategory($category_id);
					if (empty($category)) continue;

					$children_data = [];
					$children = $this->model_catalog_category->getCategories($category_id);
					foreach ($children as $child) {
						$filter_data = [
							'filter_category_id'  => $child['category_id'],
							'filter_sub_category' => true
						];
						$children_data[] = [
							'name'  => $child['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : ''),
							'href'  => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_id . '_' . $child['category_id'])
						];
					}

					$image = '';
					if (!empty($category['image'])) {
						$image = $image_base . $category['image'];
					}

					$columns[$col_num][] = [
						'name'     => $category['name'],
						'href'     => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_id),
						'image'    => $image,
						'children' => $children_data,
					];
				} elseif ($type === 'information' && !empty($item['information_id'])) {
					$info = $this->model_catalog_information->getInformation((int)$item['information_id']);
					if (!empty($info)) {
						$columns[$col_num][] = [
							'name'  => $info['title'],
							'href'  => $this->url->link('information/information', 'language=' . $this->config->get('config_language') . '&information_id=' . $item['information_id']),
							'image' => '',
						];
					}
				} elseif ($type === 'custom') {
					$title = $item['title'][$language_id] ?? '';
					if ($title === '' && is_array($item['title'])) {
						$title = (string)reset($item['title']);
					}
					$href = $item['href'] ?? '#';
					if (is_array($href)) {
						$href = $href[$language_id] ?? (string)reset($href) ?: '#';
					}
					$href = (string)$href;
					if ($href !== '' && $href !== '#' && strpos($href, 'http') !== 0) {
						$base = rtrim($this->config->get('config_url'), '/');
						$href = $base . '/' . ltrim($href, '/');
					}
					$columns[$col_num][] = [
						'name'  => $title,
						'href'  => $href,
						'image' => '',
					];
				}
			}
		}

		return $columns;
	}

	/**
	 * Build flat footer menu - either from main menu (col1+col2+col3) or from footer-specific settings
	 */
	private function buildFooterMenu(int $language_id): array {
		$use_main = (int)$this->config->get('theme_termopab_footer_menu_use_main');
		if ($use_main) {
			$columns = $this->buildMenuColumns($language_id);
			$flat = [];
			foreach ([1, 2, 3] as $col) {
				foreach ($columns[$col] ?? [] as $item) {
					$flat[] = [
						'name' => $item['name'],
						'href' => $item['href'],
					];
					// Add children (categories) as flat items
					if (!empty($item['children'])) {
						foreach ($item['children'] as $child) {
							$flat[] = [
								'name' => $child['name'],
								'href' => $child['href'],
							];
						}
					}
				}
			}
			return $flat;
		}

		$items = $this->config->get('theme_termopab_footer_menu');
		if (is_string($items)) {
			$items = json_decode($items, true);
		}
		if (!is_array($items)) {
			return [];
		}

		$this->load->model('catalog/category');
		$this->load->model('catalog/information');

		$flat = [];
		foreach ($items as $item) {
			$type = $item['type'] ?? 'category';
			$entry = null;

			if ($type === 'category' && !empty($item['category_id'])) {
				$category_id = (int)$item['category_id'];
				$category = $this->model_catalog_category->getCategory($category_id);
				if (!empty($category)) {
					$entry = [
						'name' => $category['name'],
						'href' => $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $category_id),
					];
				}
			} elseif ($type === 'information' && !empty($item['information_id'])) {
				$info = $this->model_catalog_information->getInformation((int)$item['information_id']);
				if (!empty($info)) {
					$entry = [
						'name' => $info['title'],
						'href' => $this->url->link('information/information', 'language=' . $this->config->get('config_language') . '&information_id=' . $item['information_id']),
					];
				}
			} elseif ($type === 'custom') {
				$title = $item['title'][$language_id] ?? '';
				if ($title === '' && is_array($item['title'])) {
					$title = (string)reset($item['title']);
				}
				$href = $item['href'] ?? '#';
				if (is_array($href)) {
					$href = $href[$language_id] ?? (string)reset($href) ?: '#';
				}
				$href = (string)$href;
				if ($href !== '' && $href !== '#' && strpos($href, 'http') !== 0) {
					$base = rtrim($this->config->get('config_url'), '/');
					$href = $base . '/' . ltrim($href, '/');
				}
				$entry = ['name' => $title, 'href' => $href];
			}

			if ($entry && $entry['name'] !== '') {
				$flat[] = $entry;
			}
		}
		return $flat;
	}
}