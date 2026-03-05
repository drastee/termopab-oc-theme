<?php
namespace Opencart\Catalog\Controller\Extension\Termopab;

class Testimonial extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		$this->load->language('extension/termopab/testimonial/list');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/testimonial', 'language=' . $this->config->get('config_language'))
		];

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_testimonials'] = $this->language->get('text_testimonials');
		$data['text_read_more'] = $this->language->get('text_read_more');
		$data['text_back_list'] = $this->language->get('text_back_list');
		$data['text_empty'] = $this->language->get('text_empty');

		$page = (int)($this->request->get['page'] ?? 1);
		$limit = (int)($this->config->get('config_pagination') ?: 12);
		$start = ($page - 1) * $limit;

		$this->load->model('extension/termopab/testimonial');

		$total = $this->model_extension_termopab_testimonial->getTotalTestimonials();
		$results = $this->model_extension_termopab_testimonial->getTestimonials(['start' => $start, 'limit' => $limit]);

		$data['testimonials'] = [];
		$image_base = rtrim((string)$this->config->get('config_url'), '/') . '/image/';
		$placeholder = $image_base . 'placeholder.png';

		foreach ($results as $row) {
			$image_path = html_entity_decode((string)($row['image'] ?? ''), ENT_QUOTES, 'UTF-8');
			$img = $image_path && is_file(DIR_IMAGE . $image_path) ? $image_base . ltrim($image_path, '/') : $placeholder;
			$data['testimonials'][] = [
				'testimonial_id' => $row['testimonial_id'],
				'name'           => $row['name'] ?: ('Testimonial #' . $row['testimonial_id']),
				'description'    => $row['description'] ?: '',
				'image'          => $img,
				'href'           => $this->url->link('extension/termopab/testimonial.info', 'language=' . $this->config->get('config_language') . '&testimonial_id=' . $row['testimonial_id']),
			];
		}

		$pagination_url = $this->url->link('extension/termopab/testimonial', 'language=' . $this->config->get('config_language') . '&page={page}');
		$data['pagination_data'] = $this->buildPaginationData($total, $page, $limit, $pagination_url);

		$data['header'] = $this->load->controller('common/header');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/testimonial/list', $data));
	}

	public function info(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		$this->load->language('extension/termopab/testimonial/info');

		$testimonial_id = (int)($this->request->get['testimonial_id'] ?? 0);

		if (!$testimonial_id && isset($this->request->get['_route_'])) {
			$route = (string)$this->request->get['_route_'];
			$route = trim($route, '/');

			if ($route !== '') {
				$parts = explode('/', $route);
				$keyword = (string)end($parts);

				if ($keyword !== '') {
					$query = $this->db->query("SELECT `value` FROM `" . DB_PREFIX . "seo_url` WHERE `store_id` = '" . (int)$this->config->get('config_store_id') . "' AND `key` = 'testimonial_id' AND `keyword` = '" . $this->db->escape($keyword) . "' LIMIT 1");
					if (!empty($query->row['value'])) {
						$testimonial_id = (int)$query->row['value'];
						$this->request->get['testimonial_id'] = $testimonial_id;
					}
				}
			}
		}
		if (!$testimonial_id) {
			$this->response->redirect($this->url->link('extension/termopab/testimonial', 'language=' . $this->config->get('config_language')));
			return;
		}

		$this->load->model('extension/termopab/testimonial');

		$testimonial = $this->model_extension_termopab_testimonial->getTestimonial($testimonial_id);
		if (!$testimonial) {
			$this->response->redirect($this->url->link('extension/termopab/testimonial', 'language=' . $this->config->get('config_language')));
			return;
		}

		$this->document->setTitle($testimonial['meta_title'] ?: $testimonial['name']);
		if ($testimonial['meta_description']) {
			$this->document->setDescription($testimonial['meta_description']);
		}
		if ($testimonial['meta_keyword']) {
			$this->document->setKeywords($testimonial['meta_keyword']);
		}

		$data['breadcrumbs'] = [];
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'language=' . $this->config->get('config_language'))
		];
		$this->load->language('extension/termopab/testimonial/list');
		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/testimonial', 'language=' . $this->config->get('config_language'))
		];
		$data['breadcrumbs'][] = [
			'text' => $testimonial['name'],
			'href' => $this->url->link('extension/termopab/testimonial.info', 'language=' . $this->config->get('config_language') . '&testimonial_id=' . $testimonial_id)
		];

		$data['heading'] = $testimonial['heading'] ?? '';
		$data['name'] = $testimonial['name'];
		$data['description'] = $testimonial['description'];
		$data['article'] = html_entity_decode($testimonial['article'], ENT_QUOTES, 'UTF-8');
		$data['back_list'] = $this->url->link('extension/termopab/testimonial', 'language=' . $this->config->get('config_language'));
		$data['text_back_list'] = $this->language->get('text_back_list');
		$data['text_gallery'] = $this->language->get('text_gallery');

		$image_base = rtrim((string)$this->config->get('config_url'), '/') . '/image/';

		$main_image_path = html_entity_decode((string)($testimonial['image'] ?? ''), ENT_QUOTES, 'UTF-8');
		if ($main_image_path && is_file(DIR_IMAGE . $main_image_path)) {
			$data['image'] = $image_base . ltrim($main_image_path, '/');
		} else {
			$data['image'] = '';
		}
		$data['video_url'] = '';
		if (!empty($testimonial['video'])) {
			$video_path = str_starts_with($testimonial['video'], 'catalog/') ? $testimonial['video'] : 'catalog/' . ltrim($testimonial['video'], '/');
			$data['video_url'] = $image_base . $video_path;
		}
		$data['testimonial_images'] = [];
		$gallery = $this->model_extension_termopab_testimonial->getTestimonialImages($testimonial_id);
		foreach ($gallery as $row) {
			$img = $row['image'] ?? '';
			$img_path = html_entity_decode((string)$img, ENT_QUOTES, 'UTF-8');
			if ($img_path && is_file(DIR_IMAGE . $img_path)) {
				$data['testimonial_images'][] = [
					'image' => $image_base . ltrim($img_path, '/'),
					'thumb' => $image_base . ltrim($img_path, '/'),
				];
			}
		}

		$data['header'] = $this->load->controller('common/header');
		$data['content_top'] = $this->load->controller('common/content_top');
		$data['content_bottom'] = $this->load->controller('common/content_bottom');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/testimonial/info', $data));
	}

	private function buildPaginationData(int $total, int $page, int $limit, string $url_template): array {
		$num_pages = $limit > 0 ? (int)ceil($total / $limit) : 1;
		$page = max(1, min($page, $num_pages ?: 1));

		$prev_url = $page > 1 ? str_replace('{page}', (string)($page - 1), $url_template) : '';
		$next_url = $page < $num_pages ? str_replace('{page}', (string)($page + 1), $url_template) : '';

		$pages = [];
		if ($num_pages <= 1) {
			// one page
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
