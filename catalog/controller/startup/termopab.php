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

	public function event(string &$route, array &$args, mixed &$output): void {
		$override = ['common/header', 'common/footer', 'common/home'];
		if (in_array($route, $override)) {
			$route = 'extension/termopab/' . $route;
		}
	}
}