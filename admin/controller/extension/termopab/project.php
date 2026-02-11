<?php
namespace Opencart\Admin\Controller\Extension\Termopab;

/**
 * Projects list (default action).
 * Route: extension/termopab/project
 */
class Project extends \Opencart\System\Engine\Controller {
	public function index(): void {
		// One-time DB migration: ?install_tables=1 (uses same permission as Projects)
		if (!empty($this->request->get['install_tables']) && $this->user->hasPermission('modify', 'extension/termopab/project')) {
			$this->runInstallTables();
			$this->response->redirect($this->url->link('extension/termopab/project', 'user_token=' . $this->session->data['user_token']));
			return;
		}

		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');
		$this->load->language('extension/termopab/project/list');

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

		$data['add'] = $this->url->link('extension/termopab/project/form', 'user_token=' . $this->session->data['user_token']);
		$data['install'] = $this->url->link('extension/termopab/project', 'user_token=' . $this->session->data['user_token'] . '&install_tables=1');

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];
			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}
		if (isset($this->session->data['error'])) {
			$data['error_warning'] = $this->session->data['error'];
			unset($this->session->data['error']);
		} else {
			$data['error_warning'] = '';
		}

		$data['user_token'] = $this->session->data['user_token'];

		$page = (int)($this->request->get['page'] ?? 1);
		$limit = (int)($this->config->get('config_pagination_admin') ?: 20);
		$start = ($page - 1) * $limit;

		$this->load->model('extension/termopab/project');
		$this->load->model('tool/image');

		$total = $this->model_extension_termopab_project->getTotalProjects();
		$results = $this->model_extension_termopab_project->getProjects(['start' => $start, 'limit' => $limit, 'sort' => 'p.sort_order', 'order' => 'ASC']);

		$data['projects'] = [];
		$placeholder = $this->model_tool_image->resize('no_image.png', 40, 40);
		foreach ($results as $row) {
			$img = $row['image'] && is_file(DIR_IMAGE . html_entity_decode($row['image'], ENT_QUOTES, 'UTF-8'))
				? $this->model_tool_image->resize($row['image'], 40, 40) : $placeholder;
			$data['projects'][] = [
				'project_id'  => $row['project_id'],
				'title'       => $row['title'] ?: ('(ID ' . $row['project_id'] . ')'),
				'image'       => $img,
				'sort_order'  => $row['sort_order'],
				'status'      => $row['status'],
				'edit'        => $this->url->link('extension/termopab/project/form', 'user_token=' . $this->session->data['user_token'] . '&project_id=' . $row['project_id']),
				'delete'      => $this->url->link('extension/termopab/project/delete', 'user_token=' . $this->session->data['user_token'] . '&project_id=' . $row['project_id']),
			];
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('extension/termopab/project', 'user_token=' . $this->session->data['user_token'] . '&page={page}')
		]);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/project/list', $data));
	}

	private function runInstallTables(): void {
		$prefix = defined('DB_PREFIX') ? DB_PREFIX : 'tp_';
		$sql = [
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "project` (
				`project_id` int(11) NOT NULL AUTO_INCREMENT,
				`image` varchar(255) DEFAULT NULL,
				`logo` varchar(255) DEFAULT NULL,
				`video` varchar(512) DEFAULT NULL,
				`sort_order` int(11) NOT NULL DEFAULT 0,
				`status` tinyint(1) NOT NULL DEFAULT 1,
				`date_added` datetime NOT NULL,
				`date_modified` datetime NOT NULL,
				PRIMARY KEY (`project_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "project_description` (
				`project_id` int(11) NOT NULL,
				`language_id` int(11) NOT NULL,
				`heading` varchar(255) DEFAULT NULL,
				`title` varchar(255) NOT NULL,
				`description` text DEFAULT NULL,
				`article` mediumtext DEFAULT NULL,
				`meta_title` varchar(255) DEFAULT NULL,
				`meta_description` varchar(255) DEFAULT NULL,
				`meta_keyword` varchar(255) DEFAULT NULL,
				PRIMARY KEY (`project_id`,`language_id`),
				KEY `language_id` (`language_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "project_image` (
				`project_image_id` int(11) NOT NULL AUTO_INCREMENT,
				`project_id` int(11) NOT NULL,
				`image` varchar(255) NOT NULL,
				`sort_order` int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY (`project_image_id`),
				KEY `project_id` (`project_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
		];
		$alters = [
			"ALTER TABLE `" . $prefix . "project` ADD COLUMN `logo` varchar(255) DEFAULT NULL AFTER `image`",
			"ALTER TABLE `" . $prefix . "project` ADD COLUMN `video` varchar(512) DEFAULT NULL AFTER `logo`",
			"ALTER TABLE `" . $prefix . "project_description` ADD COLUMN `heading` varchar(255) DEFAULT NULL AFTER `language_id`",
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
			$this->session->data['success'] = 'Таблицы проектов созданы/обновлены.';
		} catch (\Throwable $e) {
			$this->session->data['error'] = 'Ошибка: ' . $e->getMessage();
		}
	}
}
