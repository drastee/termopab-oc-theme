<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Module;

class BreweryReviewsSlider extends \Opencart\System\Engine\Controller {

	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');
		$this->load->language('extension/termopab/module/brewery_reviews_slider');

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
			'href' => $this->url->link('extension/termopab/module/brewery_reviews_slider', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''))
		];

		$data['save'] = $this->url->link('extension/termopab/module/brewery_reviews_slider.save', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''));
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$module_info = [];
		if ($module_id) {
			$this->load->model('setting/module');
			$module_info = $this->model_setting_module->getModule($module_id);
		}

		$data['name'] = $module_info['name'] ?? '';
		$data['status'] = $module_info['status'] ?? 1;
		$data['module_id'] = $module_id;

		$data['title'] = $module_info['title'] ?? [];
		if (!is_array($data['title'])) {
			$data['title'] = $data['title'] !== '' ? [($this->config->get('config_language_id') ?: 1) => $data['title']] : [];
		}
		$data['link_text'] = $module_info['link_text'] ?? [];
		if (!is_array($data['link_text'])) {
			$data['link_text'] = $data['link_text'] !== '' ? [($this->config->get('config_language_id') ?: 1) => $data['link_text']] : [];
		}

		$data['use_latest'] = (int)($module_info['use_latest'] ?? 1);
		$data['limit'] = (int)($module_info['limit'] ?? 10);
		if ($data['limit'] < 1) {
			$data['limit'] = 10;
		}

		$selected_ids = $module_info['brewery_review_id'] ?? [];
		if (!is_array($selected_ids)) {
			$selected_ids = $selected_ids !== '' ? [(int)$selected_ids] : [];
		}
		$data['selected_brewery_review_ids'] = array_map('intval', $selected_ids);

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('extension/termopab/brewery_review');
		$all_reviews = $this->model_extension_termopab_brewery_review->getBreweryReviews(['sort' => 'b.sort_order', 'order' => 'ASC']);
		$data['brewery_reviews'] = [];
		foreach ($all_reviews as $r) {
			$data['brewery_reviews'][] = [
				'brewery_review_id' => (int)$r['brewery_review_id'],
				'title'             => $r['title'] ?: ('Review #' . $r['brewery_review_id']),
				'selected'          => in_array((int)$r['brewery_review_id'], $data['selected_brewery_review_ids'], true),
			];
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/module/brewery_reviews_slider', $data));
	}

	public function save(): void {
		$this->load->language('extension/termopab/module/brewery_reviews_slider');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/module/brewery_reviews_slider')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$name = $this->request->post['name'] ?? '';
		if (oc_strlen($name) < 3 || oc_strlen($name) > 64) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (!$json) {
			$use_latest = (int)($this->request->post['use_latest'] ?? 1);
			$limit = (int)($this->request->post['limit'] ?? 10);
			if ($limit < 1) {
				$limit = 10;
			}

			$brewery_review_id = $this->request->post['brewery_review_id'] ?? [];
			if (!is_array($brewery_review_id)) {
				$brewery_review_id = [];
			}
			$brewery_review_id = array_values(array_unique(array_map('intval', array_filter($brewery_review_id))));

			$title = [];
			foreach ($this->request->post['title'] ?? [] as $lang_id => $val) {
				$title[(int)$lang_id] = trim((string)$val);
			}
			$link_text = [];
			foreach ($this->request->post['link_text'] ?? [] as $lang_id => $val) {
				$link_text[(int)$lang_id] = trim((string)$val);
			}

			$this->load->model('setting/module');

			$post = [
				'name'                => $name,
				'title'               => $title,
				'link_text'           => $link_text,
				'use_latest'          => $use_latest,
				'limit'               => $limit,
				'brewery_review_id'   => $brewery_review_id,
				'status'              => (int)($this->request->post['status'] ?? 0),
			];

			$module_id = (int)($this->request->post['module_id'] ?? 0);
			if (!$module_id) {
				$json['module_id'] = $this->model_setting_module->addModule('termopab.brewery_reviews_slider', $post);
			} else {
				$this->model_setting_module->editModule($module_id, $post);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
