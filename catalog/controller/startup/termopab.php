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
	}
}