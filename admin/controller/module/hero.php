<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Module;

class Hero extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');

		$this->load->language('extension/termopab/module/hero');

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
			'href' => $this->url->link('extension/termopab/module/hero', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''))
		];

		$data['save'] = $this->url->link('extension/termopab/module/hero.save', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''));
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$module_info = [];
		if ($module_id) {
			$this->load->model('setting/module');
			$module_info = $this->model_setting_module->getModule($module_id);
		}

		$data['name'] = $module_info['name'] ?? '';
		$data['status'] = $module_info['status'] ?? 1;
		$data['module_id'] = $module_id;

		// Video path (e.g. catalog/hero/hero.mp4)
		$data['video'] = $module_info['video'] ?? '';
		// Poster image path (e.g. catalog/hero/hero-bg.webp)
		$data['poster'] = $module_info['poster'] ?? '';

		$this->load->model('tool/image');
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		if ($data['poster'] && is_file(DIR_IMAGE . html_entity_decode($data['poster'], ENT_QUOTES, 'UTF-8'))) {
			$data['poster_thumb'] = $this->model_tool_image->resize($data['poster'], 200, 120);
		} else {
			$data['poster_thumb'] = $data['placeholder'];
		}

		// Title lines per language: { lang_id: ['Line 1', 'Line 2', 'Line 3'] }
		// Each line = one <span> for GSAP animation
		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['title_lines'] = $module_info['title_lines'] ?? [];

		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/module/hero', $data));
	}

	public function save(): void {
		$this->load->language('extension/termopab/module/hero');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/module/hero')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$name = $this->request->post['name'] ?? '';
		if (oc_strlen($name) < 3 || oc_strlen($name) > 64) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (!$json) {
			$title_lines = [];
			foreach ($this->request->post['title_lines'] ?? [] as $lang_id => $lines) {
				if (!is_array($lines)) {
					$lines = array_filter(array_map('trim', explode("\n", (string)$lines)));
				}
				$title_lines[(int)$lang_id] = array_values(array_filter(array_map('trim', $lines)));
			}

			$post = [
				'name'        => $name,
				'video'       => trim($this->request->post['video'] ?? ''),
				'poster'      => trim($this->request->post['poster'] ?? ''),
				'title_lines' => $title_lines,
				'status'      => (int)($this->request->post['status'] ?? 0),
			];

			$this->load->model('setting/module');

			$module_id = (int)($this->request->post['module_id'] ?? 0);
			if (!$module_id) {
				$json['module_id'] = $this->model_setting_module->addModule('termopab.hero', $post);
			} else {
				$this->model_setting_module->editModule($module_id, $post);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Upload video file (mp4). Saves to image/catalog/hero/
	 */
	public function upload(): void {
		$this->load->language('extension/termopab/module/hero');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/module/hero')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$max_size = ((int)$this->config->get('config_file_max_size') ?: 10) * 1024 * 1024;

		// Use $_FILES directly - Request::clean() may alter tmp_name path
		$file = $_FILES['video'] ?? null;

		if (!$json && $file && !empty($file['name']) && isset($file['tmp_name'])) {

			if ($file['error'] !== UPLOAD_ERR_OK) {
				$upload_errors = [
					UPLOAD_ERR_INI_SIZE   => 'Файл перевищує upload_max_filesize в php.ini',
					UPLOAD_ERR_FORM_SIZE  => 'Файл надто великий',
					UPLOAD_ERR_PARTIAL    => 'Файл завантажено лише частково',
					UPLOAD_ERR_NO_FILE    => 'Файл не вибрано',
					UPLOAD_ERR_NO_TMP_DIR => 'Відсутня тимчасова папка',
					UPLOAD_ERR_CANT_WRITE => 'Неможливо записати файл на диск',
					UPLOAD_ERR_EXTENSION  => 'PHP розширення зупинило завантаження',
				];
				$json['error'] = $upload_errors[$file['error']] ?? $this->language->get('error_upload') . ' (код: ' . $file['error'] . ')';
			} elseif ($file['size'] > $max_size) {
				$json['error'] = sprintf($this->language->get('error_upload_size'), $this->config->get('config_file_max_size') ?: 10);
			} else {
				$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
				if ($ext !== 'mp4') {
					$json['error'] = $this->language->get('error_video_type');
				}
			}

			if (!$json) {
				$dir = DIR_IMAGE . 'catalog/hero/';
				if (!is_dir($dir)) {
					mkdir($dir, 0755, true);
				}

				$filename = preg_replace('/[^a-zA-Z0-9._-]/', '', basename(html_entity_decode($file['name'], ENT_QUOTES, 'UTF-8')));
				if (empty($filename)) {
					$filename = 'hero_' . time() . '.mp4';
				} elseif (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'mp4') {
					$filename .= '.mp4';
				}

				$path = $dir . $filename;
				if (is_uploaded_file($file['tmp_name']) && move_uploaded_file($file['tmp_name'], $path)) {
					$json['path'] = 'catalog/hero/' . $filename;
					$json['success'] = $this->language->get('text_uploaded');
				} else {
					$json['error'] = 'Не вдалося зберегти файл. Перевірте права на папку image/catalog/hero/';
				}
			}
		} else {
			$hint = '';
			if (empty($_FILES['video'])) {
				$hint = ' Файл не прийшов на сервер — можливо post_max_size або upload_max_filesize занадто малі в php.ini.';
			} elseif (!empty($_FILES['video']['error'])) {
				$hint = ' Код помилки PHP: ' . (int)$_FILES['video']['error'];
			}
			$json['error'] = 'Помилка завантаження!' . $hint;
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
