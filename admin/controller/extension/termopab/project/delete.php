<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Project;

class Delete extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->load->language('extension/termopab/project/list');

		if (!$this->user->hasPermission('modify', 'extension/termopab/project')) {
			$this->session->data['error'] = $this->language->get('error_permission');
		} else {
			$project_id = (int)($this->request->get['project_id'] ?? 0);
			if ($project_id) {
				$this->load->model('extension/termopab/project');
				$this->model_extension_termopab_project->deleteProject($project_id);
				$this->session->data['success'] = $this->language->get('text_success');
			}
		}

		$this->response->redirect($this->url->link('extension/termopab/project', 'user_token=' . $this->session->data['user_token']));
	}
}
