<?php
namespace Opencart\Admin\Controller\Extension\Termopab;

/**
 * One-time install: creates project tables if they do not exist.
 * Visit once after installing the extension: Extensions > Extensions > Termopab Theme,
 * or open: index.php?route=extension/termopab/install&user_token=...
 */
class Install extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$prefix = defined('DB_PREFIX') ? DB_PREFIX : 'tp_';

		$sql = [
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "project` (
				`project_id` int(11) NOT NULL AUTO_INCREMENT,
				`image` varchar(255) DEFAULT NULL,
				`logo` varchar(255) DEFAULT NULL,
				`video` varchar(512) DEFAULT NULL,
				`sort_order` int(11) NOT NULL DEFAULT 0,
				`status` tinyint(1) NOT NULL DEFAULT 1,
				`date_added` datetime NOT NULL,
				`date_modified` datetime NOT NULL,
				PRIMARY KEY (`project_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "project_description` (
				`project_id` int(11) NOT NULL,
				`language_id` int(11) NOT NULL,
				`heading` varchar(255) DEFAULT NULL,
				`title` varchar(255) NOT NULL,
				`description` text DEFAULT NULL,
				`article` mediumtext DEFAULT NULL,
				`meta_title` varchar(255) DEFAULT NULL,
				`meta_description` varchar(255) DEFAULT NULL,
				`meta_keyword` varchar(255) DEFAULT NULL,
				PRIMARY KEY (`project_id`,`language_id`),
				KEY `language_id` (`language_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "project_image` (
				`project_image_id` int(11) NOT NULL AUTO_INCREMENT,
				`project_id` int(11) NOT NULL,
				`image` varchar(255) NOT NULL,
				`sort_order` int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY (`project_image_id`),
				KEY `project_id` (`project_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "brewery_review` (
				`brewery_review_id` int(11) NOT NULL AUTO_INCREMENT,
				`brewery_review_category_id` int(11) NOT NULL DEFAULT 0,
				`image` varchar(255) DEFAULT NULL,
				`logo` varchar(255) DEFAULT NULL,
				`video` varchar(512) DEFAULT NULL,
				`sort_order` int(11) NOT NULL DEFAULT 0,
				`status` tinyint(1) NOT NULL DEFAULT 1,
				`date_added` datetime NOT NULL,
				`date_modified` datetime NOT NULL,
				PRIMARY KEY (`brewery_review_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "brewery_review_description` (
				`brewery_review_id` int(11) NOT NULL,
				`language_id` int(11) NOT NULL,
				`heading` varchar(255) DEFAULT NULL,
				`title` varchar(255) NOT NULL,
				`description` text DEFAULT NULL,
				`article` mediumtext DEFAULT NULL,
				`meta_title` varchar(255) DEFAULT NULL,
				`meta_description` varchar(255) DEFAULT NULL,
				`meta_keyword` varchar(255) DEFAULT NULL,
				PRIMARY KEY (`brewery_review_id`,`language_id`),
				KEY `language_id` (`language_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "brewery_review_image` (
				`brewery_review_image_id` int(11) NOT NULL AUTO_INCREMENT,
				`brewery_review_id` int(11) NOT NULL,
				`image` varchar(255) NOT NULL,
				`sort_order` int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY (`brewery_review_image_id`),
				KEY `brewery_review_id` (`brewery_review_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "brewery_review_category` (
				`brewery_review_category_id` int(11) NOT NULL AUTO_INCREMENT,
				`sort_order` int(11) NOT NULL DEFAULT 0,
				`status` tinyint(1) NOT NULL DEFAULT 1,
				`date_added` datetime NOT NULL,
				`date_modified` datetime NOT NULL,
				PRIMARY KEY (`brewery_review_category_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "brewery_review_category_description` (
				`brewery_review_category_id` int(11) NOT NULL,
				`language_id` int(11) NOT NULL,
				`title` varchar(255) NOT NULL,
				PRIMARY KEY (`brewery_review_category_id`,`language_id`),
				KEY `language_id` (`language_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "category_content` (
				`category_content_id` int(11) NOT NULL AUTO_INCREMENT,
				`category_id` int(11) NOT NULL,
				`store_id` int(11) NOT NULL DEFAULT 0,
				`layout_type` varchar(32) NOT NULL,
				`position` varchar(32) NOT NULL,
				`code` varchar(64) NOT NULL,
				`sort_order` int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY (`category_content_id`),
				KEY `category_store_layout` (`category_id`,`store_id`,`layout_type`,`position`,`sort_order`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
			"CREATE TABLE IF NOT EXISTS `" . $prefix . "product_extra_image` (
				`product_extra_image_id` int(11) NOT NULL AUTO_INCREMENT,
				`product_id` int(11) NOT NULL,
				`image` varchar(255) NOT NULL,
				`sort_order` int(11) NOT NULL DEFAULT 0,
				PRIMARY KEY (`product_extra_image_id`),
				KEY `product_id` (`product_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
		];

		$alters = [
			"ALTER TABLE `" . $prefix . "project` ADD COLUMN `logo` varchar(255) DEFAULT NULL AFTER `image`",
			"ALTER TABLE `" . $prefix . "project` ADD COLUMN `video` varchar(512) DEFAULT NULL AFTER `logo`",
			"ALTER TABLE `" . $prefix . "project_description` ADD COLUMN `heading` varchar(255) DEFAULT NULL AFTER `language_id`",
			"ALTER TABLE `" . $prefix . "brewery_review` ADD COLUMN `brewery_review_category_id` int(11) NOT NULL DEFAULT 0 AFTER `brewery_review_id`",
			"ALTER TABLE `" . $prefix . "category` ADD COLUMN `hero_image` varchar(255) DEFAULT NULL",
			"ALTER TABLE `" . $prefix . "category` ADD COLUMN `hero_image_mobile` varchar(255) DEFAULT NULL",
			"ALTER TABLE `" . $prefix . "category` ADD COLUMN `breadcrumb_background` varchar(32) DEFAULT 'black'",
			"ALTER TABLE `" . $prefix . "product` ADD COLUMN `view_360` varchar(255) DEFAULT NULL",
			"ALTER TABLE `" . $prefix . "product` ADD COLUMN `main_video` varchar(255) DEFAULT NULL",
			"ALTER TABLE `" . $prefix . "product` ADD COLUMN `main_video_poster` varchar(255) DEFAULT NULL",
			"ALTER TABLE `" . $prefix . "product` ADD COLUMN `video_review` text DEFAULT NULL",
			"ALTER TABLE `" . $prefix . "product_description` ADD COLUMN `short_description` text DEFAULT NULL",
			"ALTER TABLE `" . $prefix . "product_description` ADD COLUMN `description_characteristics` text DEFAULT NULL",
		];

		try {
			foreach ($sql as $query) {
				$this->db->query($query);
			}
			foreach ($alters as $query) {
				try {
					$this->db->query($query);
				} catch (\Throwable $e) {
					// Ignore duplicate column errors when columns already exist
				}
			}
			$this->addGlbToAllowedUploads();
			$this->session->data['success'] = 'Project tables created successfully.';
		} catch (\Throwable $e) {
			$this->session->data['error'] = 'Install error: ' . $e->getMessage();
		}

		$this->response->redirect($this->url->link('extension/termopab/project', 'user_token=' . $this->session->data['user_token']));
	}

	/**
	 * Add GLB and WebP to allowed uploads (360° view + WebP images).
	 * Uses \r\n as delimiter — OpenCart filemanager uses explode("\r\n", ...).
	 */
	private function addGlbToAllowedUploads(): void {
		$this->load->model('setting/setting');
		$stores = [0];
		$store_query = $this->db->query("SELECT store_id FROM `" . DB_PREFIX . "store`");
		if ($store_query->num_rows) {
			foreach ($store_query->rows as $row) {
				$stores[] = (int)$row['store_id'];
			}
		}
		$eol = "\r\n"; // Filemanager expects \r\n
		foreach ($stores as $store_id) {
			$config = $this->model_setting_setting->getSetting('config', $store_id);
			if (empty($config)) {
				continue;
			}
			$ext_raw = (string)($config['config_file_ext_allowed'] ?? '');
			$ext = preg_replace('~\r?\n~', "\n", $ext_raw);
			$list = array_filter(array_map('trim', explode("\n", $ext)));
			$ext_changed = false;
			foreach (['glb', 'webp'] as $add) {
				if (!in_array($add, $list, true)) {
					$list[] = $add;
					$ext_changed = true;
				}
			}
			$needs_normalize = (strpos($ext_raw, "\r\n") === false && strpos($ext_raw, "\n") !== false);
			if ($ext_changed || $needs_normalize) {
				$config['config_file_ext_allowed'] = implode($eol, $list);
			}
			$mime_raw = (string)($config['config_file_mime_allowed'] ?? '');
			$mime = preg_replace('~\r?\n~', "\n", $mime_raw);
			$list = array_filter(array_map('trim', explode("\n", $mime)));
			$mime_changed = false;
			foreach (['model/gltf-binary', 'image/webp'] as $add) {
				if (!in_array($add, $list, true)) {
					$list[] = $add;
					$mime_changed = true;
				}
			}
			$mime_needs_normalize = (strpos($mime_raw, "\r\n") === false && strpos($mime_raw, "\n") !== false);
			if ($mime_changed || $mime_needs_normalize) {
				$config['config_file_mime_allowed'] = implode($eol, $list);
			}
			if ($ext_changed || $needs_normalize || $mime_changed || $mime_needs_normalize) {
				$config_filtered = [];
				foreach ($config as $k => $v) {
					if (strpos((string)$k, 'config_') === 0) {
						$config_filtered[$k] = $v;
					}
				}
				if (!empty($config_filtered)) {
					$this->model_setting_setting->editSetting('config', $config_filtered, $store_id);
				}
			}
		}
	}
}
