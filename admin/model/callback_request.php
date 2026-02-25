<?php
namespace Opencart\Admin\Model\Extension\Termopab;

class CallbackRequest extends \Opencart\System\Engine\Model {
	public function getCallbackRequest(int $callback_request_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "callback_request` WHERE `callback_request_id` = '" . (int)$callback_request_id . "'");
		return $query->num_rows ? $query->row : [];
	}

	public function getCallbackRequests(array $data = []): array {
		$sql = "SELECT * FROM `" . DB_PREFIX . "callback_request` cr WHERE 1=1";

		$allowed_sort = ['cr.date_added' => 1, 'cr.status' => 1, 'cr.callback_request_id' => 1];
		$sort = isset($data['sort']) && isset($allowed_sort[$data['sort']]) ? $data['sort'] : 'cr.date_added';
		$order = isset($data['order']) && strtoupper($data['order']) === 'ASC' ? 'ASC' : 'DESC';
		$sql .= " ORDER BY " . $sort . " " . $order;

		if (isset($data['start']) || isset($data['limit'])) {
			$start = (int)($data['start'] ?? 0);
			$limit = (int)($data['limit'] ?? 20);
			$sql .= " LIMIT " . $start . "," . $limit;
		}

		$query = $this->db->query($sql);
		return $query->rows;
	}

	public function getTotalCallbackRequests(): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "callback_request`");
		return (int)($query->row['total'] ?? 0);
	}

	public function editCallbackRequest(int $callback_request_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "callback_request` SET
			`status` = '" . (int)($data['status'] ?? 0) . "',
			`comment` = '" . $this->db->escape((string)($data['comment'] ?? '')) . "'
			WHERE `callback_request_id` = '" . (int)$callback_request_id . "'");
	}

	public function deleteCallbackRequest(int $callback_request_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "callback_request` WHERE `callback_request_id` = '" . (int)$callback_request_id . "'");
	}
}
