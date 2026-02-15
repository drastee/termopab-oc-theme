<?php
namespace Opencart\Catalog\Controller\Extension\Termopab\Module;

class BreweryReviewsSlider extends \Opencart\System\Engine\Controller {

	public function index(array $setting): string {
		if (empty($setting['status'])) {
			return '';
		}

		$language_id = (int)$this->config->get('config_language_id');
		$use_latest = (int)($setting['use_latest'] ?? 1);

		$this->load->model('extension/termopab/brewery_review');
		$this->load->model('tool/image');

		if ($use_latest) {
			$limit = (int)($setting['limit'] ?? 10);
			if ($limit < 1) {
				$limit = 10;
			}
			$rows = $this->model_extension_termopab_brewery_review->getBreweryReviews([
				'language_id' => $language_id,
				'sort'        => 'b.date_added',
				'order'       => 'DESC',
				'start'       => 0,
				'limit'       => $limit,
			]);
		} else {
			$ids = $setting['brewery_review_id'] ?? [];
			if (!is_array($ids)) {
				$ids = $ids !== '' ? [(int)$ids] : [];
			}
			$ids = array_filter(array_map('intval', $ids));
			if (empty($ids)) {
				return '';
			}
			$rows = $this->model_extension_termopab_brewery_review->getBreweryReviewsByIds($ids, $language_id);
		}

		if (empty($rows)) {
			return '';
		}

		$img_width = 400;
		$img_height = 300;
		$placeholder = $this->model_tool_image->resize('placeholder.png', $img_width, $img_height);

		$data['reviews'] = [];
		foreach ($rows as $row) {
			$image = '';
			if (!empty($row['image']) && is_file(DIR_IMAGE . html_entity_decode($row['image'], ENT_QUOTES, 'UTF-8'))) {
				$image = $this->model_tool_image->resize($row['image'], $img_width, $img_height);
			} else {
				$image = $placeholder;
			}
			$description = isset($row['description']) ? trim(strip_tags(html_entity_decode($row['description'], ENT_QUOTES, 'UTF-8'))) : '';
			if (mb_strlen($description) > 120) {
				$description = mb_substr($description, 0, 117) . '...';
			}
			$data['reviews'][] = [
				'title'       => $row['title'] ?: ('Review #' . $row['brewery_review_id']),
				'description' => $description,
				'image'       => $image,
				'href'        => $this->url->link('extension/termopab/brewery_review.info', 'language=' . $this->config->get('config_language') . '&brewery_review_id=' . (int)$row['brewery_review_id']),
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
		$data['link_href'] = $this->url->link('extension/termopab/brewery_review', 'language=' . $this->config->get('config_language'));

		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/catalog/view/template/');
		return $this->load->view('extension/termopab/module/brewery_reviews_slider', $data);
	}
}
