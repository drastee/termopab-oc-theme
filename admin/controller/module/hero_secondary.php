<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Module;

class HeroSecondary extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');

		$this->load->language('extension/termopab/module/hero_secondary');

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
			'href' => $this->url->link('extension/termopab/module/hero_secondary', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''))
		];

		$data['save'] = $this->url->link('extension/termopab/module/hero_secondary.save', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''));
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$module_info = [];
		if ($module_id) {
			$this->load->model('setting/module');
			$module_info = $this->model_setting_module->getModule($module_id);
		}

		$data['name'] = $module_info['name'] ?? '';
		$data['status'] = $module_info['status'] ?? 1;
		$data['module_id'] = $module_id;
		$data['image_desktop'] = $module_info['image_desktop'] ?? '';
		$data['image_mobile'] = $module_info['image_mobile'] ?? '';
		$data['title'] = $module_info['title'] ?? [];
		$data['subtitle'] = $module_info['subtitle'] ?? [];
		$data['height_mode'] = $module_info['height_mode'] ?? 'default';
		$data['custom_class'] = $module_info['custom_class'] ?? '';

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('tool/image');
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		if ($data['image_desktop'] && is_file(DIR_IMAGE . html_entity_decode($data['image_desktop'], ENT_QUOTES, 'UTF-8'))) {
			$data['thumb_desktop'] = $this->model_tool_image->resize($data['image_desktop'], 200, 120);
		} else {
			$data['thumb_desktop'] = $data['placeholder'];
		}

		if ($data['image_mobile'] && is_file(DIR_IMAGE . html_entity_decode($data['image_mobile'], ENT_QUOTES, 'UTF-8'))) {
			$data['thumb_mobile'] = $this->model_tool_image->resize($data['image_mobile'], 200, 120);
		} else {
			$data['thumb_mobile'] = $data['placeholder'];
		}

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/module/hero_secondary', $data));
	}

	public function save(): void {
		$this->load->language('extension/termopab/module/hero_secondary');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/module/hero_secondary')) {
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

			$post = [
				'name'           => $name,
				'image_desktop'  => trim($this->request->post['image_desktop'] ?? ''),
				'image_mobile'   => trim($this->request->post['image_mobile'] ?? ''),
				'title'          => $title,
				'subtitle'       => $subtitle,
				'height_mode'    => (string)($this->request->post['height_mode'] ?? 'default'),
				'custom_class'   => trim($this->request->post['custom_class'] ?? ''),
				'status'         => (int)($this->request->post['status'] ?? 0),
			];

			$this->load->model('setting/module');

			$module_id = (int)($this->request->post['module_id'] ?? 0);
			if (!$module_id) {
				$json['module_id'] = $this->model_setting_module->addModule('termopab.hero_secondary', $post);
			} else {
				$this->model_setting_module->editModule($module_id, $post);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
