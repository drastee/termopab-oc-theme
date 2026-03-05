<?php
namespace Opencart\Catalog\Controller\Extension\Termopab;

/**
 * Обзори пивоварень (каталог). Маршрут: extension/termopab/brewery_review
 * index() — список, info() — картка одного огляду.
 */
class BreweryReview extends \Opencart\System\Engine\Controller {

	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		$this->load->language('extension/termopab/brewery_review/list');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/brewery_review', 'language=' . $this->config->get('config_language'))
		];

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_reviews'] = $this->language->get('text_reviews');
		$data['text_read_more'] = $this->language->get('text_read_more');
		$data['text_back_list'] = $this->language->get('text_back_list');
		$data['text_empty'] = $this->language->get('text_empty');
		$data['text_filter_all'] = $this->language->get('text_filter_all');

		$filter_category_id = (int)($this->request->get['category_id'] ?? ($this->request->get['brewery_review_category_id'] ?? 0));

		if (!$filter_category_id && isset($this->request->get['_route_'])) {
			$route = (string)$this->request->get['_route_'];
			$route = trim($route, '/');

			if ($route !== '' && str_contains($route, '/')) {
				$parts = explode('/', $route);
				$keyword = (string)end($parts);

				if ($keyword !== '') {
					$query = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "seo_url` WHERE `store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `key` = 'brewery_review_category_id' AND `keyword` = '" . $this->db->escape($keyword) . "' LIMIT 1");
					if (!empty($query->row['value'])) {
						$filter_category_id = (int)$query->row['value'];
						$this->request->get['category_id'] = $filter_category_id;
					}
				}
			}
		}
		$page = (int)($this->request->get['page'] ?? 1);
		$limit = (int)($this->config->get('config_pagination') ?: 12);
		$start = ($page - 1) * $limit;

		$this->load->model('extension/termopab/brewery_review');

		$filter_data = ['start' => $start, 'limit' => $limit];
		if ($filter_category_id > 0) {
			$filter_data['filter_brewery_review_category_id'] = $filter_category_id;
		}

		$total = $this->model_extension_termopab_brewery_review->getTotalBreweryReviews($filter_data);
		$results = $this->model_extension_termopab_brewery_review->getBreweryReviews($filter_data);

		$base_url = $this->url->link('extension/termopab/brewery_review', 'language=' . $this->config->get('config_language'));
		$data['filter_categories'] = [];
		$data['filter_categories'][] = [
			'name'   => $this->language->get('text_filter_all'),
			'href'   => $base_url,
			'active' => $filter_category_id === 0,
		];
		foreach ($this->model_extension_termopab_brewery_review->getBreweryReviewCategories() as $cat) {
			$data['filter_categories'][] = [
				'name'   => $cat['title'] ?: ('ID ' . $cat['brewery_review_category_id']),
				'href'   => $this->url->link('extension/termopab/brewery_review', 'language=' . $this->config->get('config_language') . '&brewery_review_category_id=' . (int)$cat['brewery_review_category_id']),
				'active' => $filter_category_id === (int)$cat['brewery_review_category_id'],
			];
		}

		$data['projects'] = [];
		$image_base = rtrim((string)$this->config->get('config_url'), '/') . '/image/';
		$placeholder = $image_base . 'placeholder.png';

		foreach ($results as $row) {
			$image_path = html_entity_decode((string)($row['image'] ?? ''), ENT_QUOTES, 'UTF-8');
			$img = $image_path && is_file(DIR_IMAGE . $image_path) ? $image_base . ltrim($image_path, '/') : $placeholder;
			$data['projects'][] = [
				'project_id'   => $row['brewery_review_id'],
				'title'        => $row['title'] ?: ('Review #' . $row['brewery_review_id']),
				'description'  => $row['description'] ?: '',
				'image'        => $img,
				'href'         => $this->url->link('extension/termopab/brewery_review.info', 'language=' . $this->config->get('config_language') . '&brewery_review_id=' . $row['brewery_review_id']),
			];
		}

		$pagination_url = $base_url . ($filter_category_id > 0 ? '&brewery_review_category_id=' . $filter_category_id : '') . '&page={page}';
		$data['pagination_data'] = $this->buildPaginationData($total, $page, $limit, $pagination_url);

		$data['header'] = $this->load->controller('common/header');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/brewery_review/list', $data));
	}

	public function info(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		$this->load->language('extension/termopab/brewery_review/info');

		$brewery_review_id = (int)($this->request->get['brewery_review_id'] ?? 0);

		if (!$brewery_review_id && isset($this->request->get['_route_'])) {
			$route = (string)$this->request->get['_route_'];
			$route = trim($route, '/');

			if ($route !== '') {
				$parts = explode('/', $route);
				$keyword = (string)end($parts);

				if ($keyword !== '') {
					$query = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "seo_url` WHERE `store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `key` = 'brewery_review_id' AND `keyword` = '" . $this->db->escape($keyword) . "' LIMIT 1");
					if (!empty($query->row['value'])) {
						$brewery_review_id = (int)$query->row['value'];
						$this->request->get['brewery_review_id'] = $brewery_review_id;
					}
				}
			}
		}
		if (!$brewery_review_id) {
			$this->response->redirect($this->url->link('extension/termopab/brewery_review', 'language=' . $this->config->get('config_language')));
			return;
		}

		$this->load->model('extension/termopab/brewery_review');

		$project = $this->model_extension_termopab_brewery_review->getBreweryReview($brewery_review_id);
		if (!$project) {
			$this->response->redirect($this->url->link('extension/termopab/brewery_review', 'language=' . $this->config->get('config_language')));
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
		$this->load->language('extension/termopab/brewery_review/list');
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/brewery_review', 'language=' . $this->config->get('config_language'))
		];
		$data['breadcrumbs'][] = [
			'text' => $project['title'],
			'href' => $this->url->link('extension/termopab/brewery_review.info', 'language=' . $this->config->get('config_language') . '&brewery_review_id=' . $brewery_review_id)
		];

		$data['heading'] = $project['heading'] ?? '';
		$data['title'] = $project['title'];
		$data['description'] = $project['description'];
		$data['article'] = html_entity_decode($project['article'], ENT_QUOTES, 'UTF-8');

		$data['back_list'] = $this->url->link('extension/termopab/brewery_review', 'language=' . $this->config->get('config_language'));
		$data['text_back_list'] = $this->language->get('text_back_list');
		$data['text_gallery'] = $this->language->get('text_gallery');

		$image_base = rtrim((string)$this->config->get('config_url'), '/') . '/image/';

		$main_image_path = html_entity_decode((string)($project['image'] ?? ''), ENT_QUOTES, 'UTF-8');
		if ($main_image_path && is_file(DIR_IMAGE . $main_image_path)) {
			$data['image'] = $image_base . ltrim($main_image_path, '/');
		} else {
			$data['image'] = '';
		}
		$logo_path = html_entity_decode((string)($project['logo'] ?? ''), ENT_QUOTES, 'UTF-8');
		if ($logo_path && is_file(DIR_IMAGE . $logo_path)) {
			$data['logo'] = $image_base . ltrim($logo_path, '/');
		} else {
			$data['logo'] = '';
		}
		$data['video_url'] = '';
		if (!empty($project['video'])) {
			$video_path = str_starts_with($project['video'], 'catalog/') ? $project['video'] : 'catalog/' . ltrim($project['video'], '/');
			$data['video_url'] = $image_base . $video_path;
		}
		$data['project_images'] = [];
		$gallery = $this->model_extension_termopab_brewery_review->getBreweryReviewImages($brewery_review_id);
		foreach ($gallery as $row) {
			$img = $row['image'] ?? '';
			$img_path = html_entity_decode((string)$img, ENT_QUOTES, 'UTF-8');
			if ($img_path && is_file(DIR_IMAGE . $img_path)) {
				$data['project_images'][] = [
					'image' => $image_base . ltrim($img_path, '/'),
					'thumb' => $image_base . ltrim($img_path, '/'),
				];
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/brewery_review/info', $data));
	}

	private function buildPaginationData(int $total, int $page, int $limit, string $url_template): array {
		$num_pages = $limit > 0 ? (int)ceil($total / $limit) : 1;
		$page = max(1, min($page, $num_pages ?: 1));

		$prev_url = $page > 1 ? str_replace('{page}', (string)($page - 1), $url_template) : '';
		$next_url = $page < $num_pages ? str_replace('{page}', (string)($page + 1), $url_template) : '';

		$pages = [];
		if ($num_pages <= 1) {
		} elseif ($num_pages <= 7) {
			for ($i = 1; $i <= $num_pages; $i++) {
				$pages[] = ['num' => $i, 'url' => str_replace('{page}', (string)$i, $url_template), 'active' => $i === $page];
			}
		} else {
			$pages[] = ['num' => 1, 'url' => str_replace('{page}', '1', $url_template), 'active' => $page === 1];
			if ($page > 3) {
				$pages[] = ['num' => null, 'url' => null, 'dots' => true];
			}
			$start = max(2, $page - 1);
			$end = min($num_pages - 1, $page + 1);
			for ($i = $start; $i <= $end; $i++) {
				$pages[] = ['num' => $i, 'url' => str_replace('{page}', (string)$i, $url_template), 'active' => $i === $page];
			}
			if ($page < $num_pages - 2) {
				$pages[] = ['num' => null, 'url' => null, 'dots' => true];
			}
			if ($num_pages > 1) {
				$pages[] = ['num' => $num_pages, 'url' => str_replace('{page}', (string)$num_pages, $url_template), 'active' => $page === $num_pages];
			}
		}

		return [
			'prev_url' => $prev_url,
			'next_url' => $next_url,
			'pages'    => $pages,
		];
	}
}
