<?php
namespace Opencart\Catalog\Model\Extension\Termopab\Common;

/**
 * Model for modal cart data (payment methods with optional form HTML from payment extensions).
 * Used by theme when rendering footer/modal so payment logic stays out of startup.
 */
class ModalCart extends \Opencart\System\Engine\Model {

	/**
	 * Get payment methods for modal cart (enabled in admin). Uses store default country/zone so geo-zones apply.
	 * Includes each method's index() HTML (e.g. bank transfer instructions) when available.
	 *
	 * @return array<int, array{code: string, name: string, html: string}>
	 */
	public function getPaymentMethods(): array {
		$payment_address = [
			'country_id' => (int)$this->config->get('config_country_id'),
			'zone_id'    => (int)$this->config->get('config_zone_id'),
			'address_id' => 0,
		];
		$this->load->model('checkout/payment_method');
		$methods = $this->model_checkout_payment_method->getMethods($payment_address);
		$list = [];
		foreach ($methods as $payment_code => $method) {
			if (empty($method['option']) || !is_array($method['option'])) {
				continue;
			}
			foreach ($method['option'] as $option) {
				if (!empty($option['code']) && isset($option['name'])) {
					$list[] = [
						'code' => $option['code'],
						'name' => $option['name'],
						'html' => $this->getPaymentMethodFormHtml($payment_code),
					];
				}
			}
		}
		return $list;
	}

	/**
	 * Load payment extension index() view (instructions, form, etc.) for modal. Returns empty string on failure.
	 *
	 * @param string $payment_code e.g. bank_transfer
	 * @return string
	 */
	public function getPaymentMethodFormHtml(string $payment_code): string {
		try {
			$this->load->model('setting/extension');
			$extension_info = $this->model_setting_extension->getExtensionByCode('payment', $payment_code);
			if (!$extension_info || empty($extension_info['extension']) || empty($extension_info['code'])) {
				return '';
			}
			$output = $this->load->controller('extension/' . $extension_info['extension'] . '/payment/' . $extension_info['code']);
			return is_string($output) ? $output : '';
		} catch (\Throwable $e) {
			return '';
		}
	}

	/**
	 * Get address form data for modal cart: countries, zones (for default country), custom fields (location address).
	 * Used to render payment/shipping address blocks compatible with OpenCart checkout.
	 *
	 * @return array{countries: array, zones: array, custom_fields: array, country_id: int}
	 */
	public function getAddressFormData(): array {
		$this->load->model('localisation/country');
		$this->load->model('localisation/zone');
		$this->load->model('account/custom_field');

		$country_id = (int)$this->config->get('config_country_id');
		$customer_group_id = $this->customer->isLogged()
			? (int)$this->customer->getGroupId()
			: (int)$this->config->get('config_customer_group_id');

		$custom_fields = [];
		foreach ($this->model_account_custom_field->getCustomFields($customer_group_id) as $custom_field) {
			if (isset($custom_field['location']) && $custom_field['location'] === 'address') {
				$custom_fields[] = $custom_field;
			}
		}

		return [
			'countries'     => $this->model_localisation_country->getCountries(),
			'zones'         => $this->model_localisation_zone->getZonesByCountryId($country_id),
			'custom_fields' => $custom_fields,
			'country_id'    => $country_id,
		];
	}
}
