<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class NewsSlider extends \Opencart\System\Engine\Controller {

	public function index(array $setting): string {
		if (empty($setting['status'])) {
			return '';
		}

		$language_id = (int)$this->config->get('config_language_id');
		$use_latest = (int)($setting['use_latest'] ?? 1);

		$this->load->model('extension/termopab/news');

		$image_base = rtrim((string)$this->config->get('config_url'), '/') . '/image/';
		$placeholder = $image_base . 'placeholder.png';

		if ($use_latest) {
			$limit = (int)($setting['limit'] ?? 10);
			if ($limit < 1) {
				$limit = 10;
			}
			$rows = $this->model_extension_termopab_news->getNewss([
				'language_id' => $language_id,
				'sort'        => 'p.date_added',
				'order'       => 'DESC',
				'start'       => 0,
				'limit'       => $limit,
			]);
		} else {
			$ids = $setting['news_id'] ?? [];
			if (!is_array($ids)) {
				$ids = $ids !== '' ? [(int)$ids] : [];
			}
			$ids = array_filter(array_map('intval', $ids));
			if (empty($ids)) {
				return '';
			}
			$rows = $this->model_extension_termopab_news->getNewssByIds($ids, $language_id);
		}

		if (empty($rows)) {
			return '';
		}

		$data['reviews'] = [];
		foreach ($rows as $row) {
			$image_path = html_entity_decode((string)($row['image'] ?? ''), ENT_QUOTES, 'UTF-8');
			$image = ($image_path && is_file(DIR_IMAGE . $image_path)) ? $image_base . ltrim($image_path, '/') : $placeholder;

			$description = isset($row['description']) ? trim(strip_tags(html_entity_decode($row['description'], ENT_QUOTES, 'UTF-8'))) : '';
			if (mb_strlen($description) > 120) {
				$description = mb_substr($description, 0, 117) . '...';
			}

			$data['reviews'][] = [
				'title'       => $row['title'] ?: ('News #' . $row['news_id']),
				'description' => $description,
				'image'       => $image,
				'href'        => $this->url->link('extension/termopab/news.info', 'language=' . $this->config->get('config_language') . '&news_id=' . (int)$row['news_id']),
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
		$data['link_href'] = $this->url->link('extension/termopab/news', 'language=' . $this->config->get('config_language'));

		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		return $this->load->view('extension/termopab/module/news_slider', $data);
	}
}
