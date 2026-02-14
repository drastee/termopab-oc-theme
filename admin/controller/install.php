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
		];

		$alters = [
			"ALTER TABLE `" . $prefix . "project` ADD COLUMN `logo` varchar(255) DEFAULT NULL AFTER `image`",
			"ALTER TABLE `" . $prefix . "project` ADD COLUMN `video` varchar(512) DEFAULT NULL AFTER `logo`",
			"ALTER TABLE `" . $prefix . "project_description` ADD COLUMN `heading` varchar(255) DEFAULT NULL AFTER `language_id`",
			"ALTER TABLE `" . $prefix . "brewery_review` ADD COLUMN `brewery_review_category_id` int(11) NOT NULL DEFAULT 0 AFTER `brewery_review_id`",
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
			$this->session->data['success'] = 'Project tables created successfully.';
		} catch (\Throwable $e) {
			$this->session->data['error'] = 'Install error: ' . $e->getMessage();
		}

		$this->response->redirect($this->url->link('extension/termopab/project', 'user_token=' . $this->session->data['user_token']));
	}
}
