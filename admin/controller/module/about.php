<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Module;

class About extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');

		$this->load->language('extension/termopab/module/about');

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
			'href' => $this->url->link('extension/termopab/module/about', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''))
		];

		$data['save'] = $this->url->link('extension/termopab/module/about.save', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''));
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$module_info = [];
		if ($module_id) {
			$this->load->model('setting/module');
			$module_info = $this->model_setting_module->getModule($module_id);
		}

		$data['name'] = $module_info['name'] ?? '';
		$data['status'] = $module_info['status'] ?? 1;
		$data['module_id'] = $module_id;

		// Title: first line without span, rest in span (per language) — stored as title_lines[lang_id] = [line1, line2, ...]
		$data['title_lines'] = $module_info['title_lines'] ?? [];
		$data['description'] = $module_info['description'] ?? [];
		$data['button_text'] = $module_info['button_text'] ?? [];
		$data['button_url'] = $module_info['button_url'] ?? '';
		$data['facts'] = $module_info['facts'] ?? [];

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/module/about', $data));
	}

	public function save(): void {
		$this->load->language('extension/termopab/module/about');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/module/about')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$name = $this->request->post['name'] ?? '';
		if (oc_strlen($name) < 3 || oc_strlen($name) > 64) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (!$json) {
			$title_lines = [];
			foreach ($this->request->post['title_lines'] ?? [] as $lang_id => $raw) {
				if (is_array($raw)) {
					$lines = array_values(array_filter(array_map('trim', $raw)));
				} else {
					$lines = array_filter(array_map('trim', explode("\n", (string)$raw)));
				}
				$title_lines[(int)$lang_id] = array_values($lines);
			}

			$description = [];
			foreach ($this->request->post['description'] ?? [] as $lang_id => $val) {
				$description[(int)$lang_id] = trim((string)$val);
			}

			$button_text = [];
			foreach ($this->request->post['button_text'] ?? [] as $lang_id => $val) {
				$button_text[(int)$lang_id] = trim((string)$val);
			}

			$button_url = trim($this->request->post['button_url'] ?? '');

			$facts = [];
			$fact_keys = array_keys($this->request->post['fact'] ?? []);
			$fact_keys = array_filter($fact_keys, 'is_numeric');
			sort($fact_keys, SORT_NUMERIC);
			foreach ($fact_keys as $key) {
				$fact = $this->request->post['fact'][$key] ?? [];
				$title = [];
				$text = [];
				foreach ($fact['title'] ?? [] as $lid => $v) {
					$title[(int)$lid] = trim((string)$v);
				}
				foreach ($fact['text'] ?? [] as $lid => $v) {
					$text[(int)$lid] = trim((string)$v);
				}
				$url = trim($fact['url'] ?? '');
				$facts[] = [
					'title' => $title,
					'text'  => $text,
					'url'   => $url ?: '#',
				];
			}

			$post = [
				'name'        => $name,
				'title_lines' => $title_lines,
				'description' => $description,
				'button_text' => $button_text,
				'button_url'  => $button_url,
				'facts'       => $facts,
				'status'      => (int)($this->request->post['status'] ?? 0),
			];

			$this->load->model('setting/module');
			$module_id = (int)($this->request->post['module_id'] ?? 0);
			if (!$module_id) {
				$json['module_id'] = $this->model_setting_module->addModule('termopab.about', $post);
			} else {
				$this->model_setting_module->editModule($module_id, $post);
			}
			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
