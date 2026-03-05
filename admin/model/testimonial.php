<?php
namespace Opencart\Admin\Model\Extension\Termopab;

class Testimonial extends \Opencart\System\Engine\Model {
	public function getTestimonialSeoKeywords(int $testimonial_id): array {
		$query = $this->db->query("SELECT `language_id`, `keyword` FROM `" . DB_PREFIX . "seo_url` WHERE `store_id` = '0' AND `key` = 'testimonial_id' AND `value` = '" . (int)$testimonial_id . "'");
		$result = [];
		foreach ($query->rows as $row) {
			$result[(int)$row['language_id']] = (string)($row['keyword'] ?? '');
		}
		return $result;
	}

	public function setTestimonialSeoKeywords(int $testimonial_id, array $seo_keyword): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `store_id` = '0' AND `key` = 'testimonial_id' AND `value` = '" . (int)$testimonial_id . "'");

		foreach ($seo_keyword as $language_id => $keyword) {
			$keyword = trim((string)$keyword);
			if ($keyword === '') {
				continue;
			}
			$this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET
				`store_id` = '0',
				`language_id` = '" . (int)$language_id . "',
				`key` = 'testimonial_id',
				`value` = '" . (int)$testimonial_id . "',
				`keyword` = '" . $this->db->escape($keyword) . "',
				`sort_order` = '1'");
		}
	}

	public function getTestimonial(int $testimonial_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "testimonial` WHERE `testimonial_id` = '" . (int)$testimonial_id . "'");
		return $query->num_rows ? $query->row : [];
	}

	public function getTestimonials(array $data = []): array {
		$sql = "SELECT t.*, td.name FROM `" . DB_PREFIX . "testimonial` t
			LEFT JOIN `" . DB_PREFIX . "testimonial_description` td ON (t.testimonial_id = td.testimonial_id AND td.language_id = '" . (int)$this->config->get('config_language_id') . "')
			WHERE 1=1";

		$allowed_sort = ['t.sort_order' => 1, 'td.name' => 1, 't.date_added' => 1, 't.status' => 1];
		$sort = isset($data['sort']) && isset($allowed_sort[$data['sort']]) ? $data['sort'] : 't.sort_order';
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

	public function getTestimonialDescriptions(int $testimonial_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "testimonial_description` WHERE `testimonial_id` = '" . (int)$testimonial_id . "'");
		$result = [];
		foreach ($query->rows as $row) {
			$result[$row['language_id']] = $row;
		}
		return $result;
	}

	public function getTestimonialImages(int $testimonial_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "testimonial_image` WHERE `testimonial_id` = '" . (int)$testimonial_id . "' ORDER BY `sort_order` ASC");
		return $query->rows;
	}

	public function addTestimonial(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "testimonial` SET
			`image` = '" . $this->db->escape($data['image'] ?? '') . "',
			`video` = '" . $this->db->escape($data['video'] ?? '') . "',
			`sort_order` = '" . (int)($data['sort_order'] ?? 0) . "',
			`status` = '" . (int)($data['status'] ?? 1) . "',
			`date_added` = NOW(),
			`date_modified` = NOW()");
		$testimonial_id = (int)$this->db->getLastId();

		foreach ($data['testimonial_description'] ?? [] as $language_id => $desc) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "testimonial_description` SET
				`testimonial_id` = '" . $testimonial_id . "',
				`language_id` = '" . (int)$language_id . "',
				`heading` = '" . $this->db->escape($desc['heading'] ?? '') . "',
				`name` = '" . $this->db->escape($desc['name'] ?? '') . "',
				`description` = '" . $this->db->escape($desc['description'] ?? '') . "',
				`article` = '" . $this->db->escape($desc['article'] ?? '') . "',
				`meta_title` = '" . $this->db->escape($desc['meta_title'] ?? '') . "',
				`meta_description` = '" . $this->db->escape($desc['meta_description'] ?? '') . "',
				`meta_keyword` = '" . $this->db->escape($desc['meta_keyword'] ?? '') . "'");
		}

		foreach ($data['testimonial_image'] ?? [] as $idx => $img) {
			$image = $this->db->escape($img['image'] ?? '');
			if ($image === '') continue;
			$this->db->query("INSERT INTO `" . DB_PREFIX . "testimonial_image` SET
				`testimonial_id` = '" . $testimonial_id . "',
				`image` = '" . $image . "',
				`sort_order` = '" . (int)($idx . '') . "'");
		}

		$this->setTestimonialSeoKeywords($testimonial_id, $data['seo_keyword'] ?? []);

		return $testimonial_id;
	}

	public function editTestimonial(int $testimonial_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "testimonial` SET
			`image` = '" . $this->db->escape($data['image'] ?? '') . "',
			`video` = '" . $this->db->escape($data['video'] ?? '') . "',
			`sort_order` = '" . (int)($data['sort_order'] ?? 0) . "',
			`status` = '" . (int)($data['status'] ?? 1) . "',
			`date_modified` = NOW()
			WHERE `testimonial_id` = '" . (int)$testimonial_id . "'");

		$this->db->query("DELETE FROM `" . DB_PREFIX . "testimonial_description` WHERE `testimonial_id` = '" . (int)$testimonial_id . "'");
		foreach ($data['testimonial_description'] ?? [] as $language_id => $desc) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "testimonial_description` SET
				`testimonial_id` = '" . (int)$testimonial_id . "',
				`language_id` = '" . (int)$language_id . "',
				`heading` = '" . $this->db->escape($desc['heading'] ?? '') . "',
				`name` = '" . $this->db->escape($desc['name'] ?? '') . "',
				`description` = '" . $this->db->escape($desc['description'] ?? '') . "',
				`article` = '" . $this->db->escape($desc['article'] ?? '') . "',
				`meta_title` = '" . $this->db->escape($desc['meta_title'] ?? '') . "',
				`meta_description` = '" . $this->db->escape($desc['meta_description'] ?? '') . "',
				`meta_keyword` = '" . $this->db->escape($desc['meta_keyword'] ?? '') . "'");
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "testimonial_image` WHERE `testimonial_id` = '" . (int)$testimonial_id . "'");
		foreach ($data['testimonial_image'] ?? [] as $idx => $img) {
			$image = $this->db->escape($img['image'] ?? '');
			if ($image === '') continue;
			$this->db->query("INSERT INTO `" . DB_PREFIX . "testimonial_image` SET
				`testimonial_id` = '" . (int)$testimonial_id . "',
				`image` = '" . $image . "',
				`sort_order` = '" . (int)($idx . '') . "'");
		}

		$this->setTestimonialSeoKeywords($testimonial_id, $data['seo_keyword'] ?? []);
	}

	public function copyTestimonial(int $testimonial_id): int {
		$testimonial = $this->getTestimonial($testimonial_id);
		if (empty($testimonial)) {
			return 0;
		}

		$descriptions = $this->getTestimonialDescriptions($testimonial_id);
		$copySuffix = ' (copy)';
		foreach ($descriptions as $lang_id => $desc) {
			$descriptions[$lang_id]['name'] = ($desc['name'] ?? '') . $copySuffix;
			$descriptions[$lang_id]['heading'] = ($desc['heading'] ?? '') . $copySuffix;
			$descriptions[$lang_id]['meta_title'] = ($desc['meta_title'] ?? '') . $copySuffix;
		}

		$images = $this->getTestimonialImages($testimonial_id);
		$testimonial_image = [];
		$data = [
			'image'   => $testimonial['image'] ?? '',
			'video'   => $testimonial['video'] ?? '',
			'sort_order' => (int)($testimonial['sort_order'] ?? 0),
			'status'  => (int)($testimonial['status'] ?? 1),
			'testimonial_description' => $this->getTestimonialDescriptions($testimonial_id),
			'testimonial_image' => $this->getTestimonialImages($testimonial_id),
		];

		return $this->addTestimonial($data);
	}

	public function deleteTestimonial(int $testimonial_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `store_id` = '0' AND `key` = 'testimonial_id' AND `value` = '" . (int)$testimonial_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "testimonial_image` WHERE `testimonial_id` = '" . (int)$testimonial_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "testimonial_description` WHERE `testimonial_id` = '" . (int)$testimonial_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "testimonial` WHERE `testimonial_id` = '" . (int)$testimonial_id . "'");
	}

	public function getTotalTestimonials(): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "testimonial`");
		return (int)$query->row['total'];
	}
}
