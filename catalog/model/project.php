<?php
namespace Opencart\Catalog\Model\Extension\Termopab;

class Project extends \Opencart\System\Engine\Model {
	public function getProject(int $project_id, int $language_id = 0): array {
		if (!$language_id) {
			$language_id = (int)$this->config->get('config_language_id');
		}
		$query = $this->db->query("SELECT p.*, pd.heading, pd.title, pd.description, pd.article, pd.meta_title, pd.meta_description, pd.meta_keyword
			FROM `" . DB_PREFIX . "project` p
			LEFT JOIN `" . DB_PREFIX . "project_description` pd ON (p.project_id = pd.project_id AND pd.language_id = '" . $language_id . "')
			WHERE p.project_id = '" . (int)$project_id . "' AND p.status = '1'");
		return $query->num_rows ? $query->row : [];
	}

	public function getProjectImages(int $project_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "project_image` WHERE `project_id` = '" . (int)$project_id . "' ORDER BY `sort_order` ASC");
		return $query->rows;
	}

	public function getProjects(array $data = []): array {
		$language_id = (int)($data['language_id'] ?? $this->config->get('config_language_id'));
		$sql = "SELECT p.*, pd.title, pd.description
			FROM `" . DB_PREFIX . "project` p
			LEFT JOIN `" . DB_PREFIX . "project_description` pd ON (p.project_id = pd.project_id AND pd.language_id = '" . $language_id . "')
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

	public function getTotalProjects(): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "project` WHERE status = '1'");
		return (int)$query->row['total'];
	}
}
