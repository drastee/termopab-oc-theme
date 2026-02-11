<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Project;

class Info extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		$this->load->language('extension/termopab/project/info');

		$project_id = (int)($this->request->get['project_id'] ?? 0);
		if (!$project_id) {
			$this->response->redirect($this->url->link('extension/termopab/project', 'language=' . $this->config->get('config_language')));
			return;
		}

		$this->load->model('extension/termopab/project');
		$this->load->model('tool/image');

		$project = $this->model_extension_termopab_project->getProject($project_id);
		if (!$project) {
			$this->response->redirect($this->url->link('extension/termopab/project', 'language=' . $this->config->get('config_language')));
			return;
		}

		$this->document->setTitle($project['meta_title'] ?: $project['title']);
		if ($project['meta_description']) {
			$this->document->setDescription($project['meta_description']);
		}
		if ($project['meta_keyword']) {
			$this->document->setKeywords($project['meta_keyword']);
		}

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];
		$this->load->language('extension/termopab/project/list');
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/project', 'language=' . $this->config->get('config_language'))
		];
		$data['breadcrumbs'][] = [
			'text' => $project['title'],
			'href' => $this->url->link('extension/termopab/project/info', 'language=' . $this->config->get('config_language') . '&project_id=' . $project_id)
		];

		$data['heading'] = $project['heading'] ?? '';
		$data['title'] = $project['title'];
		$data['description'] = $project['description'];
		$data['article'] = $project['article'];
		$data['back_list'] = $this->url->link('extension/termopab/project', 'language=' . $this->config->get('config_language'));
		$data['text_back_list'] = $this->language->get('text_back_list');
		$data['text_gallery'] = $this->language->get('text_gallery');

		$image_base = $this->config->get('config_url') . 'image/';
		$width = (int)$this->config->get('config_image_content_width') ?: 800;
		$height = (int)$this->config->get('config_image_content_height') ?: 600;
		$thumb_w = (int)$this->config->get('config_image_thumb_width') ?: 300;
		$thumb_h = (int)$this->config->get('config_image_thumb_height') ?: 300;

		if (!empty($project['image']) && is_file(DIR_IMAGE . html_entity_decode($project['image'], ENT_QUOTES, 'UTF-8'))) {
			$data['image'] = $this->model_tool_image->resize($project['image'], $width, $height);
		} else {
			$data['image'] = '';
		}
		if (!empty($project['logo']) && is_file(DIR_IMAGE . html_entity_decode($project['logo'], ENT_QUOTES, 'UTF-8'))) {
			$data['logo'] = $this->model_tool_image->resize($project['logo'], 200, 100);
		} else {
			$data['logo'] = '';
		}
		$data['video_url'] = '';
		if (!empty($project['video'])) {
			$video_path = str_starts_with($project['video'], 'catalog/') ? $project['video'] : 'catalog/' . ltrim($project['video'], '/');
			$data['video_url'] = $image_base . $video_path;
		}
		$data['project_images'] = [];
		$gallery = $this->model_extension_termopab_project->getProjectImages($project_id);
		foreach ($gallery as $row) {
			$img = $row['image'] ?? '';
			if ($img && is_file(DIR_IMAGE . html_entity_decode($img, ENT_QUOTES, 'UTF-8'))) {
				$data['project_images'][] = [
					'image' => $this->model_tool_image->resize($img, $width, $height),
					'thumb' => $this->model_tool_image->resize($img, $thumb_w, $thumb_h),
				];
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/project/info', $data));
	}
}
