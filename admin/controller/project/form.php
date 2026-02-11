<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Project;

class Form extends \Opencart\System\Engine\Controller {
	public function index(): void {
		if (method_exists($this->language, 'addPath')) {
			$this->language->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/language/');
		}
		$this->load->language('extension/termopab/project/form');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/project', 'user_token=' . $this->session->data['user_token'])
		];

		$project_id = (int)($this->request->get['project_id'] ?? 0);
		$data['save'] = $this->url->link('extension/termopab/project/form.save', 'user_token=' . $this->session->data['user_token'] . ($project_id ? '&project_id=' . $project_id : ''));
		$data['cancel'] = $this->url->link('extension/termopab/project', 'user_token=' . $this->session->data['user_token']);

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}
		if (isset($this->session->data['error_warning'])) {
			$data['error_warning'] = $this->session->data['error_warning'];
			unset($this->session->data['error_warning']);
		} else {
			$data['error_warning'] = '';
		}

		$data['project_id'] = $project_id;
		$data['user_token'] = $this->session->data['user_token'];

		$this->ensureProjectModel();
		$this->load->model('localisation/language');
		$this->load->model('tool/image');

		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		if ($project_id) {
			$project = $this->model_extension_termopab_project->getProject($project_id);
			$descriptions = $this->model_extension_termopab_project->getProjectDescriptions($project_id);
			$gallery = $this->model_extension_termopab_project->getProjectImages($project_id);
		} else {
			$project = ['image' => '', 'logo' => '', 'video' => '', 'sort_order' => 0, 'status' => 1];
			$descriptions = [];
			$gallery = [];
		}

		$data['image'] = $project['image'] ?? '';
		$data['image_thumb'] = ($data['image'] && is_file(DIR_IMAGE . html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8')))
			? $this->model_tool_image->resize($data['image'], 100, 100) : $data['placeholder'];
		$data['logo'] = $project['logo'] ?? '';
		$data['logo_thumb'] = (!empty($data['logo']) && is_file(DIR_IMAGE . html_entity_decode($data['logo'], ENT_QUOTES, 'UTF-8')))
			? $this->model_tool_image->resize($data['logo'], 100, 100) : $data['placeholder'];
		$data['video'] = $project['video'] ?? '';
		$data['sort_order'] = $project['sort_order'] ?? 0;
		$data['status'] = $project['status'] ?? 1;

		$data['project_image'] = [];
		foreach ($gallery as $row) {
			$img = $row['image'] ?? '';
			$thumb = ($img && is_file(DIR_IMAGE . html_entity_decode($img, ENT_QUOTES, 'UTF-8')))
				? $this->model_tool_image->resize($img, 100, 100) : $data['placeholder'];
			$data['project_image'][] = ['image' => $img, 'image_thumb' => $thumb, 'sort_order' => $row['sort_order']];
		}

		$data['project_description'] = [];
		foreach ($data['languages'] as $language) {
			$d = $descriptions[$language['language_id']] ?? [];
			$data['project_description'][$language['language_id']] = [
				'heading'          => $d['heading'] ?? '',
				'title'            => $d['title'] ?? '',
				'description'      => $d['description'] ?? '',
				'article'          => $d['article'] ?? '',
				'meta_title'       => $d['meta_title'] ?? '',
				'meta_description' => $d['meta_description'] ?? '',
				'meta_keyword'     => $d['meta_keyword'] ?? '',
			];
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/project/form', $data));
	}

	public function save(): void {
		if (method_exists($this->language, 'addPath')) {
			$this->language->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/language/');
		}
		$this->load->language('extension/termopab/project/form');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/project/form')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$project_id = (int)($this->request->post['project_id'] ?? 0);
		$project_description = $this->request->post['project_description'] ?? [];
		$first_title = '';
		foreach ($project_description as $lid => $d) {
			$t = trim((string)($d['title'] ?? ''));
			if ($t !== '' && $first_title === '') {
				$first_title = $t;
			}
		}
		if ($first_title === '' && !empty($project_description)) {
			$first_title = trim((string)reset($project_description)['title'] ?? '');
		}
		if (oc_strlen($first_title) < 1 || oc_strlen($first_title) > 255) {
			$json['error']['title'] = $this->language->get('error_title');
		}

		if (!$json) {
			$project_image = [];
			$raw = $this->request->post['project_image'] ?? [];
			if (is_array($raw)) {
				$keys = array_filter(array_keys($raw), 'is_numeric');
				sort($keys, SORT_NUMERIC);
				foreach ($keys as $idx) {
					$img = trim((string)($raw[$idx]['image'] ?? ''));
					if ($img !== '') {
						$project_image[] = ['image' => $img];
					}
				}
			}
			$data = [
				'image'       => trim((string)($this->request->post['image'] ?? '')),
				'logo'        => trim((string)($this->request->post['logo'] ?? '')),
				'video'       => trim((string)($this->request->post['video'] ?? '')),
				'sort_order'  => (int)($this->request->post['sort_order'] ?? 0),
				'status'      => (int)($this->request->post['status'] ?? 0),
				'project_description' => [],
				'project_image' => $project_image,
			];
			foreach ($project_description as $language_id => $desc) {
				$data['project_description'][(int)$language_id] = [
					'heading'          => trim((string)($desc['heading'] ?? '')),
					'title'            => trim((string)($desc['title'] ?? '')),
					'description'      => trim((string)($desc['description'] ?? '')),
					'article'          => (string)($desc['article'] ?? ''),
					'meta_title'       => trim((string)($desc['meta_title'] ?? '')),
					'meta_description' => trim((string)($desc['meta_description'] ?? '')),
					'meta_keyword'     => trim((string)($desc['meta_keyword'] ?? '')),
				];
			}

			$this->ensureProjectModel();
			if (!$project_id) {
				$project_id = $this->model_extension_termopab_project->addProject($data);
				$json['redirect'] = $this->url->link('extension/termopab/project/form', 'user_token=' . $this->session->data['user_token'] . '&project_id=' . $project_id);
			} else {
				$this->model_extension_termopab_project->editProject($project_id, $data);
				$json['redirect'] = $this->url->link('extension/termopab/project', 'user_token=' . $this->session->data['user_token']);
			}
			$json['success'] = $this->language->get('text_success');
		}

		$is_ajax = !empty($this->request->server['HTTP_X_REQUESTED_WITH'])
			&& strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
		if (!$is_ajax) {
			if (!empty($json['redirect'])) {
				$this->session->data['success'] = $json['success'] ?? '';
				$this->response->redirect($json['redirect']);
				return;
			}
			if (!empty($json['error'])) {
				$this->session->data['error_warning'] = $json['error']['warning'] ?? implode(' ', $json['error']);
				$back = $project_id
					? $this->url->link('extension/termopab/project/form', 'user_token=' . $this->session->data['user_token'] . '&project_id=' . $project_id)
					: $this->url->link('extension/termopab/project/form', 'user_token=' . $this->session->data['user_token']);
				$this->response->redirect($back);
				return;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Upload video file (mp4). Saves to image/catalog/project/
	 */
	public function upload(): void {
		if (method_exists($this->language, 'addPath')) {
			$this->language->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/language/');
		}
		$this->load->language('extension/termopab/project/form');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/project/form')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$max_size = ((int)$this->config->get('config_file_max_size') ?: 10) * 1024 * 1024;
		$file = $_FILES['video'] ?? null;

		if (!$json && $file && !empty($file['name']) && isset($file['tmp_name'])) {
			if ($file['error'] !== UPLOAD_ERR_OK) {
				$upload_errors = [
					UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize',
					UPLOAD_ERR_FORM_SIZE  => 'File too large',
					UPLOAD_ERR_PARTIAL    => 'File uploaded only partially',
					UPLOAD_ERR_NO_FILE    => 'No file selected',
					UPLOAD_ERR_NO_TMP_DIR => 'Missing temp directory',
					UPLOAD_ERR_CANT_WRITE => 'Cannot write file to disk',
					UPLOAD_ERR_EXTENSION  => 'PHP extension stopped upload',
				];
				$json['error'] = $upload_errors[$file['error']] ?? 'Upload error (code: ' . $file['error'] . ')';
			} elseif ($file['size'] > $max_size) {
				$json['error'] = sprintf($this->language->get('error_upload_size'), $this->config->get('config_file_max_size') ?: 10);
			} else {
				$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
				if ($ext !== 'mp4') {
					$json['error'] = $this->language->get('error_video_type');
				}
			}

			if (!isset($json['error'])) {
				$dir = DIR_IMAGE . 'catalog/project/';
				if (!is_dir($dir)) {
					mkdir($dir, 0755, true);
				}
				$filename = preg_replace('/[^a-zA-Z0-9._-]/', '', basename(html_entity_decode($file['name'], ENT_QUOTES, 'UTF-8')));
				if ($filename === '') {
					$filename = 'project_' . time() . '.mp4';
				} elseif (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'mp4') {
					$filename .= '.mp4';
				}
				$path = $dir . $filename;
				if (is_uploaded_file($file['tmp_name']) && move_uploaded_file($file['tmp_name'], $path)) {
					$json['path'] = 'catalog/project/' . $filename;
					$json['success'] = $this->language->get('text_uploaded');
				} else {
					$json['error'] = $this->language->get('error_upload_path');
				}
			}
		} else {
			$json['error'] = empty($_FILES['video']) ? 'No file received.' : 'Upload error: ' . (int)($_FILES['video']['error'] ?? 0);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function ensureProjectModel(): void {
		$key = 'model_extension_termopab_project';
		if ($this->registry->has($key)) {
			return;
		}
		$path = DIR_EXTENSION . 'termopab/admin/model/extension/termopab/project.php';
		if (is_file($path)) {
			require_once $path;
			$this->registry->set($key, new \Opencart\Admin\Model\Extension\Termopab\Project($this->registry));
		} else {
			$this->load->model('extension/termopab/project');
		}
	}
}
