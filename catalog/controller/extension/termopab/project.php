<?php
namespace Opencart\Catalog\Controller\Extension\Termopab;

/**
 * Projects list. Route: extension/termopab/project
 */
class Project extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		$this->load->language('extension/termopab/project/list');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/project', 'language=' . $this->config->get('config_language'))
		];

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_projects'] = $this->language->get('text_projects');
		$data['text_read_more'] = $this->language->get('text_read_more');
		$data['text_back_list'] = $this->language->get('text_back_list');
		$data['text_empty'] = $this->language->get('text_empty');

		$page = (int)($this->request->get['page'] ?? 1);
		$limit = (int)($this->config->get('config_pagination') ?: 12);
		$start = ($page - 1) * $limit;

		$this->load->model('extension/termopab/project');
		$this->load->model('tool/image');

		$total = $this->model_extension_termopab_project->getTotalProjects();
		$results = $this->model_extension_termopab_project->getProjects(['start' => $start, 'limit' => $limit]);

		$data['projects'] = [];
		$width = (int)$this->config->get('config_image_content_width') ?: 300;
		$height = (int)$this->config->get('config_image_content_height') ?: 300;
		$placeholder = $this->model_tool_image->resize('placeholder.png', $width, $height);

		foreach ($results as $row) {
			$img = $row['image'] && is_file(DIR_IMAGE . html_entity_decode($row['image'], ENT_QUOTES, 'UTF-8'))
				? $this->model_tool_image->resize($row['image'], $width, $height) : $placeholder;
			$data['projects'][] = [
				'project_id'   => $row['project_id'],
				'title'        => $row['title'] ?: ('Project #' . $row['project_id']),
				'description'  => $row['description'] ?: '',
				'image'        => $img,
				'href'         => $this->url->link('extension/termopab/project/info', 'language=' . $this->config->get('config_language') . '&project_id=' . $row['project_id']),
			];
		}

		$data['pagination'] = $this->load->controller('common/pagination', [
			'total' => $total,
			'page'  => $page,
			'limit' => $limit,
			'url'   => $this->url->link('extension/termopab/project', 'language=' . $this->config->get('config_language') . '&page={page}')
		]);

		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/project/list', $data));
	}
}
