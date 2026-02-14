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

		$filter_category_id = (int)($this->request->get['category_id'] ?? 0);
		$page = (int)($this->request->get['page'] ?? 1);
		$limit = (int)($this->config->get('config_pagination') ?: 12);
		$start = ($page - 1) * $limit;

		$this->load->model('extension/termopab/brewery_review');
		$this->load->model('tool/image');

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
				'href'   => $base_url . '&category_id=' . (int)$cat['brewery_review_category_id'],
				'active' => $filter_category_id === (int)$cat['brewery_review_category_id'],
			];
		}

		$data['projects'] = [];
		$width = (int)$this->config->get('config_image_content_width') ?: 300;
		$height = (int)$this->config->get('config_image_content_height') ?: 300;
		$placeholder = $this->model_tool_image->resize('placeholder.png', $width, $height);

		foreach ($results as $row) {
			$img = $row['image'] && is_file(DIR_IMAGE . html_entity_decode($row['image'], ENT_QUOTES, 'UTF-8'))
				? $this->model_tool_image->resize($row['image'], $width, $height) : $placeholder;
			$data['projects'][] = [
				'project_id'   => $row['brewery_review_id'],
				'title'        => $row['title'] ?: ('Review #' . $row['brewery_review_id']),
				'description'  => $row['description'] ?: '',
				'image'        => $img,
				'href'         => $this->url->link('extension/termopab/brewery_review.info', 'language=' . $this->config->get('config_language') . '&brewery_review_id=' . $row['brewery_review_id']),
			];
		}

		$pagination_url = $base_url . ($filter_category_id > 0 ? '&category_id=' . $filter_category_id : '') . '&page={page}';
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
		if (!$brewery_review_id) {
			$this->response->redirect($this->url->link('extension/termopab/brewery_review', 'language=' . $this->config->get('config_language')));
			return;
		}

		$this->load->model('extension/termopab/brewery_review');
		$this->load->model('tool/image');

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
		$data['article'] = $project['article'];
		$data['back_list'] = $this->url->link('extension/termopab/brewery_review', 'language=' . $this->config->get('config_language'));
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
		$gallery = $this->model_extension_termopab_brewery_review->getBreweryReviewImages($brewery_review_id);
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
