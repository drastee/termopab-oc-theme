<?php
namespace Opencart\Catalog\Model\Extension\Termopab;

class Testimonial extends \Opencart\System\Engine\Model {
	public function getTestimonial(int $testimonial_id, int $language_id = 0): array {
		if (!$language_id) {
			$language_id = (int)$this->config->get('config_language_id');
		}
		$query = $this->db->query("SELECT t.*, td.heading, td.name, td.description, td.article, td.meta_title, td.meta_description, td.meta_keyword
			FROM `" . DB_PREFIX . "testimonial` t
			LEFT JOIN `" . DB_PREFIX . "testimonial_description` td ON (t.testimonial_id = td.testimonial_id AND td.language_id = '" . $language_id . "')
			WHERE t.testimonial_id = '" . (int)$testimonial_id . "' AND t.status = '1'");
		return $query->num_rows ? $query->row : [];
	}

	public function getTestimonialImages(int $testimonial_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "testimonial_image` WHERE `testimonial_id` = '" . (int)$testimonial_id . "' ORDER BY `sort_order` ASC");
		return $query->rows;
	}

	public function getTestimonials(array $data = []): array {
		$language_id = (int)($data['language_id'] ?? $this->config->get('config_language_id'));
		$sql = "SELECT t.*, td.name, td.description
			FROM `" . DB_PREFIX . "testimonial` t
			LEFT JOIN `" . DB_PREFIX . "testimonial_description` td ON (t.testimonial_id = td.testimonial_id AND td.language_id = '" . $language_id . "')
			WHERE t.status = '1'";

		$sort = $data['sort'] ?? 't.sort_order';
		$allowed_sort = ['t.sort_order' => 1, 'td.name' => 1, 't.date_added' => 1];
		if (!isset($allowed_sort[$sort])) {
			$sort = 't.sort_order';
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

	public function getTotalTestimonials(): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "testimonial` WHERE status = '1'");
		return (int)$query->row['total'];
	}

	public function getTestimonialsByIds(array $testimonial_ids, int $language_id = 0): array {
		if (!$testimonial_ids) {
			return [];
		}
		if (!$language_id) {
			$language_id = (int)$this->config->get('config_language_id');
		}
		$ids = array_map('intval', array_filter($testimonial_ids));
		if (!$ids) {
			return [];
		}
		$sql = "SELECT t.*, td.name, td.description
			FROM `" . DB_PREFIX . "testimonial` t
			LEFT JOIN `" . DB_PREFIX . "testimonial_description` td ON (t.testimonial_id = td.testimonial_id AND td.language_id = '" . (int)$language_id . "')
			WHERE t.status = '1' AND t.testimonial_id IN (" . implode(',', $ids) . ")";
		$query = $this->db->query($sql);
		$by_id = [];
		foreach ($query->rows as $row) {
			$by_id[(int)$row['testimonial_id']] = $row;
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
