<?php
namespace Opencart\Admin\Model\Extension\Termopab;

class BreweryReviewCategory extends \Opencart\System\Engine\Model {

	public function getCategory(int $brewery_review_category_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "brewery_review_category` WHERE `brewery_review_category_id` = '" . (int)$brewery_review_category_id . "'");
		return $query->num_rows ? $query->row : [];
	}

	public function getCategories(array $data = []): array {
		$sql = "SELECT c.*, cd.title FROM `" . DB_PREFIX . "brewery_review_category` c
			LEFT JOIN `" . DB_PREFIX . "brewery_review_category_description` cd ON (c.brewery_review_category_id = cd.brewery_review_category_id AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "')
			WHERE 1=1";
		$sql .= " ORDER BY c.sort_order ASC, cd.title ASC";
		if (isset($data['start']) || isset($data['limit'])) {
			$start = (int)($data['start'] ?? 0);
			$limit = (int)($data['limit'] ?? 100);
			$sql .= " LIMIT " . $start . "," . $limit;
		}
		$query = $this->db->query($sql);
		return $query->rows;
	}

	public function getCategoryDescriptions(int $brewery_review_category_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "brewery_review_category_description` WHERE `brewery_review_category_id` = '" . (int)$brewery_review_category_id . "'");
		$result = [];
		foreach ($query->rows as $row) {
			$result[$row['language_id']] = $row;
		}
		return $result;
	}

	public function addCategory(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "brewery_review_category` SET
			`sort_order` = '" . (int)($data['sort_order'] ?? 0) . "',
			`status` = '" . (int)($data['status'] ?? 1) . "',
			`date_added` = NOW(),
			`date_modified` = NOW()");
		$id = (int)$this->db->getLastId();
		foreach ($data['category_description'] ?? [] as $language_id => $desc) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "brewery_review_category_description` SET
				`brewery_review_category_id` = '" . $id . "',
				`language_id` = '" . (int)$language_id . "',
				`title` = '" . $this->db->escape($desc['title'] ?? '') . "'");
		}
		return $id;
	}

	public function editCategory(int $brewery_review_category_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "brewery_review_category` SET
			`sort_order` = '" . (int)($data['sort_order'] ?? 0) . "',
			`status` = '" . (int)($data['status'] ?? 1) . "',
			`date_modified` = NOW()
			WHERE `brewery_review_category_id` = '" . (int)$brewery_review_category_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "brewery_review_category_description` WHERE `brewery_review_category_id` = '" . (int)$brewery_review_category_id . "'");
		foreach ($data['category_description'] ?? [] as $language_id => $desc) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "brewery_review_category_description` SET
				`brewery_review_category_id` = '" . (int)$brewery_review_category_id . "',
				`language_id` = '" . (int)$language_id . "',
				`title` = '" . $this->db->escape($desc['title'] ?? '') . "'");
		}
	}

	public function deleteCategory(int $brewery_review_category_id): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "brewery_review` SET `brewery_review_category_id` = 0 WHERE `brewery_review_category_id` = '" . (int)$brewery_review_category_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "brewery_review_category_description` WHERE `brewery_review_category_id` = '" . (int)$brewery_review_category_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "brewery_review_category` WHERE `brewery_review_category_id` = '" . (int)$brewery_review_category_id . "'");
	}

	public function getTotalCategories(): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "brewery_review_category`");
		return (int)$query->row['total'];
	}
}
