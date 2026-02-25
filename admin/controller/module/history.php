<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Module;

class History extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');

		$this->load->language('extension/termopab/module/history');

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
			'href' => $this->url->link('extension/termopab/module/history', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''))
		];

		$data['save'] = $this->url->link('extension/termopab/module/history.save', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''));
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$module_info = [];
		if ($module_id) {
			$this->load->model('setting/module');
			try {
				$module_info = $this->model_setting_module->getModule($module_id);
			} catch (\Throwable $e) {
				$module_info = [];
			}
			if (!is_array($module_info)) {
				$module_info = [];
			}
		}

		$data['name'] = $module_info['name'] ?? '';
		$data['status'] = $module_info['status'] ?? 1;
		$data['module_id'] = $module_id;
		$data['title'] = $module_info['title'] ?? [];
		$data['slides'] = $module_info['slides'] ?? [];

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['user_token'] = $this->session->data['user_token'];

		$this->document->addScript('view/javascript/ckeditor/ckeditor.js');
		$this->document->addScript('view/javascript/ckeditor/adapters/jquery.js');
		$this->load->language('default');
		$data['ckeditor'] = $this->language->get('ckeditor') ?: 'en';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/module/history', $data));
	}

	public function save(): void {
		$this->load->language('extension/termopab/module/history');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/module/history')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$name = $this->request->post['name'] ?? '';
		if (oc_strlen($name) < 3 || oc_strlen($name) > 64) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (!$json) {
			$title = [];
			foreach ($this->request->post['title'] ?? [] as $lang_id => $val) {
				$title[(int)$lang_id] = $this->ensureUtf8(trim((string)$val));
			}

			$slides = [];
			$slide_keys = array_keys($this->request->post['slide'] ?? []);
			$slide_keys = array_filter($slide_keys, 'is_numeric');
			sort($slide_keys, SORT_NUMERIC);
			foreach ($slide_keys as $key) {
				$slide = $this->request->post['slide'][$key] ?? [];
				$year = $this->ensureUtf8(trim((string)($slide['year'] ?? '')));
				$content = [];
				foreach ($slide['content'] ?? [] as $lid => $v) {
					$raw = (string)$v;
					$content[(int)$lid] = $this->ensureUtf8(html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
				}
				$slides[] = [
					'year'    => $year,
					'content' => $content,
				];
			}

			$post = [
				'name'   => $this->ensureUtf8($name),
				'title'  => $title,
				'slides' => $slides,
				'status' => (int)($this->request->post['status'] ?? 0),
			];

			// Рекурсивно очистити всі рядки до валідного UTF-8 і переконатися, що json_encode не падає
			$post = $this->sanitizeForJson($post);
			$post['status'] = (int)($this->request->post['status'] ?? 0);

			// Переконатися, що дані коректно кодуються в JSON (ядро зберігає через json_encode)
			if (json_encode($post) === false) {
				$json['error']['warning'] = $this->language->get('error_encoding');
			} else {
				$this->load->model('setting/module');
				$module_id = (int)($this->request->post['module_id'] ?? 0);
				if (!$module_id) {
					$json['module_id'] = $this->model_setting_module->addModule('termopab.history', $post);
				} else {
					$this->model_setting_module->editModule($module_id, $post);
				}
				$json['success'] = $this->language->get('text_success');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Привести рядок до валідного UTF-8, щоб json_encode у ядрі не повернув false.
	 */
	private function ensureUtf8(string $str): string {
		if ($str === '') {
			return '';
		}
		if (function_exists('mb_convert_encoding')) {
			$enc = mb_detect_encoding($str, ['UTF-8', 'ISO-8859-1', 'Windows-1251'], true);
			if ($enc && $enc !== 'UTF-8') {
				$str = (string) mb_convert_encoding($str, 'UTF-8', $enc);
			} elseif (!$enc) {
				$str = (string) mb_convert_encoding($str, 'UTF-8', 'UTF-8');
			}
		}
		return $str;
	}

	/**
	 * Рекурсивно пройти масив і переконатися, що всі рядки — валідний UTF-8.
	 * У ядра при збереженні викликається json_encode; невалідний UTF-8 дає false → в БД потрапляє порожнє/бите.
	 */
	private function sanitizeForJson(array $data): array {
		$out = [];
		foreach ($data as $k => $v) {
			if (is_array($v)) {
				$out[$k] = $this->sanitizeForJson($v);
			} elseif (is_string($v)) {
				if (function_exists('mb_convert_encoding')) {
					$v = (string) mb_convert_encoding($v, 'UTF-8', 'UTF-8');
				}
				$out[$k] = $v;
			} elseif (is_int($v) || is_bool($v) || is_float($v)) {
				$out[$k] = $v;
			} else {
				$out[$k] = (string) $v;
			}
		}
		return $out;
	}
}
