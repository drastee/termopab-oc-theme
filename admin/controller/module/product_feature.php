<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Module;

class ProductFeature extends \Opencart\System\Engine\Controller {
	public function index(): void {
		// Ensure template path is registered (for local dev when extension may not be in extension_install)
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');

		$this->load->language('extension/termopab/module/product_feature');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
		];

		$module_id = isset($this->request->get['module_id']) ? (int)$this->request->get['module_id'] : 0;

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/module/product_feature', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''))
		];

		$data['save'] = $this->url->link('extension/termopab/module/product_feature.save', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''));
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$module_info = [];
		if ($module_id) {
			$this->load->model('setting/module');
			$module_info = $this->model_setting_module->getModule($module_id);
		}

		$data['name'] = $module_info['name'] ?? '';
		$data['width'] = (int)($module_info['width'] ?? 0) ?: (int)$this->config->get('config_image_product_width') ?: 300;
		$data['height'] = (int)($module_info['height'] ?? 0) ?: (int)$this->config->get('config_image_product_height') ?: 300;
		$data['status'] = $module_info['status'] ?? 1;
		$data['module_id'] = $module_id;

		// Items: [{ product_id, attribute: [ids], description: { lang_id: text } }]
		$items = $module_info['items'] ?? [];
		$data['items'] = [];

		$this->load->model('catalog/product');
		$this->load->model('catalog/attribute');
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$config_lang_id = (int)$this->config->get('config_language_id');
		if (!$config_lang_id && $data['languages']) {
			$first = reset($data['languages']);
			$config_lang_id = (int)($first['language_id'] ?? 1);
		}
		$data['config_language_id'] = $config_lang_id ?: 1;

		$entry_description = $this->language->get('entry_description');
		$row_index = 0;

		foreach ($items as $item) {
			$product_id = (int)($item['product_id'] ?? 0);
			if (!$product_id) {
				continue;
			}
			$product_info = $this->model_catalog_product->getProduct($product_id);
			if (!$product_info) {
				continue;
			}

			$attribute_ids = $item['attribute'] ?? [];
			$product_attrs = $this->model_catalog_product->getAttributes($product_id);
			$attribute_options = [];
			foreach ($product_attrs as $pa) {
				$attr_info = $this->model_catalog_attribute->getAttribute($pa['attribute_id']);
				if ($attr_info) {
					$attribute_options[] = [
						'attribute_id' => $pa['attribute_id'],
						'name'         => $attr_info['name'],
						'selected'     => in_array((int)$pa['attribute_id'], array_map('intval', $attribute_ids)),
					];
				}
			}

			$descriptions = $item['description'] ?? [];
			$description_html = $this->buildDescriptionHtml($data['languages'], $descriptions, $row_index, $entry_description);

			$data['items'][] = [
				'row'               => $row_index,
				'product_id'        => $product_id,
				'product_name'      => $product_info['name'],
				'attribute_options' => $attribute_options,
				'description_html'  => $description_html,
			];
			$row_index++;
		}

		$data['next_row'] = $row_index;

		// Template for new rows (used in <template> with __ROW__ placeholder)
		$data['description_template_html'] = $this->buildDescriptionHtml($data['languages'], [], '__ROW__', $entry_description);

		$data['user_token'] = $this->session->data['user_token'];
		$data['attributes_url'] = 'index.php?route=extension/termopab/module/product_feature.attributes&user_token=' . $this->session->data['user_token'];
		$data['product_autocomplete_url'] = 'index.php?route=catalog/product.autocomplete&user_token=' . $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/module/product_feature', $data));
	}

	/**
	 * Build description block HTML (row layout with flags).
	 *
	 * @param array  $langs
	 * @param array  $descriptions
	 * @param int|string $row_index
	 * @param string $entry_description
	 * @return string
	 */
	protected function buildDescriptionHtml(array $langs, array $descriptions, $row_index, string $entry_description): string {
		if (empty($langs)) {
			$langs = [['language_id' => 1, 'name' => 'Default', 'image' => '']];
		}
		$n = count($langs);
		$col_class = 'col-md-' . ($n <= 4 ? (int)(12 / $n) : 2);
		$html = '<div class="row g-2 mb-2">';
		foreach ($langs as $lang) {
			$lid = (int)$lang['language_id'];
			$val = $descriptions[$lid] ?? '';
			$flag = !empty($lang['image']) ? '<img src="' . htmlspecialchars($lang['image']) . '" alt="" title="' . htmlspecialchars($lang['name']) . '" class="me-1" style="width:16px;height:11px;vertical-align:middle"/>' : '';
			$html .= '<div class="' . $col_class . '">';
			$html .= '<label class="form-label small">' . $flag . htmlspecialchars($lang['name']) . '</label>';
			$html .= '<textarea name="item[' . $row_index . '][description][' . $lid . ']" class="form-control" rows="3" placeholder="' . htmlspecialchars($entry_description) . '">' . htmlspecialchars($val) . '</textarea>';
			$html .= '</div>';
		}
		$html .= '</div>';
		return $html;
	}

	public function attributes(): void {
		$this->load->language('extension/termopab/module/product_feature');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/module/product_feature')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$product_id = (int)($this->request->get['product_id'] ?? 0);

		if (!$json && $product_id) {
			$this->load->model('catalog/product');
			$this->load->model('catalog/attribute');

			$product_attributes = $this->model_catalog_product->getAttributes($product_id);
			$json['attributes'] = [];

			foreach ($product_attributes as $pa) {
				$attr_info = $this->model_catalog_attribute->getAttribute($pa['attribute_id']);
				if ($attr_info) {
					$json['attributes'][] = [
						'attribute_id' => $pa['attribute_id'],
						'name'         => $attr_info['name']
					];
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function save(): void {
		$this->load->language('extension/termopab/module/product_feature');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/module/product_feature')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$name = $this->request->post['name'] ?? '';
		if (oc_strlen($name) < 3 || oc_strlen($name) > 64) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (!$json) {
			$items = [];
			$item_keys = array_keys($this->request->post['item'] ?? []);
			$item_keys = array_filter($item_keys, 'is_numeric');
			$item_keys = array_values($item_keys);

			foreach ($item_keys as $key) {
				$item = $this->request->post['item'][$key] ?? [];
				$product_id = (int)($item['product_id'] ?? 0);
				if (!$product_id) {
					continue;
				}
				$attribute = $item['attribute'] ?? [];
				if (!is_array($attribute)) {
					$attribute = [];
				}
				$description = $item['description'] ?? [];
				if (!is_array($description)) {
					$description = [];
				}

				$items[] = [
					'product_id'  => $product_id,
					'attribute'   => array_map('intval', $attribute),
					'description' => $description,
				];
			}

			$this->load->model('setting/module');

			$post = [
				'name'   => $name,
				'width'  => (int)($this->request->post['width'] ?? 0) ?: 300,
				'height' => (int)($this->request->post['height'] ?? 0) ?: 300,
				'items'  => $items,
				'status' => (int)($this->request->post['status'] ?? 0),
			];

			$module_id = (int)($this->request->post['module_id'] ?? 0);
			if (!$module_id) {
				$json['module_id'] = $this->model_setting_module->addModule('termopab.product_feature', $post);
			} else {
				$this->model_setting_module->editModule($module_id, $post);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
