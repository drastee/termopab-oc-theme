<?php
namespace Opencart\Catalog\Controller\Extension\Termopab;

/**
 * Проекти (каталог). Маршрут: extension/termopab/news
 * index() — список, info() — картка одного проєкту.
 */
class News extends \Opencart\System\Engine\Controller {

	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		$this->load->language('extension/termopab/news/list');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/news', 'language=' . $this->config->get('config_language'))
		];

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_newss'] = $this->language->get('text_newss');
		$data['text_read_more'] = $this->language->get('text_read_more');
		$data['text_back_list'] = $this->language->get('text_back_list');
		$data['text_empty'] = $this->language->get('text_empty');

		$page = (int)($this->request->get['page'] ?? 1);
		$limit = (int)($this->config->get('config_pagination') ?: 12);
		$start = ($page - 1) * $limit;

		$this->load->model('extension/termopab/news');

		$total = $this->model_extension_termopab_news->getTotalNewss();
		$results = $this->model_extension_termopab_news->getNewss(['start' => $start, 'limit' => $limit]);

		$data['newss'] = [];
		$image_base = rtrim((string)$this->config->get('config_url'), '/') . '/image/';
		$placeholder = $image_base . 'placeholder.png';

		foreach ($results as $row) {
			$image_path = html_entity_decode((string)($row['image'] ?? ''), ENT_QUOTES, 'UTF-8');
			$img = $image_path && is_file(DIR_IMAGE . $image_path) ? $image_base . ltrim($image_path, '/') : $placeholder;
			$data['newss'][] = [
				'news_id'   => $row['news_id'],
				'title'        => $row['title'] ?: ('News #' . $row['news_id']),
				'description'  => $row['description'] ?: '',
				'image'        => $img,
				'href'         => $this->url->link('extension/termopab/news.info', 'language=' . $this->config->get('config_language') . '&news_id=' . $row['news_id']),
			];
		}

		$pagination_url = $this->url->link('extension/termopab/news', 'language=' . $this->config->get('config_language') . '&page={page}');
		$data['pagination_data'] = $this->buildPaginationData($total, $page, $limit, $pagination_url);

		$data['header'] = $this->load->controller('common/header');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/news/list', $data));
	}

	public function info(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		$this->load->language('extension/termopab/news/info');

		$news_id = (int)($this->request->get['news_id'] ?? 0);
		if (!$news_id) {
			$this->response->redirect($this->url->link('extension/termopab/news', 'language=' . $this->config->get('config_language')));
			return;
		}

		$this->load->model('extension/termopab/news');

		$news = $this->model_extension_termopab_news->getNews($news_id);
		if (!$news) {
			$this->response->redirect($this->url->link('extension/termopab/news', 'language=' . $this->config->get('config_language')));
			return;
		}

		$this->document->setTitle($news['meta_title'] ?: $news['title']);
		if ($news['meta_description']) {
			$this->document->setDescription($news['meta_description']);
		}
		if ($news['meta_keyword']) {
			$this->document->setKeywords($news['meta_keyword']);
		}

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];
		$this->load->language('extension/termopab/news/list');
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/news', 'language=' . $this->config->get('config_language'))
		];
		$data['breadcrumbs'][] = [
			'text' => $news['title'],
			'href' => $this->url->link('extension/termopab/news.info', 'language=' . $this->config->get('config_language') . '&news_id=' . $news_id)
		];

		$data['heading'] = $news['heading'] ?? '';
		$data['title'] = $news['title'];
		$data['description'] = $news['description'];
		$data['article'] = html_entity_decode($news['article'], ENT_QUOTES, 'UTF-8');

		$data['back_list'] = $this->url->link('extension/termopab/news', 'language=' . $this->config->get('config_language'));
		$data['text_back_list'] = $this->language->get('text_back_list');
		$data['text_gallery'] = $this->language->get('text_gallery');

		$image_base = rtrim((string)$this->config->get('config_url'), '/') . '/image/';

		$main_image_path = html_entity_decode((string)($news['image'] ?? ''), ENT_QUOTES, 'UTF-8');
		if ($main_image_path && is_file(DIR_IMAGE . $main_image_path)) {
			$data['image'] = $image_base . ltrim($main_image_path, '/');
		} else {
			$data['image'] = '';
		}
		$logo_path = html_entity_decode((string)($news['logo'] ?? ''), ENT_QUOTES, 'UTF-8');
		if ($logo_path && is_file(DIR_IMAGE . $logo_path)) {
			$data['logo'] = $image_base . ltrim($logo_path, '/');
		} else {
			$data['logo'] = '';
		}
		$data['video_url'] = '';
		if (!empty($news['video'])) {
			$video_path = str_starts_with($news['video'], 'catalog/') ? $news['video'] : 'catalog/' . ltrim($news['video'], '/');
			$data['video_url'] = $image_base . $video_path;
		}
		$data['news_images'] = [];
		$gallery = $this->model_extension_termopab_news->getNewsImages($news_id);
		foreach ($gallery as $row) {
			$img = $row['image'] ?? '';
			$img_path = html_entity_decode((string)$img, ENT_QUOTES, 'UTF-8');
			if ($img_path && is_file(DIR_IMAGE . $img_path)) {
				$data['news_images'][] = [
					'image' => $image_base . ltrim($img_path, '/'),
					'thumb' => $image_base . ltrim($img_path, '/'),
				];
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/news/info', $data));
	}

	/**
	 * Build pagination data for the theme pagination component (prev, pages with dots, next).
	 */
	private function buildPaginationData(int $total, int $page, int $limit, string $url_template): array {
		$num_pages = $limit > 0 ? (int)ceil($total / $limit) : 1;
		$page = max(1, min($page, $num_pages ?: 1));

		$prev_url = $page > 1 ? str_replace('{page}', (string)($page - 1), $url_template) : '';
		$next_url = $page < $num_pages ? str_replace('{page}', (string)($page + 1), $url_template) : '';

		$pages = [];
		if ($num_pages <= 1) {
			// one page — show nothing or just current
		} elseif ($num_pages <= 7) {
			for ($i = 1; $i <= $num_pages; $i++) {
				$pages[] = ['num' => $i, 'url' => str_replace('{page}', (string)$i, $url_template), 'active' => $i === $page];
			}
		} else {
			// first, ..., current-1, current, current+1, ..., last
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
