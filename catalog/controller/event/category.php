<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Event;

/**
 * Event: switch category template by layout_id + per-category unique modules.
 * Trigger: catalog/view/product/category/before
 */
class Category extends \Opencart\System\Engine\Controller {

	private const POSITIONS = ['top', 'bottom'];

	public function onCategoryViewBefore(string &$route, array &$data, string &$code = '', string &$output = ''): void {
		$path = (string)($this->request->get['path'] ?? '');
		$parts = array_filter(explode('_', $path), 'strlen');
		$category_id = $parts ? (int)array_pop($parts) : 0;
		if ($category_id <= 0) {
			return;
		}

		$this->load->model('catalog/category');
		$layout_id = (int)$this->model_catalog_category->getLayoutId($category_id);
		$layout_parent_id = (int)$this->config->get('theme_termopab_layout_parent_id') ?: 19;
		$layout_child_id = (int)$this->config->get('theme_termopab_layout_child_id') ?: 20;
		$store_id = (int)$this->config->get('config_store_id');

		if ($layout_id === $layout_parent_id) {
			$route = 'extension/termopab/product/category_parent';
			$row = $this->db->query("SELECT `landing_video_title` FROM `" . DB_PREFIX . "category` WHERE `category_id` = '" . (int)$category_id . "'");
			$data['landing_video_title'] = $row->num_rows ? ($row->row['landing_video_title'] ?? '') : '';
			$this->load->language('extension/termopab/theme/termopab');
			$data['text_content_expand'] = $this->language->get('text_content_expand');
			$data['text_content_collapse'] = $this->language->get('text_content_collapse');
			$this->injectSlotModules($data, $category_id, $store_id, 'parent');
		} elseif ($layout_id === $layout_child_id) {
			$route = 'extension/termopab/product/category_child';
			$row = $this->db->query("SELECT `tech_spec_link` FROM `" . DB_PREFIX . "category` WHERE `category_id` = '" . (int)$category_id . "'");
			$data['tech_spec_link'] = $row->num_rows ? ($row->row['tech_spec_link'] ?? '') : '';
			$this->injectSlotModules($data, $category_id, $store_id, 'child');
		}
	}

	private function injectSlotModules(array &$data, int $category_id, int $store_id, string $layout_type): void {
		$this->load->model('setting/module');
		$table = DB_PREFIX . 'category_content';
		$query = $this->db->query("SELECT `position`, `code`, `sort_order` FROM `" . $table . "` WHERE `category_id` = '" . (int)$category_id . "' AND `store_id` = '" . (int)$store_id . "' AND `layout_type` = '" . $this->db->escape($layout_type) . "' AND `position` IN ('top','bottom') ORDER BY `position`, `sort_order`");

		$by_position = ['top' => [], 'bottom' => []];
		foreach ($query->rows as $row) {
			if (isset($by_position[$row['position']])) {
				$by_position[$row['position']][] = $row['code'];
			}
		}

		foreach (self::POSITIONS as $pos) {
			$key = $layout_type . '_' . $pos;
			$data[$key] = '';
			foreach ($by_position[$pos] ?? [] as $module_code) {
				$html = $this->renderModule($module_code);
				if ($html !== '') {
					$data[$key] .= $html;
				}
			}
		}
	}

	private function renderModule(string $code): string {
		$part = explode('.', $code);
		if (count($part) < 3) {
			return '';
		}
		$this->load->model('setting/module');
		$setting_info = $this->model_setting_module->getModule((int)$part[2]);
		if (!$setting_info || empty($setting_info['status'])) {
			return '';
		}
		$output = $this->load->controller('extension/' . $part[0] . '/module/' . $part[1], $setting_info);
		return $output instanceof \Opencart\System\Engine\Action ? '' : (string)$output;
	}
}
