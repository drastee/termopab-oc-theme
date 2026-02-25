<?php
namespace Opencart\Admin\Controller\Extension\Termopab;

/**
 * Обзори пивоварень (CRUD). Маршрут: extension/termopab/brewery_review
 */
class BreweryReview extends \Opencart\System\Engine\Controller {

	public function index(): void {
		if (!empty($this->request->get['install_tables']) && $this->user->hasPermission('modify', 'extension/termopab/brewery_review')) {
			$this->runInstallTables();
			$this->response->redirect($this->url->link('extension/termopab/brewery_review', 'user_token=' . $this->session->data['user_token']));
			return;
		}

		$this->addPaths();
		$this->load->language('extension/termopab/brewery_review/list');
		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/brewery_review', 'user_token=' . $this->session->data['user_token'])
		];

		$data['add'] = $this->url->link('extension/termopab/brewery_review.form', 'user_token=' . $this->session->data['user_token']);
		$data['install'] = $this->url->link('extension/termopab/brewery_review', 'user_token=' . $this->session->data['user_token'] . '&install_tables=1');

		$data['success'] = $this->session->data['success'] ?? '';
		$data['error_warning'] = $this->session->data['error'] ?? '';
		unset($this->session->data['success'], $this->session->data['error']);

		$data['user_token'] = $this->session->data['user_token'];
		$data['show_install'] = false;

		$page = (int)($this->request->get['page'] ?? 1);
		$limit = (int)($this->config->get('config_pagination_admin') ?: 20);
		$start = ($page - 1) * $limit;

		$this->load->model('extension/termopab/brewery_review');
		$this->load->model('tool/image');

		try {
			$total = $this->model_extension_termopab_brewery_review->getTotalBreweryReviews();
			$results = $this->model_extension_termopab_brewery_review->getBreweryReviews(['start' => $start, 'limit' => $limit, 'sort' => 'b.sort_order', 'order' => 'ASC']);
		} catch (\Throwable $e) {
			$total = 0;
			$results = [];
			$data['show_install'] = true;
		}

		$store_url = rtrim((string)$this->config->get('config_url'), '/');
		$data['brewery_reviews'] = [];
		$placeholder = $this->model_tool_image->resize('no_image.png', 40, 40);
		foreach ($results as $row) {
			$img = $row['image'] && is_file(DIR_IMAGE . html_entity_decode($row['image'], ENT_QUOTES, 'UTF-8'))
				? $this->model_tool_image->resize($row['image'], 40, 40) : $placeholder;
			$data['brewery_reviews'][] = [
				'brewery_review_id' => $row['brewery_review_id'],
				'title'             => $row['title'] ?: ('(ID ' . $row['brewery_review_id'] . ')'),
				'category_title'    => $row['category_title'] ?? '',
				'image'             => $img,
				'sort_order'        => $row['sort_order'],
				'status'            => $row['status'],
				'view'              => $store_url . '/index.php?route=extension/termopab/brewery_review.info' . '&brewery_review_id=' . $row['brewery_review_id'],
				'edit'              => $this->url->link('extension/termopab/brewery_review.form', 'user_token=' . $this->session->data['user_token'] . '&brewery_review_id=' . $row['brewery_review_id']),
				'copy'              => $this->url->link('extension/termopab/brewery_review.copy', 'user_token=' . $this->session->data['user_token'] . '&brewery_review_id=' . $row['brewery_review_id']),
				'delete'            => $this->url->link('extension/termopab/brewery_review.delete', 'user_token=' . $this->session->data['user_token'] . '&brewery_review_id=' . $row['brewery_review_id']),
			];
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('extension/termopab/brewery_review', 'user_token=' . $this->session->data['user_token'] . '&page={page}')
		]);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/brewery_review/list', $data));
	}

	public function form(): void {
		$this->addPaths();
		$this->load->language('extension/termopab/brewery_review/form');
		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/brewery_review', 'user_token=' . $this->session->data['user_token'])
		];

		$brewery_review_id = (int)($this->request->get['brewery_review_id'] ?? 0);
		$data['save'] = $this->url->link('extension/termopab/brewery_review.save', 'user_token=' . $this->session->data['user_token'] . ($brewery_review_id ? '&brewery_review_id=' . $brewery_review_id : ''));
		$data['cancel'] = $this->url->link('extension/termopab/brewery_review', 'user_token=' . $this->session->data['user_token']);

		$data['success'] = $this->session->data['success'] ?? '';
		$data['error_warning'] = $this->session->data['error_warning'] ?? '';
		unset($this->session->data['success'], $this->session->data['error_warning']);

		$data['brewery_review_id'] = $brewery_review_id;
		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('extension/termopab/brewery_review');
		$this->load->model('extension/termopab/brewery_review_category');
		$this->load->model('localisation/language');
		$this->load->model('tool/image');

		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		$data['categories'] = $this->model_extension_termopab_brewery_review_category->getCategories();

		if ($brewery_review_id) {
			$brewery_review = $this->model_extension_termopab_brewery_review->getBreweryReview($brewery_review_id);
			$descriptions = $this->model_extension_termopab_brewery_review->getBreweryReviewDescriptions($brewery_review_id);
			$gallery = $this->model_extension_termopab_brewery_review->getBreweryReviewImages($brewery_review_id);
			$seo_keywords = $this->model_extension_termopab_brewery_review->getBreweryReviewSeoKeywords($brewery_review_id);
		} else {
			$brewery_review = ['brewery_review_category_id' => 0, 'image' => '', 'logo' => '', 'video' => '', 'sort_order' => 0, 'status' => 1];
			$descriptions = [];
			$gallery = [];
			$seo_keywords = [];
		}

		$data['brewery_review_category_id'] = (int)($brewery_review['brewery_review_category_id'] ?? 0);
		$data['image'] = $brewery_review['image'] ?? '';
		$data['image_thumb'] = ($data['image'] && is_file(DIR_IMAGE . html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8')))
			? $this->model_tool_image->resize($data['image'], 100, 100) : $data['placeholder'];
		$data['logo'] = $brewery_review['logo'] ?? '';
		$data['logo_thumb'] = (!empty($data['logo']) && is_file(DIR_IMAGE . html_entity_decode($data['logo'], ENT_QUOTES, 'UTF-8')))
			? $this->model_tool_image->resize($data['logo'], 100, 100) : $data['placeholder'];
		$data['video'] = $brewery_review['video'] ?? '';
		$data['sort_order'] = $brewery_review['sort_order'] ?? 0;
		$data['status'] = $brewery_review['status'] ?? 1;

		$data['brewery_review_image'] = [];
		foreach ($gallery as $row) {
			$img = $row['image'] ?? '';
			$thumb = ($img && is_file(DIR_IMAGE . html_entity_decode($img, ENT_QUOTES, 'UTF-8')))
				? $this->model_tool_image->resize($img, 100, 100) : $data['placeholder'];
			$data['brewery_review_image'][] = ['image' => $img, 'image_thumb' => $thumb, 'sort_order' => $row['sort_order']];
		}

		$data['brewery_review_description'] = [];
		foreach ($data['languages'] as $language) {
			$d = $descriptions[$language['language_id']] ?? [];
			$data['brewery_review_description'][$language['language_id']] = [
				'heading'          => $d['heading'] ?? '',
				'title'             => $d['title'] ?? '',
				'description'       => $d['description'] ?? '',
				'article'           => $d['article'] ?? '',
				'meta_title'        => $d['meta_title'] ?? '',
				'meta_description'  => $d['meta_description'] ?? '',
				'meta_keyword'      => $d['meta_keyword'] ?? '',
			];
		}

		$data['seo_keyword'] = [];
		foreach ($data['languages'] as $language) {
			$data['seo_keyword'][$language['language_id']] = (string)($seo_keywords[$language['language_id']] ?? '');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/brewery_review/form', $data));
	}

	public function save(): void {
		$this->addPaths();
		$this->load->language('extension/termopab/brewery_review/form');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/brewery_review')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$brewery_review_id = (int)($this->request->post['brewery_review_id'] ?? 0);
		$brewery_review_description = $this->request->post['brewery_review_description'] ?? [];
		$first_title = '';
		foreach ($brewery_review_description as $lid => $d) {
			$t = trim((string)($d['title'] ?? ''));
			if ($t !== '' && $first_title === '') {
				$first_title = $t;
			}
		}
		if ($first_title === '' && !empty($brewery_review_description)) {
			$first_title = trim((string)reset($brewery_review_description)['title'] ?? '');
		}
		if (oc_strlen($first_title) < 1 || oc_strlen($first_title) > 255) {
			$json['error']['title'] = $this->language->get('error_title');
		}

		if (empty($json['error'])) {
			$brewery_review_image = [];
			$raw = $this->request->post['brewery_review_image'] ?? [];
			if (is_array($raw)) {
				$keys = array_filter(array_keys($raw), 'is_numeric');
				sort($keys, SORT_NUMERIC);
				foreach ($keys as $idx) {
					$img = trim((string)($raw[$idx]['image'] ?? ''));
					if ($img !== '') {
						$brewery_review_image[] = ['image' => $img];
					}
				}
			}
			$seo_keyword = $this->request->post['seo_keyword'] ?? [];
			$data = [
				'brewery_review_category_id' => (int)($this->request->post['brewery_review_category_id'] ?? 0),
				'image'                      => trim((string)($this->request->post['image'] ?? '')),
				'logo'                       => trim((string)($this->request->post['logo'] ?? '')),
				'video'                      => trim((string)($this->request->post['video'] ?? '')),
				'sort_order'                  => (int)($this->request->post['sort_order'] ?? 0),
				'status'                     => (int)($this->request->post['status'] ?? 0),
				'seo_keyword'                 => is_array($seo_keyword) ? $seo_keyword : [],
				'brewery_review_description' => [],
				'brewery_review_image'       => $brewery_review_image,
			];
			foreach ($brewery_review_description as $language_id => $desc) {
				$data['brewery_review_description'][(int)$language_id] = [
					'heading'          => trim((string)($desc['heading'] ?? '')),
					'title'            => trim((string)($desc['title'] ?? '')),
					'description'      => trim((string)($desc['description'] ?? '')),
					'article'          => (string)($desc['article'] ?? ''),
					'meta_title'       => trim((string)($desc['meta_title'] ?? '')),
					'meta_description' => trim((string)($desc['meta_description'] ?? '')),
					'meta_keyword'     => trim((string)($desc['meta_keyword'] ?? '')),
				];
			}

			$this->load->model('extension/termopab/brewery_review');
			if (!$brewery_review_id) {
				$brewery_review_id = $this->model_extension_termopab_brewery_review->addBreweryReview($data);
				$json['redirect'] = $this->url->link('extension/termopab/brewery_review.form', 'user_token=' . $this->session->data['user_token'] . '&brewery_review_id=' . $brewery_review_id);
			} else {
				$this->model_extension_termopab_brewery_review->editBreweryReview($brewery_review_id, $data);
				$json['redirect'] = $this->url->link('extension/termopab/brewery_review', 'user_token=' . $this->session->data['user_token']);
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
				$back = $brewery_review_id
					? $this->url->link('extension/termopab/brewery_review.form', 'user_token=' . $this->session->data['user_token'] . '&brewery_review_id=' . $brewery_review_id)
					: $this->url->link('extension/termopab/brewery_review.form', 'user_token=' . $this->session->data['user_token']);
				$this->response->redirect($back);
				return;
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function delete(): void {
		$this->addPaths();
		$this->load->language('extension/termopab/brewery_review/list');

		if (!$this->user->hasPermission('modify', 'extension/termopab/brewery_review')) {
			$this->session->data['error'] = $this->language->get('error_permission');
		} else {
			$brewery_review_id = (int)($this->request->get['brewery_review_id'] ?? 0);
			if ($brewery_review_id) {
				$this->load->model('extension/termopab/brewery_review');
				$this->model_extension_termopab_brewery_review->deleteBreweryReview($brewery_review_id);
				$this->session->data['success'] = $this->language->get('text_success');
			}
		}

		$this->response->redirect($this->url->link('extension/termopab/brewery_review', 'user_token=' . $this->session->data['user_token']));
	}

	public function copy(): void {
		$this->addPaths();
		$this->load->language('extension/termopab/brewery_review/list');

		if (!$this->user->hasPermission('modify', 'extension/termopab/brewery_review')) {
			$this->session->data['error'] = $this->language->get('error_permission');
		} else {
			$brewery_review_id = (int)($this->request->get['brewery_review_id'] ?? 0);
			if ($brewery_review_id) {
				$this->load->model('extension/termopab/brewery_review');
				$new_id = $this->model_extension_termopab_brewery_review->copyBreweryReview($brewery_review_id);
				if ($new_id) {
					$this->session->data['success'] = $this->language->get('text_copy_success');
					$this->response->redirect($this->url->link('extension/termopab/brewery_review.form', 'user_token=' . $this->session->data['user_token'] . '&brewery_review_id=' . $new_id));
					return;
				}
			}
			$this->session->data['error'] = $this->language->get('error_copy');
		}

		$this->response->redirect($this->url->link('extension/termopab/brewery_review', 'user_token=' . $this->session->data['user_token']));
	}

	public function upload(): void {
		$this->addPaths();
		$this->load->language('extension/termopab/brewery_review/form');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/brewery_review')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$max_size = ((int)$this->config->get('config_file_max_size') ?: 10) * 1024 * 1024;
		$file = $_FILES['video'] ?? null;

		if (empty($json['error']) && $file && !empty($file['name']) && isset($file['tmp_name'])) {
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
				$dir = DIR_IMAGE . 'catalog/brewery_review/';
				if (!is_dir($dir)) {
					mkdir($dir, 0755, true);
				}
				$filename = preg_replace('/[^a-zA-Z0-9._-]/', '', basename(html_entity_decode($file['name'], ENT_QUOTES, 'UTF-8')));
				if ($filename === '') {
					$filename = 'brewery_review_' . time() . '.mp4';
				} elseif (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'mp4') {
					$filename .= '.mp4';
				}
				$path = $dir . $filename;
				if (is_uploaded_file($file['tmp_name']) && move_uploaded_file($file['tmp_name'], $path)) {
					$json['path'] = 'catalog/brewery_review/' . $filename;
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

	protected function addPaths(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');
		if (method_exists($this->language, 'addPath')) {
			$this->language->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/language/');
		}
	}

	private function runInstallTables(): void {
		$prefix = defined('DB_PREFIX') ? DB_PREFIX : 'tp_';
		$sql = [
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "brewery_review` (
				`brewery_review_id` int(11) NOT NULL AUTO_INCREMENT,
				`brewery_review_category_id` int(11) NOT NULL DEFAULT 0,
				`image` varchar(255) DEFAULT NULL,
				`logo` varchar(255) DEFAULT NULL,
				`video` varchar(512) DEFAULT NULL,
				`sort_order` int(11) NOT NULL DEFAULT 0,
				`status` tinyint(1) NOT NULL DEFAULT 1,
				`date_added` datetime NOT NULL,
				`date_modified` datetime NOT NULL,
				PRIMARY KEY (`brewery_review_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "brewery_review_description` (
				`brewery_review_id` int(11) NOT NULL,
				`language_id` int(11) NOT NULL,
				`heading` varchar(255) DEFAULT NULL,
				`title` varchar(255) NOT NULL,
				`description` text DEFAULT NULL,
				`article` mediumtext DEFAULT NULL,
				`meta_title` varchar(255) DEFAULT NULL,
				`meta_description` varchar(255) DEFAULT NULL,
				`meta_keyword` varchar(255) DEFAULT NULL,
				PRIMARY KEY (`brewery_review_id`,`language_id`),
				KEY `language_id` (`language_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "brewery_review_image` (
				`brewery_review_image_id` int(11) NOT NULL AUTO_INCREMENT,
				`brewery_review_id` int(11) NOT NULL,
				`image` varchar(255) NOT NULL,
				`sort_order` int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY (`brewery_review_image_id`),
				KEY `brewery_review_id` (`brewery_review_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "brewery_review_category` (
				`brewery_review_category_id` int(11) NOT NULL AUTO_INCREMENT,
				`sort_order` int(11) NOT NULL DEFAULT 0,
				`status` tinyint(1) NOT NULL DEFAULT 1,
				`date_added` datetime NOT NULL,
				`date_modified` datetime NOT NULL,
				PRIMARY KEY (`brewery_review_category_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "brewery_review_category_description` (
				`brewery_review_category_id` int(11) NOT NULL,
				`language_id` int(11) NOT NULL,
				`title` varchar(255) NOT NULL,
				PRIMARY KEY (`brewery_review_category_id`,`language_id`),
				KEY `language_id` (`language_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
		];
		$alters = [
			"ALTER TABLE `" . $prefix . "brewery_review` ADD COLUMN `brewery_review_category_id` int(11) NOT NULL DEFAULT 0 AFTER `brewery_review_id`",
		];
		try {
			foreach ($sql as $q) {
				$this->db->query($q);
			}
			foreach ($alters as $q) {
				try {
					$this->db->query($q);
				} catch (\Throwable $e) {
				}
			}
			$this->session->data['success'] = 'Таблиці оглядів пивоварень та категорій створено.';
		} catch (\Throwable $e) {
			$this->session->data['error'] = 'Помилка: ' . $e->getMessage();
		}
	}
}
