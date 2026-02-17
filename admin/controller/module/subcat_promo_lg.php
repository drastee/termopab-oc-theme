<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Module;

class SubcatPromoLg extends \Opencart\System\Engine\Controller {

	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');
		$this->load->language('extension/termopab/module/subcat_promo_lg');
		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')];
		$module_id = isset($this->request->get['module_id']) ? (int)$this->request->get['module_id'] : 0;
		$data['breadcrumbs'][] = ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/termopab/module/subcat_promo_lg', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''))];

		$data['save'] = $this->url->link('extension/termopab/module/subcat_promo_lg.save', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''));
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$module_info = [];
		if ($module_id) {
			$this->load->model('setting/module');
			$module_info = $this->model_setting_module->getModule($module_id);
		}

		$data['name'] = $module_info['name'] ?? '';
		$data['status'] = $module_info['status'] ?? 1;
		$data['module_id'] = $module_id;
		$data['text_start'] = $module_info['text_start'] ?? [];
		$data['text_end'] = $module_info['text_end'] ?? [];
		$data['image'] = $module_info['image'] ?? '';

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('tool/image');
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		$data['thumb'] = $data['placeholder'];
		if (!empty($data['image']) && is_file(DIR_IMAGE . html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8'))) {
			$data['thumb'] = $this->model_tool_image->resize($data['image'], 200, 120);
		}

		$this->document->addScript('view/javascript/ckeditor/ckeditor.js');
		$this->document->addScript('view/javascript/ckeditor/adapters/jquery.js');
		$this->load->language('default');
		$data['ckeditor'] = $this->language->get('ckeditor') ?: 'en';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/termopab/module/subcat_promo_lg', $data));
	}

	public function save(): void {
		$this->load->language('extension/termopab/module/subcat_promo_lg');
		$json = [];
		if (!$this->user->hasPermission('modify', 'extension/termopab/module/subcat_promo_lg')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}
		$name = $this->request->post['name'] ?? '';
		if (oc_strlen($name) < 3 || oc_strlen($name) > 64) {
			$json['error']['name'] = $this->language->get('error_name');
		}
		if (!$json) {
			$text_start = [];
			foreach ($this->request->post['text_start'] ?? [] as $lang_id => $val) {
				$text_start[(int)$lang_id] = (string)$val;
			}
			$text_end = [];
			foreach ($this->request->post['text_end'] ?? [] as $lang_id => $val) {
				$text_end[(int)$lang_id] = (string)$val;
			}
			$post = [
				'name'        => $name,
				'text_start'  => $text_start,
				'text_end'    => $text_end,
				'image'       => trim($this->request->post['image'] ?? ''),
				'image_hover' => trim($this->request->post['image_hover'] ?? ''),
				'status'      => (int)($this->request->post['status'] ?? 0),
			];
			$this->load->model('setting/module');
			$module_id = (int)($this->request->post['module_id'] ?? 0);
			if (!$module_id) {
				$json['module_id'] = $this->model_setting_module->addModule('termopab.subcat_promo_lg', $post);
			} else {
				$this->model_setting_module->editModule($module_id, $post);
			}
			$json['success'] = $this->language->get('text_success');
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
