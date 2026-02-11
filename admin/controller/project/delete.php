<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Project;

class Delete extends \Opencart\System\Engine\Controller {
	public function index(): void {
		if (method_exists($this->language, 'addPath')) {
			$this->language->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/language/');
		}
		$this->load->language('extension/termopab/project/list');

		if (!$this->user->hasPermission('modify', 'extension/termopab/project/delete')) {
			$this->session->data['error'] = $this->language->get('error_permission');
		} else {
			$project_id = (int)($this->request->get['project_id'] ?? 0);
			if ($project_id) {
				$this->ensureProjectModel();
				$this->model_extension_termopab_project->deleteProject($project_id);
				$this->session->data['success'] = $this->language->get('text_success');
			}
		}

		$this->response->redirect($this->url->link('extension/termopab/project', 'user_token=' . $this->session->data['user_token']));
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
