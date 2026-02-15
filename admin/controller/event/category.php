<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Event;

/**
 * Event controller: Conditional Category Layout — load/save custom fields
 * (landing_video_title, tech_spec_link) for category layouts 15 and 16.
 */
class Category extends \Opencart\System\Engine\Controller {

	/**
	 * Hook: admin/view/catalog/category_form/before
	 * Load landing_video_title and tech_spec_link into $data for Twig.
	 */
	public function onCategoryFormBefore(string &$route, array &$data, string &$code = '', string &$output = ''): void {
		$data['termopab_layout_parent_id'] = (int)$this->config->get('theme_termopab_layout_parent_id') ?: 19;
		$data['termopab_layout_child_id'] = (int)$this->config->get('theme_termopab_layout_child_id') ?: 20;
		$category_id = (int)($data['category_id'] ?? 0);
		if ($category_id <= 0) {
			$data['landing_video_title'] = '';
			$data['tech_spec_link'] = '';
			return;
		}

		$query = $this->db->query("SELECT `landing_video_title`, `tech_spec_link` FROM `" . DB_PREFIX . "category` WHERE `category_id` = '" . (int)$category_id . "'");
		if ($query->num_rows) {
			$data['landing_video_title'] = $query->row['landing_video_title'] ?? '';
			$data['tech_spec_link'] = $query->row['tech_spec_link'] ?? '';
		} else {
			$data['landing_video_title'] = '';
			$data['tech_spec_link'] = '';
		}
	}

	/**
	 * Hook: admin/model/catalog/category.addCategory/after
	 * Save custom fields. $args = [$data], $output = new category_id.
	 */
	public function onAddCategoryAfter(string &$route, array &$args, &$output): void {
		if (!is_numeric($output) || (int)$output <= 0) {
			return;
		}
		$category_id = (int)$output;
		$data = $args[0] ?? [];
		$this->saveCustomFields($category_id, $data);
	}

	/**
	 * Hook: admin/model/catalog/category.editCategory/after
	 * Save custom fields. $args = [$category_id, $data], $output = null.
	 */
	public function onEditCategoryAfter(string &$route, array &$args, &$output): void {
		if (count($args) < 2) {
			return;
		}
		$category_id = (int)$args[0];
		$data = $args[1] ?? [];
		if ($category_id <= 0) {
			return;
		}
		$this->saveCustomFields($category_id, $data);
	}

	private function saveCustomFields(int $category_id, array $data): void {
		$landing_video_title = isset($data['landing_video_title']) ? $this->db->escape((string)$data['landing_video_title']) : '';
		$tech_spec_link = isset($data['tech_spec_link']) ? $this->db->escape((string)$data['tech_spec_link']) : '';

		// Check if columns exist (they may not if install not run yet)
		$cols = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "category` LIKE 'landing_video_title'");
		if ($cols->num_rows === 0) {
			return;
		}

		$this->db->query("UPDATE `" . DB_PREFIX . "category` SET
			`landing_video_title` = '" . $landing_video_title . "',
			`tech_spec_link` = '" . $tech_spec_link . "'
			WHERE `category_id` = '" . (int)$category_id . "'");
	}
}
