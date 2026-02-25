<?php
namespace Opencart\Admin\Controller\Extension\Termopab;

/**
 * Callback requests (admin list + status update). Route: extension/termopab/callback_request
 */
class CallbackRequest extends \Opencart\System\Engine\Controller {
	public function index(): void {
		if (!empty($this->request->get['install_tables']) && $this->user->hasPermission('modify', 'extension/termopab/callback_request')) {
			$this->runInstallTables();
			$this->response->redirect($this->url->link('extension/termopab/callback_request', 'user_token=' . $this->session->data['user_token']));
			return;
		}

		$this->addPaths();
		$this->load->language('extension/termopab/callback_request/list');
		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/callback_request', 'user_token=' . $this->session->data['user_token'])
		];

		$data['install'] = $this->url->link('extension/termopab/callback_request', 'user_token=' . $this->session->data['user_token'] . '&install_tables=1');
		$data['delete'] = $this->url->link('extension/termopab/callback_request.delete', 'user_token=' . $this->session->data['user_token']);

		$data['success'] = $this->session->data['success'] ?? '';
		$data['error_warning'] = $this->session->data['error'] ?? '';
		unset($this->session->data['success'], $this->session->data['error']);

		$data['user_token'] = $this->session->data['user_token'];
		$data['show_install'] = false;

		$page = (int)($this->request->get['page'] ?? 1);
		$limit = (int)($this->config->get('config_pagination_admin') ?: 20);
		$start = ($page - 1) * $limit;

		$this->load->model('extension/termopab/callback_request');

		try {
			$total = $this->model_extension_termopab_callback_request->getTotalCallbackRequests();
			$results = $this->model_extension_termopab_callback_request->getCallbackRequests([
				'start' => $start,
				'limit' => $limit,
				'sort' => 'cr.date_added',
				'order' => 'DESC'
			]);
		} catch (\Throwable $e) {
			$total = 0;
			$results = [];
			$data['show_install'] = true;
		}

		$data['requests'] = [];
		$data['selected'] = (array)($this->request->post['selected'] ?? []);
		foreach ($results as $row) {
			$data['requests'][] = [
				'callback_request_id' => (int)$row['callback_request_id'],
				'name' => (string)($row['name'] ?? ''),
				'phone' => (string)($row['phone'] ?? ''),
				'status' => (int)($row['status'] ?? 0),
				'comment' => (string)($row['comment'] ?? ''),
				'ip' => (string)($row['ip'] ?? ''),
				'user_agent' => (string)($row['user_agent'] ?? ''),
				'date_added' => (string)($row['date_added'] ?? ''),
				'edit' => $this->url->link('extension/termopab/callback_request.form', 'user_token=' . $this->session->data['user_token'] . '&callback_request_id=' . (int)$row['callback_request_id']),
			];
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('extension/termopab/callback_request', 'user_token=' . $this->session->data['user_token'] . '&page={page}')
		]);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/callback_request/list', $data));
	}

	public function delete(): void {
		$this->addPaths();
		$this->load->language('extension/termopab/callback_request/list');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/callback_request')) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$selected = $this->request->post['selected'] ?? [];
			if (is_array($selected) && $selected) {
				$this->load->model('extension/termopab/callback_request');
				foreach ($selected as $callback_request_id) {
					$this->model_extension_termopab_callback_request->deleteCallbackRequest((int)$callback_request_id);
				}
				$json['success'] = $this->language->get('text_success');
			}
		}

		$json['redirect'] = html_entity_decode($this->url->link('extension/termopab/callback_request', 'user_token=' . $this->session->data['user_token']), ENT_QUOTES, 'UTF-8');

		$is_ajax = !empty($this->request->server['HTTP_X_REQUESTED_WITH'])
			&& strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
		if ($is_ajax) {
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			return;
		}

		if (!empty($json['error'])) {
			$this->session->data['error'] = $json['error'];
		} elseif (!empty($json['success'])) {
			$this->session->data['success'] = $json['success'];
		}
		$this->response->redirect($json['redirect']);
	}

	public function form(): void {
		$this->addPaths();
		$this->load->language('extension/termopab/callback_request/form');
		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/callback_request', 'user_token=' . $this->session->data['user_token'])
		];

		$callback_request_id = (int)($this->request->get['callback_request_id'] ?? 0);
		$data['save'] = $this->url->link('extension/termopab/callback_request.save', 'user_token=' . $this->session->data['user_token'] . '&callback_request_id=' . $callback_request_id);
		$data['delete'] = $this->url->link('extension/termopab/callback_request.delete', 'user_token=' . $this->session->data['user_token']);
		$data['cancel'] = $this->url->link('extension/termopab/callback_request', 'user_token=' . $this->session->data['user_token']);

		$data['success'] = $this->session->data['success'] ?? '';
		$data['error_warning'] = $this->session->data['error_warning'] ?? '';
		unset($this->session->data['success'], $this->session->data['error_warning']);

		$data['callback_request_id'] = $callback_request_id;
		$data['user_token'] = $this->session->data['user_token'];

		$this->load->model('extension/termopab/callback_request');
		$request = $callback_request_id ? $this->model_extension_termopab_callback_request->getCallbackRequest($callback_request_id) : [];
		$data['name'] = (string)($request['name'] ?? '');
		$data['phone'] = (string)($request['phone'] ?? '');
		$data['status'] = (int)($request['status'] ?? 0);
		$data['comment'] = (string)($request['comment'] ?? '');
		$data['ip'] = (string)($request['ip'] ?? '');
		$data['user_agent'] = (string)($request['user_agent'] ?? '');
		$data['date_added'] = (string)($request['date_added'] ?? '');

		$data['status_options'] = [
			0 => $this->language->get('text_status_new'),
			1 => $this->language->get('text_status_in_progress'),
			2 => $this->language->get('text_status_done'),
		];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/callback_request/form', $data));
	}

	public function save(): void {
		$this->addPaths();
		$this->load->language('extension/termopab/callback_request/form');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/callback_request')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$callback_request_id = (int)($this->request->get['callback_request_id'] ?? 0);
		$status = (int)($this->request->post['status'] ?? 0);
		$comment = (string)($this->request->post['comment'] ?? '');

		if (!$callback_request_id) {
			$json['error']['warning'] = $this->language->get('error_not_found');
		}

		if (empty($json['error'])) {
			$this->load->model('extension/termopab/callback_request');
			$this->model_extension_termopab_callback_request->editCallbackRequest($callback_request_id, [
				'status' => $status,
				'comment' => $comment,
			]);
			$json['success'] = $this->language->get('text_success');
			$json['redirect'] = html_entity_decode($this->url->link('extension/termopab/callback_request', 'user_token=' . $this->session->data['user_token']), ENT_QUOTES, 'UTF-8');
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
				$this->response->redirect($this->url->link('extension/termopab/callback_request.form', 'user_token=' . $this->session->data['user_token'] . '&callback_request_id=' . $callback_request_id));
				return;
			}
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
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "callback_request` (
				`callback_request_id` int(11) NOT NULL AUTO_INCREMENT,
				`name` varchar(255) NOT NULL,
				`phone` varchar(64) NOT NULL,
				`status` tinyint(1) NOT NULL DEFAULT 0,
				`comment` text DEFAULT NULL,
				`ip` varchar(40) DEFAULT NULL,
				`user_agent` varchar(255) DEFAULT NULL,
				`date_added` datetime NOT NULL,
				PRIMARY KEY (`callback_request_id`),
				KEY `status` (`status`),
				KEY `date_added` (`date_added`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
		];

		try {
			foreach ($sql as $q) {
				$this->db->query($q);
			}
			$this->session->data['success'] = $this->language->get('text_install_success');
		} catch (\Throwable $e) {
			$this->session->data['error'] = $this->language->get('text_install_error') . ' ' . $e->getMessage();
		}
	}
}
