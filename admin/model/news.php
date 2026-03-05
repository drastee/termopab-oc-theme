<?php
namespace Opencart\Admin\Model\Extension\Termopab;

class News extends \Opencart\System\Engine\Model {
	public function getNewsSeoKeywords(int $news_id): array {
		$query = $this->db->query("SELECT `language_id`, `keyword` FROM `" . DB_PREFIX . "seo_url` WHERE `store_id` = '0' AND `key` = 'news_id' AND `value` = '" . (int)$news_id . "'");
		$result = [];
		foreach ($query->rows as $row) {
			$result[(int)$row['language_id']] = (string)($row['keyword'] ?? '');
		}
		return $result;
	}

	public function setNewsSeoKeywords(int $news_id, array $seo_keyword): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `store_id` = '0' AND `key` = 'news_id' AND `value` = '" . (int)$news_id . "'");

		foreach ($seo_keyword as $language_id => $keyword) {
			$keyword = trim((string)$keyword);
			if ($keyword === '') {
				continue;
			}
			$this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET
				`store_id` = '0',
				`language_id` = '" . (int)$language_id . "',
				`key` = 'news_id',
				`value` = '" . (int)$news_id . "',
				`keyword` = '" . $this->db->escape($keyword) . "',
				`sort_order` = '1'");
		}
	}

	public function getNews(int $news_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "news` WHERE `news_id` = '" . (int)$news_id . "'");
		return $query->num_rows ? $query->row : [];
	}

	public function getNewss(array $data = []): array {
		$sql = "SELECT p.*, pd.title FROM `" . DB_PREFIX . "news` p
			LEFT JOIN `" . DB_PREFIX . "news_description` pd ON (p.news_id = pd.news_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "')
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

	public function getNewsDescriptions(int $news_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "news_description` WHERE `news_id` = '" . (int)$news_id . "'");
		$result = [];
		foreach ($query->rows as $row) {
			$result[$row['language_id']] = $row;
		}
		return $result;
	}

	public function getNewsImages(int $news_id): array {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "news_image` WHERE `news_id` = '" . (int)$news_id . "' ORDER BY `sort_order` ASC");
		return $query->rows;
	}

	public function addNews(array $data): int {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "news` SET
			`image` = '" . $this->db->escape($data['image'] ?? '') . "',
			`logo` = '" . $this->db->escape($data['logo'] ?? '') . "',
			`video` = '" . $this->db->escape($data['video'] ?? '') . "',
			`sort_order` = '" . (int)($data['sort_order'] ?? 0) . "',
			`status` = '" . (int)($data['status'] ?? 1) . "',
			`date_added` = NOW(),
			`date_modified` = NOW()");
		$news_id = (int)$this->db->getLastId();

		foreach ($data['news_description'] ?? [] as $language_id => $desc) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "news_description` SET
				`news_id` = '" . $news_id . "',
				`language_id` = '" . (int)$language_id . "',
				`heading` = '" . $this->db->escape($desc['heading'] ?? '') . "',
				`title` = '" . $this->db->escape($desc['title'] ?? '') . "',
				`description` = '" . $this->db->escape($desc['description'] ?? '') . "',
				`article` = '" . $this->db->escape($desc['article'] ?? '') . "',
				`meta_title` = '" . $this->db->escape($desc['meta_title'] ?? '') . "',
				`meta_description` = '" . $this->db->escape($desc['meta_description'] ?? '') . "',
				`meta_keyword` = '" . $this->db->escape($desc['meta_keyword'] ?? '') . "'");
		}

		foreach ($data['news_image'] ?? [] as $idx => $img) {
			$image = $this->db->escape($img['image'] ?? '');
			if ($image === '') continue;
			$this->db->query("INSERT INTO `" . DB_PREFIX . "news_image` SET
				`news_id` = '" . $news_id . "',
				`image` = '" . $image . "',
				`sort_order` = '" . (int)($idx . '') . "'");
		}

		$this->setNewsSeoKeywords($news_id, $data['seo_keyword'] ?? []);

		return $news_id;
	}

	public function editNews(int $news_id, array $data): void {
		$this->db->query("UPDATE `" . DB_PREFIX . "news` SET
			`image` = '" . $this->db->escape($data['image'] ?? '') . "',
			`logo` = '" . $this->db->escape($data['logo'] ?? '') . "',
			`video` = '" . $this->db->escape($data['video'] ?? '') . "',
			`sort_order` = '" . (int)($data['sort_order'] ?? 0) . "',
			`status` = '" . (int)($data['status'] ?? 1) . "',
			`date_modified` = NOW()
			WHERE `news_id` = '" . (int)$news_id . "'");

		$this->db->query("DELETE FROM `" . DB_PREFIX . "news_description` WHERE `news_id` = '" . (int)$news_id . "'");
		foreach ($data['news_description'] ?? [] as $language_id => $desc) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "news_description` SET
				`news_id` = '" . (int)$news_id . "',
				`language_id` = '" . (int)$language_id . "',
				`heading` = '" . $this->db->escape($desc['heading'] ?? '') . "',
				`title` = '" . $this->db->escape($desc['title'] ?? '') . "',
				`description` = '" . $this->db->escape($desc['description'] ?? '') . "',
				`article` = '" . $this->db->escape($desc['article'] ?? '') . "',
				`meta_title` = '" . $this->db->escape($desc['meta_title'] ?? '') . "',
				`meta_description` = '" . $this->db->escape($desc['meta_description'] ?? '') . "',
				`meta_keyword` = '" . $this->db->escape($desc['meta_keyword'] ?? '') . "'");
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "news_image` WHERE `news_id` = '" . (int)$news_id . "'");
		foreach ($data['news_image'] ?? [] as $idx => $img) {
			$image = $this->db->escape($img['image'] ?? '');
			if ($image === '') continue;
			$this->db->query("INSERT INTO `" . DB_PREFIX . "news_image` SET
				`news_id` = '" . (int)$news_id . "',
				`image` = '" . $image . "',
				`sort_order` = '" . (int)($idx . '') . "'");
		}

		$this->setNewsSeoKeywords($news_id, $data['seo_keyword'] ?? []);
	}

	/**
	 * Clone news with all descriptions and gallery images.
	 * Adds " (copy)" suffix to titles.
	 */
	public function copyNews(int $news_id): int {
		$news = $this->getNews($news_id);
		if (empty($news)) {
			return 0;
		}

		$descriptions = $this->getNewsDescriptions($news_id);
		$copySuffix = ' (copy)';
		foreach ($descriptions as $lang_id => $desc) {
			$descriptions[$lang_id]['title'] = ($desc['title'] ?? '') . $copySuffix;
			$descriptions[$lang_id]['heading'] = ($desc['heading'] ?? '') . $copySuffix;
			$descriptions[$lang_id]['meta_title'] = ($desc['meta_title'] ?? '') . $copySuffix;
		}

		$images = $this->getNewsImages($news_id);
		$news_image = [];
		foreach ($images as $idx => $img) {
			$news_image[] = ['image' => $img['image'] ?? ''];
		}

		$data = [
			'image'   => $news['image'] ?? '',
			'logo'    => $news['logo'] ?? '',
			'video'   => $news['video'] ?? '',
			'sort_order' => (int)($news['sort_order'] ?? 0),
			'status'  => (int)($news['status'] ?? 1),
			'news_description' => $descriptions,
			'news_image' => $news_image,
		];

		return $this->addNews($data);
	}

	public function deleteNews(int $news_id): void {
		$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE `store_id` = '0' AND `key` = 'news_id' AND `value` = '" . (int)$news_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "news_image` WHERE `news_id` = '" . (int)$news_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "news_description` WHERE `news_id` = '" . (int)$news_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "news` WHERE `news_id` = '" . (int)$news_id . "'");
	}

	public function getTotalNewss(): int {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "news`");
		return (int)$query->row['total'];
	}
}
