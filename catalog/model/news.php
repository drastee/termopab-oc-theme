<?php
namespace Opencart\Catalog\Model\Extension\Termopab;

class News extends \Opencart\System\Engine\Model {
	public function getNews(int $news_id, int $language_id = 0): array {
		if (!$language_id) {
			$language_id = (int)$this->config->get('config_language_id');
		}
		$query = $this->db->query("SELECT p.*, pd.heading, pd.title, pd.description, pd.article, pd.meta_title, pd.meta_description, pd.meta_keyword
			FROM `" . DB_PREFIX . "news` p
			LEFT JOIN `" . DB_PREFIX . "news_description` pd ON (p.news_id = pd.news_id AND pd.language_id = '" . $language_id . "')
			WHERE p.news_id = '" . (int)$news_id . "' AND p.status = '1'");
		return $query->num_rows ? $query->row : [];
	}

	public function getNewsImages(int $news_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "news_image` WHERE `news_id` = '" . (int)$news_id . "' ORDER BY `sort_order` ASC");
		return $query->rows;
	}

	public function getNewss(array $data = []): array {
		$language_id = (int)($data['language_id'] ?? $this->config->get('config_language_id'));
		$sql = "SELECT p.*, pd.title, pd.description
			FROM `" . DB_PREFIX . "news` p
			LEFT JOIN `" . DB_PREFIX . "news_description` pd ON (p.news_id = pd.news_id AND pd.language_id = '" . $language_id . "')
			WHERE p.status = '1'";

		$sort = $data['sort'] ?? 'p.sort_order';
		$allowed_sort = ['p.sort_order' => 1, 'pd.title' => 1, 'p.date_added' => 1];
		if (!isset($allowed_sort[$sort])) {
			$sort = 'p.sort_order';
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

	public function getTotalNewss(): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "news` WHERE status = '1'");
		return (int)$query->row['total'];
	}

	/**
	 * Get newss by IDs (only active), preserving order of IDs.
	 *
	 * @param int[] $news_ids
	 * @param int $language_id
	 * @return array
	 */
	public function getNewssByIds(array $news_ids, int $language_id = 0): array {
		if (!$news_ids) {
			return [];
		}
		if (!$language_id) {
			$language_id = (int)$this->config->get('config_language_id');
		}
		$ids = array_map('intval', array_filter($news_ids));
		if (!$ids) {
			return [];
		}
		$sql = "SELECT p.*, pd.title, pd.description
			FROM `" . DB_PREFIX . "news` p
			LEFT JOIN `" . DB_PREFIX . "news_description` pd ON (p.news_id = pd.news_id AND pd.language_id = '" . (int)$language_id . "')
			WHERE p.status = '1' AND p.news_id IN (" . implode(',', $ids) . ")";
		$query = $this->db->query($sql);
		$by_id = [];
		foreach ($query->rows as $row) {
			$by_id[(int)$row['news_id']] = $row;
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
