<?php
namespace Opencart\Admin\Controller\Extension\Termopab;

/**
 * Категорії оглядів пивоварень (плоский список). Маршрут: extension/termopab/brewery_review_category
 */
class BreweryReviewCategory extends \Opencart\System\Engine\Controller {

	public function index(): void {
		$this->addPaths();
		$this->load->language('extension/termopab/brewery_review_category/list');
		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		$this->load->language('extension/termopab/brewery_review/list');
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/brewery_review', 'user_token=' . $this->session->data['user_token'])
		];
		$this->load->language('extension/termopab/brewery_review_category/list');
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/brewery_review_category', 'user_token=' . $this->session->data['user_token'])
		];

		$data['add'] = $this->url->link('extension/termopab/brewery_review_category.form', 'user_token=' . $this->session->data['user_token']);
		$data['user_token'] = $this->session->data['user_token'];
		$data['success'] = $this->session->data['success'] ?? '';
		$data['error_warning'] = $this->session->data['error'] ?? '';
		unset($this->session->data['success'], $this->session->data['error']);

		$this->load->model('extension/termopab/brewery_review_category');
		$data['categories'] = [];
		foreach ($this->model_extension_termopab_brewery_review_category->getCategories() as $row) {
			$data['categories'][] = [
				'brewery_review_category_id' => $row['brewery_review_category_id'],
				'title'                      => $row['title'] ?: ('ID ' . $row['brewery_review_category_id']),
				'sort_order'                 => $row['sort_order'],
				'status'                     => $row['status'],
				'edit'                       => $this->url->link('extension/termopab/brewery_review_category.form', 'user_token=' . $this->session->data['user_token'] . '&brewery_review_category_id=' . $row['brewery_review_category_id']),
				'delete'                     => $this->url->link('extension/termopab/brewery_review_category.delete', 'user_token=' . $this->session->data['user_token'] . '&brewery_review_category_id=' . $row['brewery_review_category_id']),
			];
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/termopab/brewery_review_category/list', $data));
	}

	public function form(): void {
		$this->addPaths();
		$this->load->language('extension/termopab/brewery_review_category/form');
		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];
		$this->load->language('extension/termopab/brewery_review/list');
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/brewery_review', 'user_token=' . $this->session->data['user_token'])
		];
		$this->load->language('extension/termopab/brewery_review_category/list');
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/brewery_review_category', 'user_token=' . $this->session->data['user_token'])
		];

		$brewery_review_category_id = (int)($this->request->get['brewery_review_category_id'] ?? 0);
		$data['save'] = $this->url->link('extension/termopab/brewery_review_category.save', 'user_token=' . $this->session->data['user_token'] . ($brewery_review_category_id ? '&brewery_review_category_id=' . $brewery_review_category_id : ''));
		$data['cancel'] = $this->url->link('extension/termopab/brewery_review_category', 'user_token=' . $this->session->data['user_token']);
		$data['brewery_review_category_id'] = $brewery_review_category_id;
		$data['user_token'] = $this->session->data['user_token'];
		$data['success'] = $this->session->data['success'] ?? '';
		$data['error_warning'] = $this->session->data['error_warning'] ?? '';
		unset($this->session->data['success'], $this->session->data['error_warning']);

		$this->load->model('extension/termopab/brewery_review_category');
		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();
		if ($brewery_review_category_id) {
			$category = $this->model_extension_termopab_brewery_review_category->getCategory($brewery_review_category_id);
			$descriptions = $this->model_extension_termopab_brewery_review_category->getCategoryDescriptions($brewery_review_category_id);
			$seo_keywords = $this->model_extension_termopab_brewery_review_category->getCategorySeoKeywords($brewery_review_category_id);
		} else {
			$category = ['sort_order' => 0, 'status' => 1];
			$descriptions = [];
			$seo_keywords = [];
		}

		$data['sort_order'] = $category['sort_order'] ?? 0;
		$data['status'] = $category['status'] ?? 1;
		$data['category_description'] = [];
		foreach ($data['languages'] as $lang) {
			$d = $descriptions[$lang['language_id']] ?? [];
			$data['category_description'][$lang['language_id']] = ['title' => $d['title'] ?? ''];
		}

		$data['seo_keyword'] = [];
		foreach ($data['languages'] as $lang) {
			$data['seo_keyword'][$lang['language_id']] = (string)($seo_keywords[$lang['language_id']] ?? '');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('extension/termopab/brewery_review_category/form', $data));
	}

	public function save(): void {
		$this->addPaths();
		$this->load->language('extension/termopab/brewery_review_category/form');

		$json = [];
		if (!$this->user->hasPermission('modify', 'extension/termopab/brewery_review')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$brewery_review_category_id = (int)($this->request->post['brewery_review_category_id'] ?? 0);
		$category_description = $this->request->post['category_description'] ?? [];
		$first_title = '';
		foreach ($category_description as $d) {
			$t = trim((string)($d['title'] ?? ''));
			if ($t !== '' && $first_title === '') {
				$first_title = $t;
				break;
			}
		}
		if ($first_title === '' && !empty($category_description)) {
			$first_title = trim((string)reset($category_description)['title'] ?? '');
		}
		if (oc_strlen($first_title) < 1 || oc_strlen($first_title) > 255) {
			$json['error']['title'] = $this->language->get('error_title');
		}

		if (empty($json['error'])) {
			$seo_keyword = $this->request->post['seo_keyword'] ?? [];
			$data = [
				'sort_order'          => (int)($this->request->post['sort_order'] ?? 0),
				'status'              => (int)($this->request->post['status'] ?? 1),
				'seo_keyword'          => is_array($seo_keyword) ? $seo_keyword : [],
				'category_description' => [],
			];
			foreach ($category_description as $language_id => $desc) {
				$data['category_description'][(int)$language_id] = ['title' => trim((string)($desc['title'] ?? ''))];
			}
			$this->load->model('extension/termopab/brewery_review_category');
			if (!$brewery_review_category_id) {
				$brewery_review_category_id = $this->model_extension_termopab_brewery_review_category->addCategory($data);
				$json['redirect'] = $this->url->link('extension/termopab/brewery_review_category.form', 'user_token=' . $this->session->data['user_token'] . '&brewery_review_category_id=' . $brewery_review_category_id);
			} else {
				$this->model_extension_termopab_brewery_review_category->editCategory($brewery_review_category_id, $data);
				$json['redirect'] = $this->url->link('extension/termopab/brewery_review_category', 'user_token=' . $this->session->data['user_token']);
			}
			$json['success'] = $this->language->get('text_success');
		}

		$is_ajax = !empty($this->request->server['HTTP_X_REQUESTED_WITH']) && strtolower($this->request->server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
		if (!$is_ajax) {
			if (!empty($json['redirect'])) {
				$this->session->data['success'] = $json['success'] ?? '';
				$this->response->redirect($json['redirect']);
				return;
			}
			if (!empty($json['error'])) {
				$this->session->data['error_warning'] = $json['error']['warning'] ?? implode(' ', $json['error']);
				$this->response->redirect($this->url->link('extension/termopab/brewery_review_category.form', 'user_token=' . $this->session->data['user_token'] . ($brewery_review_category_id ? '&brewery_review_category_id=' . $brewery_review_category_id : '')));
				return;
			}
		}
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function delete(): void {
		$this->addPaths();
		$this->load->language('extension/termopab/brewery_review_category/list');
		if (!$this->user->hasPermission('modify', 'extension/termopab/brewery_review')) {
			$this->session->data['error'] = $this->language->get('error_permission');
		} else {
			$id = (int)($this->request->get['brewery_review_category_id'] ?? 0);
			if ($id) {
				$this->load->model('extension/termopab/brewery_review_category');
				$this->model_extension_termopab_brewery_review_category->deleteCategory($id);
				$this->session->data['success'] = $this->language->get('text_success');
			}
		}
		$this->response->redirect($this->url->link('extension/termopab/brewery_review_category', 'user_token=' . $this->session->data['user_token']));
	}

	protected function addPaths(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');
		if (method_exists($this->language, 'addPath')) {
			$this->language->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/language/');
		}
	}
}
