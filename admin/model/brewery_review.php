<?php
namespace Opencart\Admin\Model\Extension\Termopab;

class BreweryReview extends \Opencart\System\Engine\Model {
	public function getBreweryReview(int $brewery_review_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "brewery_review` WHERE `brewery_review_id` = '" . (int)$brewery_review_id . "'");
		return $query->num_rows ? $query->row : [];
	}

	public function getBreweryReviews(array $data = []): array {
		$sql = "SELECT b.*, bd.title, cdd.title AS category_title FROM `" . DB_PREFIX . "brewery_review` b
			LEFT JOIN `" . DB_PREFIX . "brewery_review_description` bd ON (b.brewery_review_id = bd.brewery_review_id AND bd.language_id = '" . (int)$this->config->get('config_language_id') . "')
			LEFT JOIN `" . DB_PREFIX . "brewery_review_category_description` cdd ON (b.brewery_review_category_id = cdd.brewery_review_category_id AND cdd.language_id = '" . (int)$this->config->get('config_language_id') . "')
			WHERE 1=1";

		$allowed_sort = ['b.sort_order' => 1, 'bd.title' => 1, 'b.date_added' => 1, 'b.status' => 1];
		$sort = isset($data['sort']) && isset($allowed_sort[$data['sort']]) ? $data['sort'] : 'b.sort_order';
		$order = isset($data['order']) && strtoupper($data['order']) === 'DESC' ? 'DESC' : 'ASC';
		$sql .= " ORDER BY " . $sort . " " . $order;

		if (isset($data['start']) || isset($data['limit'])) {
			$start = (int)($data['start'] ?? 0);
			$limit = (int)($data['limit'] ?? 20);
			$sql .= " LIMIT " . $start . "," . $limit;
		}

		$query = $this->db->query($sql);
		return $query->rows;
	}

	public function getBreweryReviewDescriptions(int $brewery_review_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "brewery_review_description` WHERE `brewery_review_id` = '" . (int)$brewery_review_id . "'");
		$result = [];
		foreach ($query->rows as $row) {
			$result[$row['language_id']] = $row;
		}
		return $result;
	}

	public function getBreweryReviewImages(int $brewery_review_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "brewery_review_image` WHERE `brewery_review_id` = '" . (int)$brewery_review_id . "' ORDER BY `sort_order` ASC");
		return $query->rows;
	}

	public function addBreweryReview(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "brewery_review` SET
			`brewery_review_category_id` = '" . (int)($data['brewery_review_category_id'] ?? 0) . "',
			`image` = '" . $this->db->escape($data['image'] ?? '') . "',
			`logo` = '" . $this->db->escape($data['logo'] ?? '') . "',
			`video` = '" . $this->db->escape($data['video'] ?? '') . "',
			`sort_order` = '" . (int)($data['sort_order'] ?? 0) . "',
			`status` = '" . (int)($data['status'] ?? 1) . "',
			`date_added` = NOW(),
			`date_modified` = NOW()");
		$brewery_review_id = (int)$this->db->getLastId();

		foreach ($data['brewery_review_description'] ?? [] as $language_id => $desc) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "brewery_review_description` SET
				`brewery_review_id` = '" . $brewery_review_id . "',
				`language_id` = '" . (int)$language_id . "',
				`heading` = '" . $this->db->escape($desc['heading'] ?? '') . "',
				`title` = '" . $this->db->escape($desc['title'] ?? '') . "',
				`description` = '" . $this->db->escape($desc['description'] ?? '') . "',
				`article` = '" . $this->db->escape($desc['article'] ?? '') . "',
				`meta_title` = '" . $this->db->escape($desc['meta_title'] ?? '') . "',
				`meta_description` = '" . $this->db->escape($desc['meta_description'] ?? '') . "',
				`meta_keyword` = '" . $this->db->escape($desc['meta_keyword'] ?? '') . "'");
		}

		foreach ($data['brewery_review_image'] ?? [] as $idx => $img) {
			$image = $this->db->escape($img['image'] ?? '');
			if ($image === '') continue;
			$this->db->query("INSERT INTO `" . DB_PREFIX . "brewery_review_image` SET
				`brewery_review_id` = '" . $brewery_review_id . "',
				`image` = '" . $image . "',
				`sort_order` = '" . (int)($idx . '') . "'");
		}

		return $brewery_review_id;
	}

	public function editBreweryReview(int $brewery_review_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "brewery_review` SET
			`brewery_review_category_id` = '" . (int)($data['brewery_review_category_id'] ?? 0) . "',
			`image` = '" . $this->db->escape($data['image'] ?? '') . "',
			`logo` = '" . $this->db->escape($data['logo'] ?? '') . "',
			`video` = '" . $this->db->escape($data['video'] ?? '') . "',
			`sort_order` = '" . (int)($data['sort_order'] ?? 0) . "',
			`status` = '" . (int)($data['status'] ?? 1) . "',
			`date_modified` = NOW()
			WHERE `brewery_review_id` = '" . (int)$brewery_review_id . "'");

		$this->db->query("DELETE FROM `" . DB_PREFIX . "brewery_review_description` WHERE `brewery_review_id` = '" . (int)$brewery_review_id . "'");
		foreach ($data['brewery_review_description'] ?? [] as $language_id => $desc) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "brewery_review_description` SET
				`brewery_review_id` = '" . (int)$brewery_review_id . "',
				`language_id` = '" . (int)$language_id . "',
				`heading` = '" . $this->db->escape($desc['heading'] ?? '') . "',
				`title` = '" . $this->db->escape($desc['title'] ?? '') . "',
				`description` = '" . $this->db->escape($desc['description'] ?? '') . "',
				`article` = '" . $this->db->escape($desc['article'] ?? '') . "',
				`meta_title` = '" . $this->db->escape($desc['meta_title'] ?? '') . "',
				`meta_description` = '" . $this->db->escape($desc['meta_description'] ?? '') . "',
				`meta_keyword` = '" . $this->db->escape($desc['meta_keyword'] ?? '') . "'");
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "brewery_review_image` WHERE `brewery_review_id` = '" . (int)$brewery_review_id . "'");
		foreach ($data['brewery_review_image'] ?? [] as $idx => $img) {
			$image = $this->db->escape($img['image'] ?? '');
			if ($image === '') continue;
			$this->db->query("INSERT INTO `" . DB_PREFIX . "brewery_review_image` SET
				`brewery_review_id` = '" . (int)$brewery_review_id . "',
				`image` = '" . $image . "',
				`sort_order` = '" . (int)($idx . '') . "'");
		}
	}

	public function copyBreweryReview(int $brewery_review_id): int {
		$brewery_review = $this->getBreweryReview($brewery_review_id);
		if (empty($brewery_review)) {
			return 0;
		}

		$descriptions = $this->getBreweryReviewDescriptions($brewery_review_id);
		$copySuffix = ' (copy)';
		foreach ($descriptions as $lang_id => $desc) {
			$descriptions[$lang_id]['title'] = ($desc['title'] ?? '') . $copySuffix;
			$descriptions[$lang_id]['heading'] = ($desc['heading'] ?? '') . $copySuffix;
			$descriptions[$lang_id]['meta_title'] = ($desc['meta_title'] ?? '') . $copySuffix;
		}

		$images = $this->getBreweryReviewImages($brewery_review_id);
		$brewery_review_image = [];
		foreach ($images as $img) {
			$brewery_review_image[] = ['image' => $img['image'] ?? ''];
		}

		$data = [
			'brewery_review_category_id' => (int)($brewery_review['brewery_review_category_id'] ?? 0),
			'image'                      => $brewery_review['image'] ?? '',
			'logo'                       => $brewery_review['logo'] ?? '',
			'video'                      => $brewery_review['video'] ?? '',
			'sort_order'                 => (int)($brewery_review['sort_order'] ?? 0),
			'status'                     => (int)($brewery_review['status'] ?? 1),
			'brewery_review_description' => $descriptions,
			'brewery_review_image'       => $brewery_review_image,
		];

		return $this->addBreweryReview($data);
	}

	public function deleteBreweryReview(int $brewery_review_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "brewery_review_image` WHERE `brewery_review_id` = '" . (int)$brewery_review_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "brewery_review_description` WHERE `brewery_review_id` = '" . (int)$brewery_review_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "brewery_review` WHERE `brewery_review_id` = '" . (int)$brewery_review_id . "'");
	}

	public function getTotalBreweryReviews(): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "brewery_review`");
		return (int)$query->row['total'];
	}
}
