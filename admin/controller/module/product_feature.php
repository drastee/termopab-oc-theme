<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Module;

class ProductFeature extends \Opencart\System\Engine\Controller {
	public function index(): void {
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
		$data['status'] = $module_info['status'] ?? 1;
		$data['module_id'] = $module_id;

		$slides_raw = $module_info['slides'] ?? [];
		$data['slides'] = [];

		$this->load->model('tool/image');
		$placeholder = $this->model_tool_image->resize('no_image.png', 100, 100);
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		foreach ($slides_raw as $s) {
			$img = trim($s['image'] ?? '');
			$img_hover = trim($s['image_hover'] ?? '');
			$s['image_thumb'] = ($img && is_file(DIR_IMAGE . html_entity_decode($img, ENT_QUOTES, 'UTF-8')))
				? $this->model_tool_image->resize($img, 200, 200) : $placeholder;
			$s['image_hover_thumb'] = ($img_hover && is_file(DIR_IMAGE . html_entity_decode($img_hover, ENT_QUOTES, 'UTF-8')))
				? $this->model_tool_image->resize($img_hover, 200, 200) : $placeholder;
			$data['slides'][] = $s;
		}

		$data['placeholder'] = $placeholder;
		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/module/product_feature', $data));
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
			$slides = [];
			$slide_keys = array_keys($this->request->post['slide'] ?? []);
			$slide_keys = array_filter($slide_keys, 'is_numeric');
			sort($slide_keys, SORT_NUMERIC);

			foreach ($slide_keys as $idx) {
				$slide = $this->request->post['slide'][$idx] ?? [];
				$slide_title = [];
				$slide_text_before = [];
				$slide_text_after = [];
				foreach ($slide['title'] ?? [] as $lid => $v) {
					$slide_title[(int)$lid] = trim((string)$v);
				}
				foreach ($slide['text_before'] ?? [] as $lid => $v) {
					$slide_text_before[(int)$lid] = trim((string)$v);
				}
				foreach ($slide['text_after'] ?? [] as $lid => $v) {
					$slide_text_after[(int)$lid] = trim((string)$v);
				}

				$slides[] = [
					'title'       => $slide_title,
					'text_before' => $slide_text_before,
					'text_after'  => $slide_text_after,
					'image'       => trim($slide['image'] ?? ''),
					'image_hover' => trim($slide['image_hover'] ?? ''),
				];
			}

			$post = [
				'name'   => $name,
				'slides' => $slides,
				'status' => (int)($this->request->post['status'] ?? 0),
			];

			$this->load->model('setting/module');

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
