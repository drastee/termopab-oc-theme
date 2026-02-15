<?php
namespace Opencart\Catalog\Model\Extension\Termopab;

class BreweryReview extends \Opencart\System\Engine\Model {
	public function getBreweryReview(int $brewery_review_id, int $language_id = 0): array {
		if (!$language_id) {
			$language_id = (int)$this->config->get('config_language_id');
		}
		$query = $this->db->query("SELECT b.*, bd.heading, bd.title, bd.description, bd.article, bd.meta_title, bd.meta_description, bd.meta_keyword
			FROM `" . DB_PREFIX . "brewery_review` b
			LEFT JOIN `" . DB_PREFIX . "brewery_review_description` bd ON (b.brewery_review_id = bd.brewery_review_id AND bd.language_id = '" . $language_id . "')
			WHERE b.brewery_review_id = '" . (int)$brewery_review_id . "' AND b.status = '1'");
		return $query->num_rows ? $query->row : [];
	}

	public function getBreweryReviewImages(int $brewery_review_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "brewery_review_image` WHERE `brewery_review_id` = '" . (int)$brewery_review_id . "' ORDER BY `sort_order` ASC");
		return $query->rows;
	}

	public function getBreweryReviewCategories(int $language_id = 0): array {
		if (!$language_id) {
			$language_id = (int)$this->config->get('config_language_id');
		}
		$query = $this->db->query("SELECT c.brewery_review_category_id, cd.title
			FROM `" . DB_PREFIX . "brewery_review_category` c
			LEFT JOIN `" . DB_PREFIX . "brewery_review_category_description` cd ON (c.brewery_review_category_id = cd.brewery_review_category_id AND cd.language_id = '" . (int)$language_id . "')
			WHERE c.status = '1'
			ORDER BY c.sort_order ASC, cd.title ASC");
		return $query->rows;
	}

	public function getBreweryReviews(array $data = []): array {
		$language_id = (int)($data['language_id'] ?? $this->config->get('config_language_id'));
		$sql = "SELECT b.*, bd.title, bd.description
			FROM `" . DB_PREFIX . "brewery_review` b
			LEFT JOIN `" . DB_PREFIX . "brewery_review_description` bd ON (b.brewery_review_id = bd.brewery_review_id AND bd.language_id = '" . $language_id . "')
			WHERE b.status = '1'";

		if (isset($data['filter_brewery_review_category_id']) && (int)$data['filter_brewery_review_category_id'] > 0) {
			$sql .= " AND b.brewery_review_category_id = '" . (int)$data['filter_brewery_review_category_id'] . "'";
		}

		$sort = $data['sort'] ?? 'b.sort_order';
		$allowed_sort = ['b.sort_order' => 1, 'bd.title' => 1, 'b.date_added' => 1];
		if (!isset($allowed_sort[$sort])) {
			$sort = 'b.sort_order';
		}
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

	public function getTotalBreweryReviews(array $data = []): int {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "brewery_review` WHERE status = '1'";
		if (isset($data['filter_brewery_review_category_id']) && (int)$data['filter_brewery_review_category_id'] > 0) {
			$sql .= " AND brewery_review_category_id = '" . (int)$data['filter_brewery_review_category_id'] . "'";
		}
		$query = $this->db->query($sql);
		return (int)$query->row['total'];
	}

	/**
	 * Get brewery reviews by IDs (only active), preserving order of IDs.
	 */
	public function getBreweryReviewsByIds(array $brewery_review_ids, int $language_id = 0): array {
		if (!$brewery_review_ids) {
			return [];
		}
		if (!$language_id) {
			$language_id = (int)$this->config->get('config_language_id');
		}
		$ids = array_map('intval', array_filter($brewery_review_ids));
		if (!$ids) {
			return [];
		}
		$sql = "SELECT b.*, bd.title, bd.description
			FROM `" . DB_PREFIX . "brewery_review` b
			LEFT JOIN `" . DB_PREFIX . "brewery_review_description` bd ON (b.brewery_review_id = bd.brewery_review_id AND bd.language_id = '" . (int)$language_id . "')
			WHERE b.status = '1' AND b.brewery_review_id IN (" . implode(',', $ids) . ")";
		$query = $this->db->query($sql);
		$by_id = [];
		foreach ($query->rows as $row) {
			$by_id[(int)$row['brewery_review_id']] = $row;
		}
		$ordered = [];
		foreach ($ids as $id) {
			if (isset($by_id[$id])) {
				$ordered[] = $by_id[$id];
			}
		}
		return $ordered;
	}
}
