<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Module;

class OurTeam extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');

		$this->load->language('extension/termopab/module/our_team');

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
			'href' => $this->url->link('extension/termopab/module/our_team', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''))
		];

		$data['save'] = $this->url->link('extension/termopab/module/our_team.save', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''));
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
		$data['founder_name'] = $module_info['founder_name'] ?? [];
		$data['founder_role'] = $module_info['founder_role'] ?? [];
		$data['text'] = $module_info['text'] ?? [];
		$data['button_text'] = $module_info['button_text'] ?? [];
		$data['button_url'] = $module_info['button_url'] ?? [];
		$data['owner_photo'] = $module_info['owner_photo'] ?? '';
		$data['extra_image'] = $module_info['extra_image'] ?? '';
		if ($data['extra_image'] === '' && !empty($module_info['extra_images']) && is_array($module_info['extra_images'])) {
			$first = reset($module_info['extra_images']);
			$data['extra_image'] = is_string($first) ? trim($first) : trim((string)($first['image'] ?? ''));
		}

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['user_token'] = $this->session->data['user_token'];

		if (!is_array($data['button_url'])) {
			$single = trim((string)$data['button_url']);
			$data['button_url'] = [];
			foreach ($data['languages'] as $lang) {
				$data['button_url'][(int)$lang['language_id']] = $single;
			}
			if (empty($data['button_url']) && $single !== '') {
				$data['button_url'][1] = $single;
			}
		}

		$this->load->model('tool/image');
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		if ($data['owner_photo'] && is_file(DIR_IMAGE . html_entity_decode($data['owner_photo'], ENT_QUOTES, 'UTF-8'))) {
			$data['owner_thumb'] = $this->model_tool_image->resize($data['owner_photo'], 100, 100);
		} else {
			$data['owner_thumb'] = $data['placeholder'];
		}
		if ($data['extra_image'] && is_file(DIR_IMAGE . html_entity_decode($data['extra_image'], ENT_QUOTES, 'UTF-8'))) {
			$data['extra_image_thumb'] = $this->model_tool_image->resize($data['extra_image'], 100, 100);
		} else {
			$data['extra_image_thumb'] = $data['placeholder'];
		}

		$this->document->addScript('view/javascript/ckeditor/ckeditor.js');
		$this->document->addScript('view/javascript/ckeditor/adapters/jquery.js');
		$this->load->language('default');
		$data['ckeditor'] = $this->language->get('ckeditor') ?: 'en';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/module/our_team', $data));
	}

	public function save(): void {
		$this->load->language('extension/termopab/module/our_team');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/module/our_team')) {
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
			$founder_name = [];
			foreach ($this->request->post['founder_name'] ?? [] as $lang_id => $val) {
				$founder_name[(int)$lang_id] = trim((string)$val);
			}
			$founder_role = [];
			foreach ($this->request->post['founder_role'] ?? [] as $lang_id => $val) {
				$founder_role[(int)$lang_id] = trim((string)$val);
			}
			$text = [];
			foreach ($this->request->post['text'] ?? [] as $lang_id => $val) {
				$raw = (string)$val;
				// Якщо прийшло з entity-encoded тегами (наприклад через AJAX) — декодуємо
				$text[(int)$lang_id] = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			}
			$button_text = [];
			foreach ($this->request->post['button_text'] ?? [] as $lang_id => $val) {
				$button_text[(int)$lang_id] = trim((string)$val);
			}
			$button_url = [];
			foreach ($this->request->post['button_url'] ?? [] as $lang_id => $val) {
				$button_url[(int)$lang_id] = trim((string)$val);
			}
			$owner_photo = trim((string)($this->request->post['owner_photo'] ?? ''));
			$extra_image = trim((string)($this->request->post['extra_image'] ?? ''));

			$post = [
				'name'          => $name,
				'title'         => $title,
				'founder_name'  => $founder_name,
				'founder_role'  => $founder_role,
				'text'          => $text,
				'button_text'   => $button_text,
				'button_url'    => $button_url,
				'owner_photo'   => $owner_photo,
				'extra_image'   => $extra_image,
				'status'        => (int)($this->request->post['status'] ?? 0),
			];

			$this->load->model('setting/module');
			$module_id = (int)($this->request->post['module_id'] ?? 0);
			if (!$module_id) {
				$json['module_id'] = $this->model_setting_module->addModule('termopab.our_team', $post);
			} else {
				$this->model_setting_module->editModule($module_id, $post);
			}
			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
