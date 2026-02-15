<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Module;

class CatPromoSection extends \Opencart\System\Engine\Controller {

	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');

		$this->load->language('extension/termopab/module/cat_promo_section');

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
			'href' => $this->url->link('extension/termopab/module/cat_promo_section', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''))
		];

		$data['save'] = $this->url->link('extension/termopab/module/cat_promo_section.save', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''));
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$module_info = [];
		if ($module_id) {
			$this->load->model('setting/module');
			$module_info = $this->model_setting_module->getModule($module_id);
		}

		$data['name'] = $module_info['name'] ?? '';
		$data['status'] = $module_info['status'] ?? 1;
		$data['module_id'] = $module_id;
		$data['image'] = $module_info['image'] ?? '';
		$data['image_hover'] = $module_info['image_hover'] ?? '';
		$data['title'] = $module_info['title'] ?? [];
		$data['link_url'] = $module_info['link_url'] ?? [];
		$data['link_text'] = $module_info['link_text'] ?? [];
		$data['description'] = $module_info['description'] ?? [];
		$data['layout_type'] = $module_info['layout_type'] ?? '';

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('tool/image');
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		if ($data['image'] && is_file(DIR_IMAGE . html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8'))) {
			$data['thumb'] = $this->model_tool_image->resize($data['image'], 200, 120);
		} else {
			$data['thumb'] = $data['placeholder'];
		}

		if ($data['image_hover'] && is_file(DIR_IMAGE . html_entity_decode($data['image_hover'], ENT_QUOTES, 'UTF-8'))) {
			$data['thumb_hover'] = $this->model_tool_image->resize($data['image_hover'], 200, 120);
		} else {
			$data['thumb_hover'] = $data['placeholder'];
		}

		$this->document->addScript('view/javascript/ckeditor/ckeditor.js');
		$this->document->addScript('view/javascript/ckeditor/adapters/jquery.js');
		$this->load->language('default');
		$data['ckeditor'] = $this->language->get('ckeditor') ?: 'en';

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/module/cat_promo_section', $data));
	}

	public function save(): void {
		$this->load->language('extension/termopab/module/cat_promo_section');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/module/cat_promo_section')) {
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
			$link_url = [];
			foreach ($this->request->post['link_url'] ?? [] as $lang_id => $val) {
				$link_url[(int)$lang_id] = trim((string)$val);
			}
			$link_text = [];
			foreach ($this->request->post['link_text'] ?? [] as $lang_id => $val) {
				$link_text[(int)$lang_id] = trim((string)$val);
			}
			$description = [];
			foreach ($this->request->post['description'] ?? [] as $lang_id => $val) {
				$description[(int)$lang_id] = (string)$val;
			}

			$layout_type = trim((string)($this->request->post['layout_type'] ?? ''));
			if ($layout_type !== 'cat-promo--reverse') {
				$layout_type = '';
			}

			$post = [
				'name'         => $name,
				'image'        => trim($this->request->post['image'] ?? ''),
				'image_hover'  => trim($this->request->post['image_hover'] ?? ''),
				'title'        => $title,
				'link_url'     => $link_url,
				'link_text'    => $link_text,
				'description'  => $description,
				'layout_type'  => $layout_type,
				'status'       => (int)($this->request->post['status'] ?? 0),
			];

			$this->load->model('setting/module');

			$module_id = (int)($this->request->post['module_id'] ?? 0);
			if (!$module_id) {
				$json['module_id'] = $this->model_setting_module->addModule('termopab.cat_promo_section', $post);
			} else {
				$this->model_setting_module->editModule($module_id, $post);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
