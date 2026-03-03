<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Common;

/**
 * Modal cart: zones by country (AJAX), save addresses + customer session (guest or logged-in).
 * Sets session payment_address, shipping_address, customer (guest), payment_method, agree
 * for compatibility with OpenCart checkout/confirm.
 */
class ModalCart extends \Opencart\System\Engine\Controller {

	/**
	 * Return cart products as JSON for modal (thumb, name, price_text, total_text, quantity, cart_id).
	 *
	 * @return void
	 */
	public function getCartProducts(): void {
		$this->load->model('checkout/cart');
		$this->load->model('tool/image');

		$products = $this->model_checkout_cart->getProducts();
		$price_status = $this->customer->isLogged() || !$this->config->get('config_customer_price');

		$list = [];
		foreach ($products as $product) {
			$image = !empty($product['image']) && is_file(DIR_IMAGE . html_entity_decode($product['image'], ENT_QUOTES, 'UTF-8'))
				? $product['image'] : 'placeholder.png';
			$thumb = $this->model_tool_image->resize($image, (int)$this->config->get('config_image_cart_width') ?: 80, (int)$this->config->get('config_image_cart_height') ?: 80);

			$list[] = [
				'cart_id'    => (int)$product['cart_id'],
				'name'       => $product['name'],
				'thumb'      => $thumb,
				'price_text' => $price_status ? $product['price_text'] : '',
				'total_text' => $price_status ? $product['total_text'] : '',
				'quantity'   => (int)$product['quantity'],
			];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(['products' => $list]));
	}

	/**
	 * Return zones for a country (JSON). Used by modal when country select changes.
	 *
	 * @return void
	 */
	public function zones(): void {
		$this->load->model('localisation/zone');
		$country_id = isset($this->request->get['country_id']) ? (int)$this->request->get['country_id'] : 0;
		$zones = $this->model_localisation_zone->getZonesByCountryId($country_id);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode(['zones' => $zones]));
	}

	/**
	 * Validate and save payment/shipping addresses (and guest customer) to session.
	 * POST: firstname, lastname, email, telephone, [payment_*], [shipping_*], address_match, payment_method, agreement.
	 *
	 * @return void
	 */
	public function saveAddresses(): void {
		$this->load->language('checkout/payment_address');
		$this->load->language('checkout/shipping_address');

		$json = [];
		$required = [
			'firstname'  => '',
			'lastname'   => '',
			'email'      => '',
			'telephone'  => '',
			'payment_company'       => '',
			'payment_address_1'     => '',
			'payment_address_2'    => '',
			'payment_city'         => '',
			'payment_postcode'     => '',
			'payment_country_id'   => 0,
			'payment_zone_id'      => 0,
			'payment_custom_field' => [],
			'shipping_firstname'   => '',
			'shipping_lastname'    => '',
			'shipping_company'     => '',
			'shipping_address_1'   => '',
			'shipping_address_2'   => '',
			'shipping_city'        => '',
			'shipping_postcode'    => '',
			'shipping_country_id'  => 0,
			'shipping_zone_id'     => 0,
			'shipping_custom_field'=> [],
			'address_match'        => 0,
			'payment_method'       => '',
			'shipping_method'      => '',
			'agreement'            => 0,
		];
		$post = $this->request->post + $required;

		// Cart / stock
		if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout')) || !$this->cart->hasMinimum()) {
			$json['redirect'] = $this->url->link('checkout/cart', 'language=' . $this->config->get('config_language'), true);
		}

		$theme_payment = $this->config->get('theme_termopab_modal_payment_address');
		$payment_required = ($theme_payment !== null && $theme_payment !== '')
			? (bool)$theme_payment
			: (bool)$this->config->get('config_checkout_payment_address');

		$theme_shipping = $this->config->get('theme_termopab_modal_shipping_address');
		$has_shipping = ($theme_shipping !== null && $theme_shipping !== '')
			? (bool)$theme_shipping
			: $this->cart->hasShipping();

		if (!$json) {
			// Contact
			if (!oc_validate_length($post['firstname'], 1, 32)) {
				$json['error']['firstname'] = $this->language->get('error_firstname');
			}
			if (!oc_validate_length($post['lastname'], 1, 32)) {
				$json['error']['lastname'] = $this->language->get('error_lastname');
			}
			$this->load->language('checkout/register');
			$email = trim((string)$post['email']);
			if (!oc_validate_length($email, 1, 96) || !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) {
				$json['error']['email'] = $this->language->get('error_email') ?: 'Invalid email';
			}
			if (!oc_validate_length($post['telephone'], 3, 32)) {
				$json['error']['telephone'] = $this->language->get('error_telephone') ?: 'Telephone required';
			}

			// Payment address
			if ($payment_required) {
				$this->validateAddressFields($post, 'payment_', $json);
			}

			// Shipping address (if not same as payment)
			if ($has_shipping && !$post['address_match']) {
				$this->validateAddressFields($post, 'shipping_', $json);
			}

			// Payment method
			if (!oc_validate_length($post['payment_method'], 1, 128)) {
				$json['error']['payment_method'] = $this->language->get('error_payment_method') ?: 'Select payment method';
			}

			// Agreement (checkout terms) — only if config requires it
			$checkout_id = (int)$this->config->get('config_checkout_id');
			if ($checkout_id) {
				$agree = isset($post['agreement']) && (is_string($post['agreement']) ? $post['agreement'] !== '' : (bool)$post['agreement']);
				if (!$agree) {
					$this->load->model('catalog/information');
					$info = $this->model_catalog_information->getInformation($checkout_id);
					$json['error']['agreement'] = $info ? sprintf($this->language->get('error_agree') ?: 'You must agree to %s', $info['title']) : 'Agreement required';
				}
			}
		}

		if ($json) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		// Build customer session (guest or keep existing)
		$customer_group_id = $this->customer->isLogged()
			? (int)$this->customer->getGroupId()
			: (int)$this->config->get('config_customer_group_id');

		if (!$this->customer->isLogged() || !isset($this->session->data['customer'])) {
			$this->session->data['customer'] = [
				'customer_id'       => $this->customer->isLogged() ? (int)$this->customer->getId() : 0,
				'customer_group_id' => $customer_group_id,
				'firstname'         => $post['firstname'],
				'lastname'          => $post['lastname'],
				'email'             => $post['email'],
				'telephone'         => $post['telephone'],
				'custom_field'      => isset($post['custom_field']) && is_array($post['custom_field']) ? $post['custom_field'] : [],
			];
		}

		// Payment address session
		if ($payment_required) {
			$this->session->data['payment_address'] = $this->buildAddressSession(
				$post['firstname'],
				$post['lastname'],
				$post['payment_company'],
				$post['payment_address_1'],
				$post['payment_address_2'],
				$post['payment_city'],
				$post['payment_postcode'],
				(int)$post['payment_country_id'],
				(int)$post['payment_zone_id'],
				is_array($post['payment_custom_field']) ? $post['payment_custom_field'] : []
			);
		} else {
			unset($this->session->data['payment_address']);
		}

		// Shipping address session — заповнюємо завжди, коли в кошику є доставка (для checkout/confirm)
		$cart_has_shipping = $this->cart->hasShipping();
		if ($cart_has_shipping) {
			if ($has_shipping) {
				// Тема показує блок доставки: з форми або «та сама, що й оплата»
				if (!empty($post['address_match']) && $payment_required && isset($this->session->data['payment_address'])) {
					$shipping = $this->session->data['payment_address'];
					$shipping['address_id'] = 0;
					$this->session->data['shipping_address'] = $shipping;
				} else {
					$firstname = $post['firstname'];
					$lastname  = $post['lastname'];
					$this->session->data['shipping_address'] = $this->buildAddressSession(
						$firstname,
						$lastname,
						$post['shipping_company'],
						$post['shipping_address_1'],
						$post['shipping_address_2'],
						$post['shipping_city'],
						$post['shipping_postcode'],
						(int)$post['shipping_country_id'],
						(int)$post['shipping_zone_id'],
						is_array($post['shipping_custom_field']) ? $post['shipping_custom_field'] : []
					);
				}
			} else {
				// Тема не показує блок доставки, але кошик з доставкою — підставляємо адресу оплати або мінімум
				if ($payment_required && isset($this->session->data['payment_address'])) {
					$shipping = $this->session->data['payment_address'];
					$shipping['address_id'] = 0;
					$this->session->data['shipping_address'] = $shipping;
				} else {
					$this->session->data['shipping_address'] = $this->buildAddressSession(
						$post['firstname'],
						$post['lastname'],
						'',
						'',
						'',
						'',
						'',
						(int)$this->config->get('config_country_id'),
						(int)$this->config->get('config_zone_id') ?: 0,
						[]
					);
				}
			}
		} else {
			unset($this->session->data['shipping_address']);
		}

		$this->session->data['payment_method'] = [
			'code' => $post['payment_method'],
			'name' => $post['payment_method'],
			'title'=> $post['payment_method'],
		];
		if ($checkout_id && !empty($post['agreement'])) {
			$this->session->data['agree'] = true;
		}

		if ($cart_has_shipping) {
			$this->load->model('checkout/shipping_method');
			$shipping_methods = $this->model_checkout_shipping_method->getMethods($this->session->data['shipping_address']);
			$this->session->data['shipping_methods'] = $shipping_methods;

			$found = false;
			if ($has_shipping) {
				$selected_code = trim((string)$post['shipping_method']);
				if ($selected_code !== '') {
					foreach ($shipping_methods as $method) {
						if (empty($method['quote']) || !is_array($method['quote'])) {
							continue;
						}
						foreach ($method['quote'] as $quote) {
							if (($quote['code'] ?? '') === $selected_code) {
								$this->session->data['shipping_method'] = $quote;
								$found = true;
								break 2;
							}
						}
					}
				}
			}
			if (!$found) {
				// З форми не вибрано або тема не показує доставку — беремо перший доступний спосіб
				foreach ($shipping_methods as $method) {
					if (!empty($method['quote']) && is_array($method['quote'])) {
						$first = reset($method['quote']);
						if (!empty($first)) {
							$this->session->data['shipping_method'] = $first;
							$found = true;
							break;
						}
					}
				}
			}
			if (!$found) {
				$this->load->language('checkout/shipping_method');
				$json['error']['shipping_method'] = $this->language->get('error_shipping_method');
			}
		} else {
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
		}

		if (!empty($json['error'])) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		// Перевірки як у checkout/confirm — якщо щось не так, одразу повертаємо причину
		$confirm_reason = $this->getConfirmFailureReason();
		if ($confirm_reason !== null) {
			$json['error']['confirm'] = $confirm_reason;
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		// Завжди створюємо замовлення тут (з доставкою і без) — редирект на success або помилка в модалку
		$this->load->controller('checkout/confirm');
		if (!empty($this->session->data['order_id'])) {
			$order_id = (int)$this->session->data['order_id'];
			$this->load->model('checkout/order');
			$payment_code = $this->session->data['payment_method']['code'] ?? '';
			$code = (strpos($payment_code, '.') !== false) ? explode('.', $payment_code)[0] : $payment_code;
			$status_id = (int)$this->config->get('payment_' . $code . '_order_status_id');
			if (!$status_id) {
				$status_id = (int)$this->config->get('config_order_status_id');
			}
			if ($status_id) {
				$this->model_checkout_order->addHistory($order_id, $status_id);
			}

			$json['success'] = true;
			$json['order_id'] = $order_id;

			// Success page normally clears cart/session; do the essential cleanup here to avoid redirect.
			try {
				$this->cart->clear();
			} catch (\Throwable $e) {
				// ignore
			}
			unset($this->session->data['shipping_method']);
			unset($this->session->data['shipping_methods']);
			unset($this->session->data['payment_method']);
			unset($this->session->data['payment_methods']);
			unset($this->session->data['guest']);
			unset($this->session->data['comment']);
			unset($this->session->data['coupon']);
			unset($this->session->data['reward']);
			unset($this->session->data['voucher']);
			unset($this->session->data['vouchers']);
			unset($this->session->data['order_id']);
			unset($this->session->data['agree']);
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		// Confirm не створив замовлення — повертаємо помилку в модалку, без редиректу
		$this->load->language('extension/termopab/common/modal_cart');
		$json['error']['confirm'] = $this->language->get('error_confirm_failed') ?: 'Не вдалося створити замовлення. Спробуйте ще раз або оформте на сторінці кошика.';
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Validate address fields (payment_ or shipping_ prefix).
	 *
	 * @param array $post
	 * @param string $prefix
	 * @param array $json
	 */
	/**
	 * Чи показується поле адреси в модалці (відповідає налаштуванню теми).
	 */
	private function isAddressFieldVisible(string $key): bool {
		$v = $this->config->get('theme_termopab_modal_field_' . $key);
		return ($v === '' || $v === null) ? true : in_array($v, [1, '1'], true);
	}

	private function validateAddressFields(array $post, string $prefix, array &$json): void {
		if ($this->isAddressFieldVisible('address_1') && !oc_validate_length($post[$prefix . 'address_1'], 3, 128)) {
			$json['error'][$prefix . 'address_1'] = $this->language->get('error_address_1');
		}
		if ($this->isAddressFieldVisible('city') && !oc_validate_length($post[$prefix . 'city'], 2, 128)) {
			$json['error'][$prefix . 'city'] = $this->language->get('error_city');
		}

		$country_id = (int)$post[$prefix . 'country_id'];
		$this->load->model('localisation/country');
		$country_info = $country_id ? $this->model_localisation_country->getCountry($country_id) : null;

		if ($this->isAddressFieldVisible('country')) {
			if (!$country_info) {
				$json['error'][$prefix . 'country_id'] = $this->language->get('error_country');
			} elseif ($this->isAddressFieldVisible('postcode') && $country_info['postcode_required'] && !oc_validate_length($post[$prefix . 'postcode'], 2, 10)) {
				$json['error'][$prefix . 'postcode'] = $this->language->get('error_postcode');
			}
		}

		if ($this->isAddressFieldVisible('zone')) {
			$this->load->model('localisation/zone');
			$zone_total = $country_id ? $this->model_localisation_zone->getTotalZonesByCountryId($country_id) : 0;
			if ($zone_total && empty($post[$prefix . 'zone_id'])) {
				$json['error'][$prefix . 'zone_id'] = $this->language->get('error_zone');
			}
		}

		$customer_group_id = $this->customer->isLogged() ? (int)$this->customer->getGroupId() : (int)$this->config->get('config_customer_group_id');
		$this->load->model('account/custom_field');
		$custom_fields = $this->model_account_custom_field->getCustomFields($customer_group_id);
		$cf_post = $post[$prefix . 'custom_field'] ?? [];
		foreach ($custom_fields as $custom_field) {
			if ($custom_field['location'] !== 'address') {
				continue;
			}
			$value = $cf_post[$custom_field['custom_field_id']] ?? null;
			if ($custom_field['required'] && ($value === '' || $value === null)) {
				$json['error'][$prefix . 'custom_field_' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_custom_field'), $custom_field['name']);
			} elseif ($custom_field['type'] === 'text' && !empty($custom_field['validation']) && $value !== '' && $value !== null && !oc_validate_regex($value, $custom_field['validation'])) {
				$json['error'][$prefix . 'custom_field_' . $custom_field['custom_field_id']] = sprintf($this->language->get('error_regex'), $custom_field['name']);
			}
		}
	}

	/**
	 * Build address array for session (same structure as account/address getAddress + checkout).
	 *
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $company
	 * @param string $address_1
	 * @param string $address_2
	 * @param string $city
	 * @param string $postcode
	 * @param int $country_id
	 * @param int $zone_id
	 * @param array $custom_field
	 * @return array
	 */
	private function buildAddressSession(string $firstname, string $lastname, string $company, string $address_1, string $address_2, string $city, string $postcode, int $country_id, int $zone_id, array $custom_field): array {
		$this->load->model('localisation/country');
		$this->load->model('localisation/zone');
		$this->load->model('localisation/address_format');

		$country_info = $this->model_localisation_country->getCountry($country_id);
		if ($country_info) {
			$country = $country_info['name'];
			$iso_code_2 = $country_info['iso_code_2'];
			$iso_code_3 = $country_info['iso_code_3'];
			$address_format_id = (int)$country_info['address_format_id'];
		} else {
			$country = '';
			$iso_code_2 = '';
			$iso_code_3 = '';
			$address_format_id = 0;
		}

		$address_format = '';
		if ($address_format_id) {
			$fmt = $this->model_localisation_address_format->getAddressFormat($address_format_id);
			if ($fmt) {
				$address_format = $fmt['address_format'];
			}
		}

		$zone_info = $this->model_localisation_zone->getZone($zone_id);
		$zone = $zone_info ? $zone_info['name'] : '';
		$zone_code = $zone_info ? $zone_info['code'] : '';

		return [
			'address_id'     => 0,
			'firstname'      => $firstname,
			'lastname'       => $lastname,
			'company'        => $company,
			'address_1'      => $address_1,
			'address_2'      => $address_2,
			'city'           => $city,
			'postcode'       => $postcode,
			'zone_id'        => $zone_id,
			'zone'           => $zone,
			'zone_code'      => $zone_code,
			'country_id'     => $country_id,
			'country'        => $country,
			'iso_code_2'     => $iso_code_2,
			'iso_code_3'     => $iso_code_3,
			'address_format' => $address_format,
			'custom_field'   => $custom_field,
		];
	}

	/**
	 * Ті самі перевірки, що в checkout/confirm — повертає текст помилки або null.
	 * Допомагає зрозуміти, чому замовлення не створилось.
	 *
	 * @return string|null
	 */
	private function getConfirmFailureReason(): ?string {
		$this->load->language('extension/termopab/common/modal_cart');

		if (!isset($this->session->data['customer'])) {
			return $this->language->get('error_confirm_no_customer');
		}
		if (!$this->cart->hasProducts() || (!$this->cart->hasStock() && !$this->config->get('config_stock_checkout')) || !$this->cart->hasMinimum()) {
			return $this->language->get('error_confirm_cart');
		}
		if ($this->cart->hasShipping()) {
			if (!isset($this->session->data['shipping_address']['address_id'])) {
				return $this->language->get('error_confirm_shipping_address');
			}
			if (!isset($this->session->data['shipping_method'])) {
				return $this->language->get('error_confirm_shipping_method');
			}
		}
		if ($this->config->get('config_checkout_payment_address') && !isset($this->session->data['payment_address'])) {
			return $this->language->get('error_confirm_payment_address');
		}
		if (!isset($this->session->data['payment_method'])) {
			return $this->language->get('error_confirm_payment_method');
		}
		if ($this->config->get('config_checkout_id') && empty($this->session->data['agree'])) {
			return $this->language->get('error_confirm_agree');
		}
		return null;
	}
}
