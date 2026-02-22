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
		$store_id = (int)$this->config->get('config_store_id');
		$path_parts = array_filter(explode('_', $path), 'strlen');
		$is_parent = count($path_parts) <= 1;

		$has_mobile = $this->db->query("SHOW COLUMNS FROM `" . DB_PREFIX . "category` LIKE 'hero_image_mobile'")->num_rows > 0;
		$sel = $has_mobile ? "`hero_image`, `hero_image_mobile`, `breadcrumb_background`" : "`hero_image`, `breadcrumb_background`";
		$row = $this->db->query("SELECT " . $sel . " FROM `" . DB_PREFIX . "category` WHERE `category_id` = '" . (int)$category_id . "'");
		if ($row->num_rows) {
			$hero_path = trim((string)($row->row['hero_image'] ?? ''));
			$data['hero_image'] = $hero_path ? rtrim((string)$this->config->get('config_url'), '/') . '/image/' . $hero_path : '';
			$hero_mobile_path = $has_mobile ? trim((string)($row->row['hero_image_mobile'] ?? '')) : '';
			$data['hero_image_mobile'] = $hero_mobile_path ? rtrim((string)$this->config->get('config_url'), '/') . '/image/' . $hero_mobile_path : '';
			$bc = $row->row['breadcrumb_background'] ?? 'black';
			$data['breadcrumb_background'] = in_array($bc, ['black', 'white'], true) ? $bc : 'black';
		} else {
			$data['hero_image'] = '';
			$data['hero_image_mobile'] = '';
			$data['breadcrumb_background'] = 'black';
		}

		$this->load->language('extension/termopab/theme/termopab');
		$this->load->language('default');
		$home_raw = $this->language->get('text_home');
		$data['text_home'] = trim(strip_tags($home_raw ?? '')) ?: 'Головна';
		$data['home_url'] = $this->url->link('common/home', 'language=' . $this->config->get('config_language'));

		$data['layout_type'] = $is_parent ? 'parent' : 'child';
		if ($is_parent) {
			$data['text_content_expand'] = $this->language->get('text_content_expand');
			$data['text_content_collapse'] = $this->language->get('text_content_collapse');
			$data['text_learn_more'] = $this->language->get('text_learn_more');
			$data['text_filter_all'] = $this->language->get('text_filter_all');
			$this->injectSlotModules($data, $category_id, $store_id, 'parent');
			$this->injectFilterDataForParent($data, $category_id);
			$this->rebuildProductsForParent($data, $category_id);
		} else {
			$this->injectSlotModules($data, $category_id, $store_id, 'child');
		}
		$route = 'extension/termopab/product/category';
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

	/**
	 * Inject filter data for parent layout: button-style filter (filter_groups, filter_category, filter_base_url).
	 */
	private function injectFilterDataForParent(array &$data, int $category_id): void {
		$url = '';
		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}
		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}
		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}
		$path = (string)($this->request->get['path'] ?? '');
		$data['filter_base_url'] = str_replace('&amp;', '&', $this->url->link('product/category', 'language=' . $this->config->get('config_language') . '&path=' . $path . $url));
		
		if (isset($this->request->get['filter']) && $this->request->get['filter'] !== '') {
			$data['filter_category'] = array_map('intval', array_filter(explode(',', $this->request->get['filter'])));
		} else {
			$data['filter_category'] = [];
		}

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');

		$data['filter_groups'] = [];
		$filter_groups = $this->model_catalog_category->getFilters($category_id);
		if ($filter_groups) {
			foreach ($filter_groups as $filter_group) {
				$children_data = [];
				foreach ($filter_group['filter'] as $filter) {
					$children_data[] = [
						'filter_id' => (int)$filter['filter_id'],
						'name'      => $filter['name'] . ($this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts([
							'filter_category_id' => $category_id,
							'filter_filter'      => $filter['filter_id'],
						]) . ')' : ''),
					];
				}
				$data['filter_groups'][] = [
					'filter_group_id' => (int)$filter_group['filter_group_id'],
					'name'            => $filter_group['name'],
					'filter'          => $children_data,
				];
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

	/**
	 * Rebuild $data['products'] for parent layout: raw items with description and first 2–3 attributes.
	 */
	private function rebuildProductsForParent(array &$data, int $category_id): void {
		$filter = (string)($this->request->get['filter'] ?? '');
		$sort = (string)($data['sort'] ?? 'p.sort_order');
		$order = (string)($data['order'] ?? 'ASC');
		$page = (int)($this->request->get['page'] ?? 1);
		$limit = (int)($data['limit'] ?? $this->config->get('config_pagination'));

		$url = '';
		if (isset($this->request->get['path'])) {
			$url .= '&path=' . $this->request->get['path'];
		}
		if ($filter !== '') {
			$url .= '&filter=' . $filter;
		}
		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}
		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}
		if (isset($this->request->get['limit'])) {
			$url .= '&limit=' . $this->request->get['limit'];
		}

		$this->load->model('catalog/product');
		$this->load->model('tool/image');

		$filter_data = [
			'filter_category_id'  => $category_id,
			'filter_sub_category' => false,
			'filter_filter'       => $filter,
			'sort'                => $sort,
			'order'               => $order,
			'start'               => ($page - 1) * $limit,
			'limit'               => $limit,
		];

		$results = $this->model_catalog_product->getProducts($filter_data);
		$desc_length = (int)$this->config->get('config_product_description_length') ?: 100;
		$max_attributes = 3;
		$img_width = (int)$this->config->get('config_image_product_width') ?: 228;
		$img_height = (int)$this->config->get('config_image_product_height') ?: 228;

		$data['products'] = [];

		foreach ($results as $result) {
			$short = isset($result['short_description']) ? trim(strip_tags(html_entity_decode($result['short_description'], ENT_QUOTES, 'UTF-8'))) : '';
	
			if ($short !== '') {
				$description = $short;
			} else {
				$description = trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')));
				$len = function_exists('mb_strlen') ? mb_strlen($description) : strlen($description);
				if ($len > $desc_length) {
					$description = (function_exists('mb_substr') ? mb_substr($description, 0, $desc_length) : substr($description, 0, $desc_length)) . '..';
				}
			}

			$image = (!empty($result['image']) && is_file(DIR_IMAGE . html_entity_decode($result['image'], ENT_QUOTES, 'UTF-8')))
				? $result['image']
				: 'placeholder.png';
			$thumb = $this->model_tool_image->resize($image, $img_width, $img_height);

			$params = [];
			$attribute_groups = $this->model_catalog_product->getAttributes((int)$result['product_id']);
			foreach ($attribute_groups as $group) {
				foreach ($group['attribute'] ?? [] as $attr) {
					$params[] = ['name' => $attr['name'], 'value' => $attr['text']];
					if (count($params) >= $max_attributes) {
						break 2;
					}
				}
			}

			$data['products'][] = [
				'product_id'  => (int)$result['product_id'],
				'name'        => $result['name'],
				'thumb'       => $thumb,
				'href'        => $this->url->link('product/product', 'language=' . $this->config->get('config_language') . '&product_id=' . $result['product_id'] . $url),
				'description' => $description,
				'params'      => $params,
			];
		}
	}
}
