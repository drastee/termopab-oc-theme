<?php
namespace Opencart\Catalog\Model\Extension\Termopab;

class CallbackRequest extends \Opencart\System\Engine\Model {
	public function addCallbackRequest(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "callback_request` SET
			`name` = '" . $this->db->escape((string)($data['name'] ?? '')) . "',
			`phone` = '" . $this->db->escape((string)($data['phone'] ?? '')) . "',
			`status` = '" . (int)($data['status'] ?? 0) . "',
			`comment` = '" . $this->db->escape((string)($data['comment'] ?? '')) . "',
			`ip` = '" . $this->db->escape((string)($data['ip'] ?? '')) . "',
			`user_agent` = '" . $this->db->escape((string)($data['user_agent'] ?? '')) . "',
			`date_added` = NOW()");

		return (int)$this->db->getLastId();
	}
}
