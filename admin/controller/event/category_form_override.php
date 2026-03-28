<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Event;

/**
 * Event controller: override admin category form template.
 * Trigger: admin/view/catalog/category_form/before
 */
class CategoryFormOverride extends \Opencart\System\Engine\Controller {
	public function onBefore(string &$route, array &$data, string &$code = '', string &$output = ''): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');
		$route = 'extension/termopab/catalog/category_form';
	}
}
