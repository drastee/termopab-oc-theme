<?php
namespace Opencart\Admin\Model\Extension\Termopab;

class Project extends \Opencart\System\Engine\Model {
	public function getProject(int $project_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "project` WHERE `project_id` = '" . (int)$project_id . "'");
		return $query->num_rows ? $query->row : [];
	}

	public function getProjects(array $data = []): array {
		$sql = "SELECT p.*, pd.title FROM `" . DB_PREFIX . "project` p
			LEFT JOIN `" . DB_PREFIX . "project_description` pd ON (p.project_id = pd.project_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
			WHERE 1=1";

		$allowed_sort = ['p.sort_order' => 1, 'pd.title' => 1, 'p.date_added' => 1, 'p.status' => 1];
		$sort = isset($data['sort']) && isset($allowed_sort[$data['sort']]) ? $data['sort'] : 'p.sort_order';
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

	public function getProjectDescriptions(int $project_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "project_description` WHERE `project_id` = '" . (int)$project_id . "'");
		$result = [];
		foreach ($query->rows as $row) {
			$result[$row['language_id']] = $row;
		}
		return $result;
	}

	public function getProjectImages(int $project_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "project_image` WHERE `project_id` = '" . (int)$project_id . "' ORDER BY `sort_order` ASC");
		return $query->rows;
	}

	public function addProject(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "project` SET
			`image` = '" . $this->db->escape($data['image'] ?? '') . "',
			`logo` = '" . $this->db->escape($data['logo'] ?? '') . "',
			`video` = '" . $this->db->escape($data['video'] ?? '') . "',
			`sort_order` = '" . (int)($data['sort_order'] ?? 0) . "',
			`status` = '" . (int)($data['status'] ?? 1) . "',
			`date_added` = NOW(),
			`date_modified` = NOW()");
		$project_id = (int)$this->db->getLastId();

		foreach ($data['project_description'] ?? [] as $language_id => $desc) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "project_description` SET
				`project_id` = '" . $project_id . "',
				`language_id` = '" . (int)$language_id . "',
				`heading` = '" . $this->db->escape($desc['heading'] ?? '') . "',
				`title` = '" . $this->db->escape($desc['title'] ?? '') . "',
				`description` = '" . $this->db->escape($desc['description'] ?? '') . "',
				`article` = '" . $this->db->escape($desc['article'] ?? '') . "',
				`meta_title` = '" . $this->db->escape($desc['meta_title'] ?? '') . "',
				`meta_description` = '" . $this->db->escape($desc['meta_description'] ?? '') . "',
				`meta_keyword` = '" . $this->db->escape($desc['meta_keyword'] ?? '') . "'");
		}

		foreach ($data['project_image'] ?? [] as $idx => $img) {
			$image = $this->db->escape($img['image'] ?? '');
			if ($image === '') continue;
			$this->db->query("INSERT INTO `" . DB_PREFIX . "project_image` SET
				`project_id` = '" . $project_id . "',
				`image` = '" . $image . "',
				`sort_order` = '" . (int)($idx . '') . "'");
		}

		return $project_id;
	}

	public function editProject(int $project_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "project` SET
			`image` = '" . $this->db->escape($data['image'] ?? '') . "',
			`logo` = '" . $this->db->escape($data['logo'] ?? '') . "',
			`video` = '" . $this->db->escape($data['video'] ?? '') . "',
			`sort_order` = '" . (int)($data['sort_order'] ?? 0) . "',
			`status` = '" . (int)($data['status'] ?? 1) . "',
			`date_modified` = NOW()
			WHERE `project_id` = '" . (int)$project_id . "'");

		$this->db->query("DELETE FROM `" . DB_PREFIX . "project_description` WHERE `project_id` = '" . (int)$project_id . "'");
		foreach ($data['project_description'] ?? [] as $language_id => $desc) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "project_description` SET
				`project_id` = '" . (int)$project_id . "',
				`language_id` = '" . (int)$language_id . "',
				`heading` = '" . $this->db->escape($desc['heading'] ?? '') . "',
				`title` = '" . $this->db->escape($desc['title'] ?? '') . "',
				`description` = '" . $this->db->escape($desc['description'] ?? '') . "',
				`article` = '" . $this->db->escape($desc['article'] ?? '') . "',
				`meta_title` = '" . $this->db->escape($desc['meta_title'] ?? '') . "',
				`meta_description` = '" . $this->db->escape($desc['meta_description'] ?? '') . "',
				`meta_keyword` = '" . $this->db->escape($desc['meta_keyword'] ?? '') . "'");
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "project_image` WHERE `project_id` = '" . (int)$project_id . "'");
		foreach ($data['project_image'] ?? [] as $idx => $img) {
			$image = $this->db->escape($img['image'] ?? '');
			if ($image === '') continue;
			$this->db->query("INSERT INTO `" . DB_PREFIX . "project_image` SET
				`project_id` = '" . (int)$project_id . "',
				`image` = '" . $image . "',
				`sort_order` = '" . (int)($idx . '') . "'");
		}
	}

	public function deleteProject(int $project_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "project_image` WHERE `project_id` = '" . (int)$project_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "project_description` WHERE `project_id` = '" . (int)$project_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "project` WHERE `project_id` = '" . (int)$project_id . "'");
	}

	public function getTotalProjects(): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "project`");
		return (int)$query->row['total'];
	}
}
