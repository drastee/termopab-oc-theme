<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Module;

class Assortment extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');

		$this->load->language('extension/termopab/module/assortment');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addScript('view/javascript/ckeditor/ckeditor.js');
		$this->document->addScript('view/javascript/ckeditor/adapters/jquery.js');

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
			'href' => $this->url->link('extension/termopab/module/assortment', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''))
		];

		$data['save'] = $this->url->link('extension/termopab/module/assortment.save', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''));
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$module_info = [];
		if ($module_id) {
			$this->load->model('setting/module');
			$module_info = $this->model_setting_module->getModule($module_id);
		}

		$data['name'] = $module_info['name'] ?? '';
		$data['status'] = $module_info['status'] ?? 1;
		$data['module_id'] = $module_id;
		$data['title'] = $module_info['title'] ?? [];
		$slides_raw = $module_info['slides'] ?? [];
		$data['slides'] = [];

		$this->load->model('tool/image');
		$placeholder = $this->model_tool_image->resize('no_image.png', 100, 100);
		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();
		foreach ($slides_raw as $s) {
			// Normalize link: old format was string, new format is array per lang
			$link_raw = $s['link'] ?? '';
			if (!is_array($link_raw)) {
				$val = trim((string)$link_raw);
				$s['link'] = array_fill_keys(array_column($languages, 'language_id'), $val);
			}
			$img = trim($s['image'] ?? '');
			$img_hover = trim($s['image_hover'] ?? '');
			$s['image_thumb'] = ($img && is_file(DIR_IMAGE . html_entity_decode($img, ENT_QUOTES, 'UTF-8')))
				? $this->model_tool_image->resize($img, 200, 200) : $placeholder;
			$s['image_hover_thumb'] = ($img_hover && is_file(DIR_IMAGE . html_entity_decode($img_hover, ENT_QUOTES, 'UTF-8')))
				? $this->model_tool_image->resize($img_hover, 200, 200) : $placeholder;
			$data['slides'][] = $s;
		}

		$data['languages'] = $languages;

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		$this->load->language('default');
		$data['ckeditor'] = $this->language->get('ckeditor') ?: 'en';

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/module/assortment', $data));
	}

	public function save(): void {
		$this->load->language('extension/termopab/module/assortment');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/module/assortment')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$name = $this->request->post['name'] ?? '';
		if (oc_strlen($name) < 3 || oc_strlen($name) > 64) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (!$json) {
			$title = [];
			foreach ($this->request->post['title'] ?? [] as $lang_id => $val) {
				$title[(int)$lang_id] = trim((string)$val);
			}

			$slides = [];
			$slide_keys = array_keys($this->request->post['slide'] ?? []);
			$slide_keys = array_filter($slide_keys, 'is_numeric');
			sort($slide_keys, SORT_NUMERIC);

			foreach ($slide_keys as $idx) {
				$slide = $this->request->post['slide'][$idx] ?? [];
				$slide_title = [];
				$slide_button_text = [];
				$slide_description = [];
				foreach ($slide['title'] ?? [] as $lid => $v) {
					$slide_title[(int)$lid] = trim((string)$v);
				}
				foreach ($slide['button_text'] ?? [] as $lid => $v) {
					$slide_button_text[(int)$lid] = trim((string)$v);
				}
				// Read description from raw $_POST — Request::clean() applies htmlspecialchars
				// which would corrupt CKEditor HTML; we need raw HTML for proper rendering
				$desc_raw = $_POST['slide'][$idx]['description'] ?? [];
				foreach ($desc_raw as $lid => $v) {
					$slide_description[(int)$lid] = trim((string)$v);
				}
				$slide_link = [];
				foreach ($slide['link'] ?? [] as $lid => $v) {
					$slide_link[(int)$lid] = trim((string)$v);
				}

				$slides[] = [
					'title'       => $slide_title,
					'button_text' => $slide_button_text,
					'link'        => $slide_link,
					'description' => $slide_description,
					'image'       => trim($slide['image'] ?? ''),
					'image_hover' => trim($slide['image_hover'] ?? ''),
				];
			}

			$post = [
				'name'    => $name,
				'title'   => $title,
				'slides'  => $slides,
				'status'  => (int)($this->request->post['status'] ?? 0),
			];

			$this->load->model('setting/module');

			$module_id = (int)($this->request->post['module_id'] ?? 0);
			if (!$module_id) {
				$json['module_id'] = $this->model_setting_module->addModule('termopab.assortment', $post);
			} else {
				$this->model_setting_module->editModule($module_id, $post);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
