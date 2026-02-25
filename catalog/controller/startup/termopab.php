<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Startup;

class Termopab extends \Opencart\System\Engine\Controller {
	public function index(): void {
		// Global URL link tweak: do not include language in URLs for the store default language.
		// This prevents default language prefix (e.g. /ua/) while keeping prefixes for other languages.
		$original_url = $this->registry->get('url');
		if (is_object($original_url)) {
			$config = $this->config;
			$this->registry->set('url', new class($original_url, $config) {
				private object $url;
				private object $config;
				private ?string $default_language = null;

				public function __construct(object $url, object $config) {
					$this->url = $url;
					$this->config = $config;
				}

				private function getDefaultLanguage(): string {
					if ($this->default_language !== null) {
						return $this->default_language;
					}

					$default = (string)($this->config->get('config_language_catalog') ?? '');
					if ($default === '') {
						$default = (string)($this->config->get('config_language') ?? '');
					}
					$this->default_language = $default;
					return $this->default_language;
				}

				public function link(string $route, $args = '', bool $secure = false): string {
					$default = $this->getDefaultLanguage();
					if ($default !== '' && $args) {
						if (is_array($args)) {
							if (isset($args['language']) && (string)$args['language'] === $default) {
								unset($args['language']);
							}
						} else {
							$args_str = (string)$args;
							parse_str(str_replace('&amp;', '&', $args_str), $query);
							if (isset($query['language']) && (string)$query['language'] === $default) {
								unset($query['language']);
								$args = http_build_query($query);
								$args = str_replace('%2F', '/', $args);
							}
						}
					}

					return $this->url->link($route, $args, $secure);
				}

				public function __call(string $name, array $arguments) {
					return $this->url->{$name}(...$arguments);
				}
			});
		}

		if ($this->config->get('theme_termopab_status')) {
			$this->addDesignPath();
			$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
			$this->event->register('view/*/before', new \Opencart\System\Engine\Action('extension/termopab/startup/termopab.event'));
			$this->event->register('extension/termopab/checkout/form_data', new \Opencart\System\Engine\Action('extension/termopab/startup/termopab.addModalCartFormData'));
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
			'product/product',
			'checkout/checkout',
		];
		if (in_array($route, $override)) {
			$route = 'extension/termopab/' . $route;
		}

		if (in_array($route, ['extension/termopab/common/header', 'extension/termopab/common/footer'])) {
			$this->addThemeData($data);
		}
	}

	/**
	 * Add modal cart / checkout form data to $data. Used by footer (modal) and by checkout page.
	 * Trigger: extension/termopab/checkout/form_data with [&$data].
	 */
	public function addModalCartFormData(array &$data): void {
		$this->load->language('extension/termopab/common/modal_cart');
		$data['modal_cart_title'] = $this->language->get('modal_cart_title');
		$data['modal_cart_label_name'] = $this->language->get('modal_cart_label_name');
		$data['modal_cart_placeholder_name'] = $this->language->get('modal_cart_placeholder_name');
		$data['modal_cart_label_phone'] = $this->language->get('modal_cart_label_phone');
		$data['modal_cart_placeholder_phone'] = $this->language->get('modal_cart_placeholder_phone');
		$data['modal_cart_label_email'] = $this->language->get('modal_cart_label_email');
		$data['modal_cart_placeholder_email'] = $this->language->get('modal_cart_placeholder_email');
		$data['modal_cart_label_delivery'] = $this->language->get('modal_cart_label_delivery');
		$data['modal_cart_placeholder_city'] = $this->language->get('modal_cart_placeholder_city');
		$data['modal_cart_placeholder_office'] = $this->language->get('modal_cart_placeholder_office');
		$data['modal_cart_label_payment'] = $this->language->get('modal_cart_label_payment');
		$data['modal_cart_payment_cashless'] = $this->language->get('modal_cart_payment_cashless');
		$data['modal_cart_payment_cash'] = $this->language->get('modal_cart_payment_cash');
		$data['modal_cart_button'] = $this->language->get('modal_cart_button');
		$data['modal_cart_agreement'] = $this->language->get('modal_cart_agreement');
		$data['modal_cart_error_required'] = $this->language->get('modal_cart_error_required');
		$data['modal_cart_error_email'] = $this->language->get('modal_cart_error_email');
		$data['modal_cart_error_agreement'] = $this->language->get('modal_cart_error_agreement');

		$this->load->model('extension/termopab/common/modal_cart');
		$data['modal_cart_payment_methods'] = $this->model_extension_termopab_common_modal_cart->getPaymentMethods();

		$address_data = $this->model_extension_termopab_common_modal_cart->getAddressFormData();
		$data['modal_cart_countries']      = $address_data['countries'];
		$data['modal_cart_zones']         = $address_data['zones'];
		$data['modal_cart_custom_fields'] = $address_data['custom_fields'];
		$data['modal_cart_country_id']    = $address_data['country_id'];

		$theme_payment = $this->config->get('theme_termopab_modal_payment_address');
		$data['modal_cart_payment_address_required'] = ($theme_payment !== null && $theme_payment !== '')
			? (bool)$theme_payment
			: (bool)$this->config->get('config_checkout_payment_address');

		$theme_shipping = $this->config->get('theme_termopab_modal_shipping_address');
		$data['modal_cart_has_shipping'] = ($theme_shipping !== null && $theme_shipping !== '')
			? (bool)$theme_shipping
			: $this->cart->hasShipping();

		if ($data['modal_cart_has_shipping']) {
			$data['modal_cart_shipping_methods'] = $this->model_extension_termopab_common_modal_cart->getShippingMethods();
			$data['modal_cart_label_shipping_method'] = $this->language->get('modal_cart_label_shipping_method');
		} else {
			$data['modal_cart_shipping_methods'] = [];
			$data['modal_cart_label_shipping_method'] = '';
		}

		$showField = function ($key) {
			$v = $this->config->get('theme_termopab_modal_field_' . $key);
			return ($v === '' || $v === null) ? true : in_array($v, [1, '1'], true);
		};
		$data['modal_cart_show_field_country']   = $showField('country');
		$data['modal_cart_show_field_zone']      = $showField('zone');
		$data['modal_cart_show_field_city']      = $showField('city');
		$data['modal_cart_show_field_address_1'] = $showField('address_1');
		$data['modal_cart_show_field_address_2'] = $showField('address_2');
		$data['modal_cart_show_field_company']   = $showField('company');
		$data['modal_cart_show_field_postcode']  = $showField('postcode');

		$allowed_order_keys = ['country', 'zone', 'city', 'address_1', 'address_2', 'company', 'postcode'];
		$order_raw = $this->config->get('theme_termopab_modal_address_field_order');
		$order = ['country', 'zone', 'city', 'address_1', 'address_2', 'company', 'postcode'];
		if (is_string($order_raw) && $order_raw !== '') {
			$parsed = array_values(array_intersect($allowed_order_keys, array_unique(array_map('trim', explode(',', $order_raw)))));
			if (!empty($parsed)) {
				$order = $parsed;
			}
		}
		$data['modal_cart_address_field_order'] = $order;

		$data['modal_cart_address_match_default'] = (bool)$this->config->get('theme_termopab_modal_address_match_default');

		$data['modal_cart_fill'] = [];
		if (isset($this->session->data['customer'])) {
			$c = $this->session->data['customer'];
			$data['modal_cart_fill']['firstname'] = $c['firstname'] ?? '';
			$data['modal_cart_fill']['lastname']  = $c['lastname'] ?? '';
			$data['modal_cart_fill']['email']     = $c['email'] ?? '';
			$data['modal_cart_fill']['telephone'] = $c['telephone'] ?? '';
		}
		if (isset($this->session->data['payment_address'])) {
			$p = $this->session->data['payment_address'];
			$data['modal_cart_fill']['payment_company']     = $p['company'] ?? '';
			$data['modal_cart_fill']['payment_address_1']   = $p['address_1'] ?? '';
			$data['modal_cart_fill']['payment_address_2']  = $p['address_2'] ?? '';
			$data['modal_cart_fill']['payment_city']       = $p['city'] ?? '';
			$data['modal_cart_fill']['payment_postcode']   = $p['postcode'] ?? '';
			$data['modal_cart_fill']['payment_country_id'] = (int)($p['country_id'] ?? 0);
			$data['modal_cart_fill']['payment_zone_id']     = (int)($p['zone_id'] ?? 0);
		}
		if (isset($this->session->data['shipping_address'])) {
			$s = $this->session->data['shipping_address'];
			$data['modal_cart_fill']['shipping_company']     = $s['company'] ?? '';
			$data['modal_cart_fill']['shipping_address_1']   = $s['address_1'] ?? '';
			$data['modal_cart_fill']['shipping_address_2']  = $s['address_2'] ?? '';
			$data['modal_cart_fill']['shipping_city']       = $s['city'] ?? '';
			$data['modal_cart_fill']['shipping_postcode']   = $s['postcode'] ?? '';
			$data['modal_cart_fill']['shipping_country_id'] = (int)($s['country_id'] ?? 0);
			$data['modal_cart_fill']['shipping_zone_id']    = (int)($s['zone_id'] ?? 0);
		}
		if (isset($this->session->data['payment_method']['code'])) {
			$data['modal_cart_fill']['payment_method'] = $this->session->data['payment_method']['code'];
		}
		if (isset($this->session->data['shipping_method']['code'])) {
			$data['modal_cart_fill']['shipping_method'] = $this->session->data['shipping_method']['code'];
		}
		$data['modal_cart_fill']['address_match'] = (!empty($this->session->data['shipping_address']) && !empty($this->session->data['payment_address'])
			&& ($this->session->data['shipping_address']['address_1'] ?? '') === ($this->session->data['payment_address']['address_1'] ?? '')
			&& ($this->session->data['shipping_address']['zone_id'] ?? '') === ($this->session->data['payment_address']['zone_id'] ?? '')) ? '1' : '';
		$data['modal_cart_fill']['agreement'] = !empty($this->session->data['agree']) ? '1' : '';

		$this->load->language('checkout/payment_address');
		$data['modal_cart_entry_firstname']   = $this->language->get('entry_firstname');
		$data['modal_cart_entry_lastname']    = $this->language->get('entry_lastname');
		$data['modal_cart_entry_company']     = $this->language->get('entry_company');
		$data['modal_cart_entry_address_1']   = $this->language->get('entry_address_1');
		$data['modal_cart_entry_address_2']   = $this->language->get('entry_address_2');
		$data['modal_cart_entry_city']       = $this->language->get('entry_city');
		$data['modal_cart_entry_postcode']   = $this->language->get('entry_postcode');
		$data['modal_cart_entry_country']    = $this->language->get('entry_country');
		$data['modal_cart_entry_zone']       = $this->language->get('entry_zone');
		$data['modal_cart_text_select']      = $this->language->get('text_select');
		$data['modal_cart_error_firstname']  = $this->language->get('error_firstname');
		$data['modal_cart_error_lastname']   = $this->language->get('error_lastname');
		$data['modal_cart_error_address_1']  = $this->language->get('error_address_1');
		$data['modal_cart_error_city']       = $this->language->get('error_city');
		$data['modal_cart_error_postcode']   = $this->language->get('error_postcode');
		$data['modal_cart_error_country']    = $this->language->get('error_country');
		$data['modal_cart_error_zone']       = $this->language->get('error_zone');
		$data['modal_cart_error_custom_field'] = $this->language->get('error_custom_field');
		$data['modal_cart_error_regex']      = $this->language->get('error_regex');
		$data['modal_cart_text_payment_address']  = $this->language->get('heading_title');
		$this->load->language('checkout/shipping_address');
		$data['modal_cart_text_shipping_address']  = $this->language->get('heading_title');
		$data['modal_cart_text_same_address'] = $this->language->get('modal_cart_text_same_address');

		$data['modal_cart_save_url']     = $this->url->link('extension/termopab/common/modal_cart.saveAddresses', 'language=' . $this->config->get('config_language'));
		$data['modal_cart_zones_url']    = $this->url->link('extension/termopab/common/modal_cart.zones', 'language=' . $this->config->get('config_language'));
		$data['modal_cart_products_url'] = $this->url->link('extension/termopab/common/modal_cart.getCartProducts', 'language=' . $this->config->get('config_language'));
		$data['modal_cart_remove_url']   = $this->url->link('common/cart.remove', 'language=' . $this->config->get('config_language'));
	}

	/**
	 * Add theme settings to view data for header/footer
	 */
	private function addThemeData(array &$data): void {
		$language_id = (int)$this->config->get('config_language_id');

		$route = (string)($this->request->get['route'] ?? 'common/home');
		$data['pageClass'] = $this->getPageClassFromRoute($route);

		$this->addModalCartFormData($data);

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

		$social_keys = ['instagram', 'whatsapp', 'telegram', 'facebook', 'youtube'];
		$all_social_links = [
			'instagram' => trim((string)$this->config->get('theme_termopab_social_instagram')),
			'whatsapp'  => trim((string)$this->config->get('theme_termopab_social_whatsapp')),
			'telegram'  => trim((string)$this->config->get('theme_termopab_social_telegram')),
			'facebook'  => trim((string)$this->config->get('theme_termopab_social_facebook')),
			'youtube'   => trim((string)$this->config->get('theme_termopab_social_youtube')),
		];
		$data['social_links'] = $all_social_links;

		$header_social_links = [];
		$footer_social_links = [];
		foreach ($social_keys as $key) {
			$url = $all_social_links[$key] ?? '';
			$url = $url !== '' ? $url : '#';
			// Show if checkbox not explicitly 0 (not set or 1 = show); allow empty URL to show icon with #
			$header_val = $this->config->get('theme_termopab_header_social_' . $key);
			if ($header_val !== 0 && $header_val !== '0') {
				$header_social_links[$key] = $url;
			}
			$footer_val = $this->config->get('theme_termopab_footer_social_' . $key);
			if ($footer_val !== 0 && $footer_val !== '0') {
				$footer_social_links[$key] = $url;
			}
		}
		$data['header_social_links'] = $header_social_links;
		$data['footer_social_links'] = $footer_social_links;

		$this->load->language('extension/termopab/theme/termopab');
		$data['text_call_me'] = $this->language->get('text_call_me');

		$data['menu_columns'] = $this->buildMenuColumns($language_id);
		$data['footer_menu'] = $this->buildFooterMenu($language_id);
		// Append Projects link (extension/termopab/project)
		$this->load->language('extension/termopab/project/list');
		$data['footer_menu'][] = [
			'name' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/project', 'language=' . $this->config->get('config_language')),
		];
		// Append Brewery reviews link (extension/termopab/brewery_review)
		$this->load->language('extension/termopab/brewery_review/list');
		$data['footer_menu'][] = [
			'name' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/brewery_review', 'language=' . $this->config->get('config_language')),
		];
	}

	/**
	 * Клас для body за типом сторінки (route).
	 */
	private function getPageClassFromRoute(string $route): string {
		$map = [
			'common/home'                         => 'home-page',
			'extension/termopab/common/about'     => 'about-us',
			'extension/termopab/project'          => 'project-page',
			'extension/termopab/brewery_review'   => 'testimonials-page',
			'information/contact'                 => 'contacts-page',
			'product/product'                     => 'product-page',
			'product/category'                    => 'category-page',
			'checkout/checkout'                   => 'checkout-page',
		];
		if (isset($map[$route])) {
			return $map[$route];
		}
		return str_replace(['/', '.'], ['-', '-'], $route);
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