<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Module;

class CtaThinCap extends \Opencart\System\Engine\Controller {

	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');
		$this->load->language('extension/termopab/module/cta_thin_cap');
		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_home'), 'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])];
		$data['breadcrumbs'][] = ['text' => $this->language->get('text_extension'), 'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')];
		$module_id = isset($this->request->get['module_id']) ? (int)$this->request->get['module_id'] : 0;
		$data['breadcrumbs'][] = ['text' => $this->language->get('heading_title'), 'href' => $this->url->link('extension/termopab/module/cta_thin_cap', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''))];

		$data['save'] = $this->url->link('extension/termopab/module/cta_thin_cap.save', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''));
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
		$data['subtitle'] = $module_info['subtitle'] ?? [];
		if (!is_array($data['title'])) {
			$data['title'] = $data['title'] !== '' ? [($this->config->get('config_language_id') ?: 1) => $data['title']] : [];
		}
		if (!is_array($data['subtitle'])) {
			$data['subtitle'] = $data['subtitle'] !== '' ? [($this->config->get('config_language_id') ?: 1) => $data['subtitle']] : [];
		}

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$social_keys = ['instagram', 'whatsapp', 'telegram', 'facebook', 'youtube'];
		$data['social_keys'] = $social_keys;
		$data['social_visible'] = [];
		$data['social_labels'] = [];
		foreach ($social_keys as $key) {
			$data['social_visible'][$key] = isset($module_info['social_visible'][$key]) ? (int)$module_info['social_visible'][$key] : 1;
			$data['social_labels'][$key] = $this->language->get('entry_social_' . $key);
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/termopab/module/cta_thin_cap', $data));
	}

	public function save(): void {
		$this->load->language('extension/termopab/module/cta_thin_cap');
		$json = [];
		if (!$this->user->hasPermission('modify', 'extension/termopab/module/cta_thin_cap')) {
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
			$subtitle = [];
			foreach ($this->request->post['subtitle'] ?? [] as $lang_id => $val) {
				$subtitle[(int)$lang_id] = trim((string)$val);
			}
			$social_visible = [];
			foreach (['instagram', 'whatsapp', 'telegram', 'facebook', 'youtube'] as $key) {
				$social_visible[$key] = isset($this->request->post['social_visible'][$key]) && $this->request->post['social_visible'][$key] ? 1 : 0;
			}
			$post = [
				'name'           => $name,
				'title'          => $title,
				'subtitle'       => $subtitle,
				'social_visible' => $social_visible,
				'status'         => (int)($this->request->post['status'] ?? 0),
			];
			$this->load->model('setting/module');
			$module_id = (int)($this->request->post['module_id'] ?? 0);
			if (!$module_id) {
				$json['module_id'] = $this->model_setting_module->addModule('termopab.cta_thin_cap', $post);
			} else {
				$this->model_setting_module->editModule($module_id, $post);
			}
			$json['success'] = $this->language->get('text_success');
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
