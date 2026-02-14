<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class ProjectsSlider extends \Opencart\System\Engine\Controller {
	public function index(array $setting): string {
		$project_ids = $setting['project_id'] ?? [];
		if (!is_array($project_ids)) {
			$project_ids = $project_ids !== '' ? [(int)$project_ids] : [];
		}
		$project_ids = array_filter(array_map('intval', $project_ids));
		if (empty($project_ids)) {
			return '';
		}

		$this->load->model('extension/termopab/project');
		$this->load->model('tool/image');

		$language_id = (int)$this->config->get('config_language_id');
		$rows = $this->model_extension_termopab_project->getProjectsByIds($project_ids, $language_id);
		if (empty($rows)) {
			return '';
		}

		$logo_width = 200;
		$logo_height = 200;
		$placeholder = $this->model_tool_image->resize('placeholder.png', $logo_width, $logo_height);

		$data['projects'] = [];
		foreach ($rows as $row) {
			$logo = '';
			if (!empty($row['logo']) && is_file(DIR_IMAGE . html_entity_decode($row['logo'], ENT_QUOTES, 'UTF-8'))) {
				$logo = $this->model_tool_image->resize($row['logo'], $logo_width, $logo_height);
			} else {
				$logo = $placeholder;
			}
			$data['projects'][] = [
				'title' => $row['title'] ?: ('Project #' . $row['project_id']),
				'logo'  => $logo,
				'href'  => $this->url->link('extension/termopab/project.info', 'language=' . $this->config->get('config_language') . '&project_id=' . (int)$row['project_id']),
			];
		}

		$data['slider_nav'] = true;

		$title = $setting['title'] ?? [];
		$link_text = $setting['link_text'] ?? [];
		if (!is_array($title)) {
			$title = $title !== '' ? [$language_id => $title] : [];
		}
		if (!is_array($link_text)) {
			$link_text = $link_text !== '' ? [$language_id => $link_text] : [];
		}
		$data['title'] = $title[$language_id] ?? '';
		$data['link_text'] = $link_text[$language_id] ?? '';
		$data['link_href'] = $this->url->link('extension/termopab/project', 'language=' . $this->config->get('config_language'));
		$data['type'] = '';

		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		return $this->load->view('extension/termopab/module/projects_slider', $data);
	}
}
