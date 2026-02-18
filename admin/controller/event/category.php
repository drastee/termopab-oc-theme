<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Event;

/**
 * Event controller: load/save category custom fields
 * (hero_image, breadcrumb_background) for category form.
 */
class Category extends \Opencart\System\Engine\Controller {

	/**
	 * Hook: admin/view/catalog/category_form/before
	 */
	public function onCategoryFormBefore(string &$route, array &$data, string &$code = '', string &$output = ''): void {
		$category_id = (int)($data['category_id'] ?? 0);

		$data['hero_image'] = '';
		$data['hero_image_mobile'] = '';
		$data['breadcrumb_background'] = 'black';

		if ($category_id > 0) {
			$cols = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "category` LIKE 'hero_image'");
			if ($cols->num_rows > 0) {
				$cols_mobile = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "category` LIKE 'hero_image_mobile'");
				$sel = $cols_mobile->num_rows > 0 ? "`hero_image`, `hero_image_mobile`, `breadcrumb_background`" : "`hero_image`, `breadcrumb_background`";
				$query = $this->db->query("SELECT " . $sel . " FROM `" . DB_PREFIX . "category` WHERE `category_id` = '" . (int)$category_id . "'");
				if ($query->num_rows) {
					$data['hero_image'] = $query->row['hero_image'] ?? '';
					$data['hero_image_mobile'] = $cols_mobile->num_rows > 0 ? ($query->row['hero_image_mobile'] ?? '') : '';
					$bc = $query->row['breadcrumb_background'] ?? 'black';
					$data['breadcrumb_background'] = in_array($bc, ['black', 'white'], true) ? $bc : 'black';
				}
			}
		}

		$this->load->model('tool/image');
		$data['hero_placeholder'] = $this->model_tool_image->resize('no_image.png', (int)$this->config->get('config_image_default_width'), (int)$this->config->get('config_image_default_height'));
		if (!empty($data['hero_image']) && is_file(DIR_IMAGE . html_entity_decode($data['hero_image'], ENT_QUOTES, 'UTF-8'))) {
			$data['hero_thumb'] = $this->model_tool_image->resize($data['hero_image'], (int)$this->config->get('config_image_default_width'), (int)$this->config->get('config_image_default_height'));
		} else {
			$data['hero_thumb'] = $data['hero_placeholder'];
		}
		if (!empty($data['hero_image_mobile']) && is_file(DIR_IMAGE . html_entity_decode($data['hero_image_mobile'], ENT_QUOTES, 'UTF-8'))) {
			$data['hero_thumb_mobile'] = $this->model_tool_image->resize($data['hero_image_mobile'], (int)$this->config->get('config_image_default_width'), (int)$this->config->get('config_image_default_height'));
		} else {
			$data['hero_thumb_mobile'] = $data['hero_placeholder'];
		}
	}

	/**
	 * Hook: admin/model/catalog/category.addCategory/after
	 */
	public function onAddCategoryAfter(string &$route, array &$args, &$output): void {
		if (!is_numeric($output) || (int)$output <= 0) {
			return;
		}
		$this->saveCustomFields((int)$output, $args[0] ?? []);
	}

	/**
	 * Hook: admin/model/catalog/category.editCategory/after
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
		$cols = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "category` LIKE 'hero_image'");
		if ($cols->num_rows === 0) {
			return;
		}

		$hero_image = isset($data['hero_image']) ? $this->db->escape(trim((string)$data['hero_image'])) : '';
		$hero_image_mobile = isset($data['hero_image_mobile']) ? $this->db->escape(trim((string)$data['hero_image_mobile'])) : '';
		$bc = isset($data['breadcrumb_background']) ? (string)$data['breadcrumb_background'] : 'black';
		$breadcrumb_background = in_array($bc, ['black', 'white'], true) ? "'" . $this->db->escape($bc) . "'" : "'black'";

		$cols_mobile = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "category` LIKE 'hero_image_mobile'");
		$set_mobile = $cols_mobile->num_rows > 0 ? "`hero_image_mobile` = '" . $hero_image_mobile . "', " : '';

		$this->db->query("UPDATE `" . DB_PREFIX . "category` SET
			`hero_image` = '" . $hero_image . "',
			" . $set_mobile . "`breadcrumb_background` = " . $breadcrumb_background . "
			WHERE `category_id` = '" . (int)$category_id . "'");
	}
}
