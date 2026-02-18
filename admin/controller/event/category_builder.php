<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Event;

/**
 * Category Page Builder — per-category unique sections (top, bottom).
 * Saves to tp_category_content; modules loaded via glob (same as Design > Layouts).
 */
class CategoryBuilder extends \Opencart\System\Engine\Controller {

	/**
	 * Hook: admin/view/catalog/category_form/before
	 */
	public function onCategoryFormBefore(string &$route, array &$data, string &$code = '', string &$output = ''): void {
		$data['category_builder_available_modules'] = $this->getAvailableModules();
		$data['category_builder_saved'] = $this->getSavedContent((int)($data['category_id'] ?? 0));
	}

	public function onAddCategoryAfter(string &$route, array &$args, &$output): void {
		if (!is_numeric($output) || (int)$output <= 0) {
			return;
		}
		$this->saveCategoryContent((int)$output, $args[0] ?? [], 0);
	}

	public function onEditCategoryAfter(string &$route, array &$args, &$output): void {
		if (count($args) < 2) {
			return;
		}
		$category_id = (int)$args[0];
		$data = $args[1] ?? [];
		if ($category_id <= 0) {
			return;
		}
		$this->saveCategoryContent($category_id, $data, 0);
	}

	private function getAvailableModules(): array {
		$list = [];
		$results = glob(DIR_EXTENSION . '*/admin/controller/module/*.php');
		if (!$results) {
			return $list;
		}
		$this->load->model('setting/module');
		foreach ($results as $result) {
			$path = substr($result, strlen(DIR_EXTENSION));
			$extension = substr($path, 0, strpos($path, '/'));
			$code = basename($result, '.php');
			$modules = $this->model_setting_module->getModulesByCode($extension . '.' . $code);
			foreach ($modules as $module) {
				$list[] = [
					'name' => strip_tags($module['name'] ?? ''),
					'code' => $extension . '.' . $code . '.' . ($module['module_id'] ?? 0),
				];
			}
		}
		return $list;
	}

	private function getSavedContent(int $category_id): array {
		$out = ['parent' => ['top' => [], 'bottom' => []], 'child' => ['top' => [], 'bottom' => []]];
		if ($category_id <= 0) {
			return $out;
		}
		$table = DB_PREFIX . 'category_content';
		$query = $this->db->query("SELECT `layout_type`, `position`, `code`, `sort_order` FROM `" . $table . "` WHERE `category_id` = '" . (int)$category_id . "' AND `store_id` = 0 AND `position` IN ('top','bottom') ORDER BY `layout_type`, `position`, `sort_order`");
		foreach ($query->rows as $row) {
			$type = $row['layout_type'];
			$pos = $row['position'];
			if (isset($out[$type][$pos])) {
				$out[$type][$pos][] = $row['code'];
			}
		}
		return $out;
	}

	private function saveCategoryContent(int $category_id, array $data, int $store_id): void {
		$table = DB_PREFIX . 'category_content';
		$this->db->query("DELETE FROM `" . $table . "` WHERE `category_id` = '" . (int)$category_id . "' AND `store_id` = '" . (int)$store_id . "'");

		foreach (['parent', 'child'] as $layout_type) {
			foreach (['top', 'bottom'] as $position) {
				$key = 'category_content_' . $layout_type . '_' . $position;
				$arr = $data[$key] ?? [];
				if (!is_array($arr)) {
					continue;
				}
				foreach (array_values($arr) as $sort_order => $code) {
					$code = trim((string)$code);
					if ($code === '') {
						continue;
					}
					$this->db->query("INSERT INTO `" . $table . "` SET
						`category_id` = '" . (int)$category_id . "',
						`store_id` = '" . (int)$store_id . "',
						`layout_type` = '" . $this->db->escape($layout_type) . "',
						`position` = '" . $this->db->escape($position) . "',
						`code` = '" . $this->db->escape($code) . "',
						`sort_order` = '" . (int)$sort_order . "'");
				}
			}
		}
	}
}
