<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Theme;
/**
 * Class Termopab
 *
 * @package Opencart\Admin\Controller\Extension\Termopab\Theme
 */
class Termopab extends \Opencart\System\Engine\Controller {
	/**
	 * Index
	 *
	 * @return void
	 */
	public function index(): void {
		$this->load->language('extension/termopab/theme/termopab');

		// OC4: menu via Event System (extension/termopab/event/menu)
		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->request->get['store_id'])) {
			$store_id = (int)$this->request->get['store_id'];
		} else {
			$store_id = 0;
		}

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=theme')
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/theme/termopab', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $store_id)
		];

		$data['save'] = $this->url->link('extension/termopab/theme/termopab.save', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $store_id);
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=theme');
		$data['install_project_tables'] = $this->url->link('extension/termopab/project', 'user_token=' . $this->session->data['user_token'] . '&install_tables=1');
		$data['projects_list'] = $this->url->link('extension/termopab/project', 'user_token=' . $this->session->data['user_token']);
		$data['projects_add'] = $this->url->link('extension/termopab/project.form', 'user_token=' . $this->session->data['user_token']);
		$data['brewery_reviews_list'] = $this->url->link('extension/termopab/brewery_review', 'user_token=' . $this->session->data['user_token']);
		$data['brewery_reviews_add'] = $this->url->link('extension/termopab/brewery_review.form', 'user_token=' . $this->session->data['user_token']);
		$data['brewery_reviews_add_permission'] = $this->url->link('extension/termopab/theme/termopab.addBreweryReviewPermission', 'user_token=' . $this->session->data['user_token']);
		$data['install_full_tables'] = $this->url->link('extension/termopab/theme/termopab.runFullInstall', 'user_token=' . $this->session->data['user_token']);
		$data['sync_events'] = $this->url->link('extension/termopab/theme/termopab.syncEvents', 'user_token=' . $this->session->data['user_token']);

		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$this->load->model('catalog/category');
		$this->load->model('catalog/information');
		$this->load->model('design/layout');

		$data['layouts'] = $this->model_design_layout->getLayouts();

		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['categories'] = $this->model_catalog_category->getCategories(['filter_parent_id' => 0]);
		$data['categories_flat'] = $this->model_catalog_category->getCategories();
		$data['informations'] = $this->model_catalog_information->getInformations() ?: [];
		$data['menu_languages'] = array_values($data['languages']);
		$data['menu_informations'] = array_values($data['informations']);

		$setting_info = $this->model_setting_setting->getSetting('theme_termopab', $store_id);

		if (isset($setting_info['theme_termopab_status'])) {
			$data['theme_termopab_status'] = $setting_info['theme_termopab_status'];
		} else {
			$data['theme_termopab_status'] = '';
		}

		$data['theme_termopab_brand'] = isset($setting_info['theme_termopab_brand']) ? $setting_info['theme_termopab_brand'] : [];
		$data['theme_termopab_address'] = isset($setting_info['theme_termopab_address']) ? $setting_info['theme_termopab_address'] : [];
		$telephone_raw = isset($setting_info['theme_termopab_telephone']) ? $setting_info['theme_termopab_telephone'] : '';
		$data['theme_termopab_telephones'] = is_string($telephone_raw) ? array_filter(array_map('trim', explode("\n", $telephone_raw))) : [];
		$email_raw = isset($setting_info['theme_termopab_email']) ? $setting_info['theme_termopab_email'] : '';
		$data['theme_termopab_email'] = is_array($email_raw) ? (string)reset($email_raw) : (string)$email_raw;
		$data['theme_termopab_schedule'] = isset($setting_info['theme_termopab_schedule']) ? $setting_info['theme_termopab_schedule'] : [];
		$data['theme_termopab_worknote'] = isset($setting_info['theme_termopab_worknote']) ? $setting_info['theme_termopab_worknote'] : [];
		$data['theme_termopab_delivery_payment'] = isset($setting_info['theme_termopab_delivery_payment']) ? $setting_info['theme_termopab_delivery_payment'] : [];
		$data['theme_termopab_exchange_return'] = isset($setting_info['theme_termopab_exchange_return']) ? $setting_info['theme_termopab_exchange_return'] : [];

		$this->document->addScript('view/javascript/ckeditor/ckeditor.js');
		$this->document->addScript('view/javascript/ckeditor/adapters/jquery.js');
		$this->load->language('default');
		$data['ckeditor'] = $this->language->get('ckeditor') ?: 'en';

		$social_keys = ['instagram', 'whatsapp', 'telegram', 'facebook', 'youtube'];
		foreach ($social_keys as $key) {
			$data['theme_termopab_social_' . $key] = isset($setting_info['theme_termopab_social_' . $key]) ? $setting_info['theme_termopab_social_' . $key] : '';
			// Header/footer display: default 1 (show) when not set
			$data['theme_termopab_header_social_' . $key] = isset($setting_info['theme_termopab_header_social_' . $key]) ? (int)$setting_info['theme_termopab_header_social_' . $key] : 1;
			$data['theme_termopab_footer_social_' . $key] = isset($setting_info['theme_termopab_footer_social_' . $key]) ? (int)$setting_info['theme_termopab_footer_social_' . $key] : 1;
		}

		$data['theme_termopab_modal_payment_address']  = isset($setting_info['theme_termopab_modal_payment_address']) ? (int)$setting_info['theme_termopab_modal_payment_address'] : 0;
		$data['theme_termopab_modal_shipping_address'] = isset($setting_info['theme_termopab_modal_shipping_address']) ? (int)$setting_info['theme_termopab_modal_shipping_address'] : 0;
		$modalField = function ($key) use ($setting_info) {
			return !isset($setting_info['theme_termopab_modal_field_' . $key]) || $setting_info['theme_termopab_modal_field_' . $key];
		};
		$data['theme_termopab_modal_field_country']   = $modalField('country');
		$data['theme_termopab_modal_field_zone']     = $modalField('zone');
		$data['theme_termopab_modal_field_city']     = $modalField('city');
		$data['theme_termopab_modal_field_address_1'] = $modalField('address_1');
		$data['theme_termopab_modal_field_address_2'] = $modalField('address_2');
		$data['theme_termopab_modal_field_company']   = $modalField('company');
		$data['theme_termopab_modal_field_postcode']  = $modalField('postcode');
		$data['theme_termopab_modal_address_field_order'] = isset($setting_info['theme_termopab_modal_address_field_order']) ? $setting_info['theme_termopab_modal_address_field_order'] : '';

		// Ordered list of address fields (key + label) for sortable UI in checkout tab
		$allowed_order_keys = ['country', 'zone', 'city', 'address_1', 'address_2', 'company', 'postcode'];
		$field_labels = [
			'country'   => $this->language->get('entry_modal_field_country'),
			'zone'      => $this->language->get('entry_modal_field_zone'),
			'city'      => $this->language->get('entry_modal_field_city'),
			'address_1' => $this->language->get('entry_modal_field_address_1'),
			'address_2' => $this->language->get('entry_modal_field_address_2'),
			'company'   => $this->language->get('entry_modal_field_company'),
			'postcode'  => $this->language->get('entry_modal_field_postcode'),
		];
		$order_raw = $data['theme_termopab_modal_address_field_order'];
		$order = [];
		if (is_string($order_raw) && $order_raw !== '') {
			$order = array_values(array_intersect($allowed_order_keys, array_unique(array_map('trim', explode(',', $order_raw)))));
		}
		if (empty($order)) {
			$order = $allowed_order_keys;
		}
		$data['modal_address_fields_ordered'] = [];
		foreach ($order as $key) {
			$data['modal_address_fields_ordered'][] = ['key' => $key, 'label' => $field_labels[$key] ?? $key];
		}

		$data['footer_menu_use_main'] = !isset($setting_info['theme_termopab_footer_menu_use_main']) || $setting_info['theme_termopab_footer_menu_use_main'];
		$data['footer_menu_items'] = [];
		if (!empty($setting_info['theme_termopab_footer_menu'])) {
			$items = is_string($setting_info['theme_termopab_footer_menu'])
				? json_decode($setting_info['theme_termopab_footer_menu'], true) : $setting_info['theme_termopab_footer_menu'];
			$data['footer_menu_items'] = is_array($items) ? array_values($items) : [];
		}

		foreach ([1, 2, 3] as $col) {
			$key = 'theme_termopab_menu_column' . $col;
			$raw = $setting_info[$key] ?? null;
			$items = is_string($raw) ? json_decode($raw, true) : $raw;
			$items = is_array($items) ? array_values($items) : [];

			// Migrate old column1 format: [id, id, id] -> [{type:'category', category_id:id}, ...]
			if ($col === 1 && !empty($items) && is_numeric($items[0])) {
				$migrated = [];
				foreach ($items as $cid) {
					$cid = (int)$cid;
					if ($cid > 0) {
						$migrated[] = ['type' => 'category', 'category_id' => $cid];
					}
				}
				$items = $migrated;
			}

			$data['menu_column' . $col] = $items;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/theme/termopab', $data));
	}

	/**
	 * Add permissions for "Огляди пивоварень" and "Категорії оглядів" to current user's group; redirect to brewery review list.
	 * Use when the user has no checkbox for these routes in User Groups (e.g. after manual install).
	 */
	public function addBreweryReviewPermission(): void {
		$this->load->language('extension/termopab/theme/termopab');

		if (!$this->user->hasPermission('modify', 'extension/termopab/theme/termopab')) {
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('extension/termopab/theme/termopab', 'user_token=' . $this->session->data['user_token']));
			return;
		}

		$this->load->model('user/user_group');
		$group_id = $this->user->getGroupId();
		$this->model_user_user_group->addPermission($group_id, 'access', 'extension/termopab/brewery_review');
		$this->model_user_user_group->addPermission($group_id, 'modify', 'extension/termopab/brewery_review');
		$this->model_user_user_group->addPermission($group_id, 'access', 'extension/termopab/brewery_review_category');
		$this->model_user_user_group->addPermission($group_id, 'modify', 'extension/termopab/brewery_review_category');

		$this->session->data['success'] = $this->language->get('text_brewery_reviews_permission_added');
		$this->response->redirect($this->url->link('extension/termopab/brewery_review', 'user_token=' . $this->session->data['user_token']));
	}

	/**
	 * Sync events — register Termopab events without reinstalling.
	 * Use when events were added in code but theme was not reinstalled.
	 */
	public function syncEvents(): void {
		$this->load->language('extension/termopab/theme/termopab');
		if (!$this->user->hasPermission('modify', 'extension/termopab/theme/termopab')) {
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('extension/termopab/theme/termopab', 'user_token=' . $this->session->data['user_token']));
			return;
		}
		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('termopab_admin_menu');
		$this->model_setting_event->addEvent(['code' => 'termopab_admin_menu', 'description' => 'Termopab: меню «Тема Termopab» і «Проекти»', 'trigger' => 'admin/view/common/column_left/before', 'action' => 'extension/termopab/event/menu.onColumnLeft', 'status' => 1, 'sort_order' => 0]);
		$this->model_setting_event->deleteEventByCode('termopab_admin_category');
		$this->model_setting_event->addEvent(['code' => 'termopab_admin_category', 'description' => 'Termopab: category form custom fields', 'trigger' => 'admin/view/catalog/category_form/before', 'action' => 'extension/termopab/event/category.onCategoryFormBefore', 'status' => 1, 'sort_order' => 0]);
		$this->model_setting_event->addEvent(['code' => 'termopab_admin_category', 'description' => 'Termopab: category save custom fields (add)', 'trigger' => 'admin/model/catalog/category.addCategory/after', 'action' => 'extension/termopab/event/category.onAddCategoryAfter', 'status' => 1, 'sort_order' => 0]);
		$this->model_setting_event->addEvent(['code' => 'termopab_admin_category', 'description' => 'Termopab: category save custom fields (edit)', 'trigger' => 'admin/model/catalog/category.editCategory/after', 'action' => 'extension/termopab/event/category.onEditCategoryAfter', 'status' => 1, 'sort_order' => 0]);
		$this->model_setting_event->deleteEventByCode('termopab_admin_category_builder');
		$this->model_setting_event->addEvent(['code' => 'termopab_admin_category_builder', 'description' => 'Termopab: category builder form data', 'trigger' => 'admin/view/catalog/category_form/before', 'action' => 'extension/termopab/event/category_builder.onCategoryFormBefore', 'status' => 1, 'sort_order' => 1]);
		$this->model_setting_event->addEvent(['code' => 'termopab_admin_category_builder', 'description' => 'Termopab: category builder save (add)', 'trigger' => 'admin/model/catalog/category.addCategory/after', 'action' => 'extension/termopab/event/category_builder.onAddCategoryAfter', 'status' => 1, 'sort_order' => 0]);
		$this->model_setting_event->addEvent(['code' => 'termopab_admin_category_builder', 'description' => 'Termopab: category builder save (edit)', 'trigger' => 'admin/model/catalog/category.editCategory/after', 'action' => 'extension/termopab/event/category_builder.onEditCategoryAfter', 'status' => 1, 'sort_order' => 0]);
		$this->model_setting_event->deleteEventByCode('termopab_catalog_category');
		$this->model_setting_event->addEvent(['code' => 'termopab_catalog_category', 'description' => 'Termopab: category layout parent/child', 'trigger' => 'catalog/view/product/category/before', 'action' => 'extension/termopab/event/category.onCategoryViewBefore', 'status' => 1, 'sort_order' => 50]);
		$this->model_setting_event->deleteEventByCode('termopab_admin_product');
		$this->model_setting_event->addEvent(['code' => 'termopab_admin_product', 'description' => 'Termopab: override admin catalog/product controller', 'trigger' => 'admin/controller/catalog/product*/before', 'action' => 'extension/termopab/event/product.onAdminProductBefore', 'status' => 1, 'sort_order' => -100]);
		$this->model_setting_event->addEvent(['code' => 'termopab_admin_product', 'description' => 'Termopab: product_form view override (360-view tab)', 'trigger' => 'admin/view/catalog/product_form/before', 'action' => 'extension/termopab/event/product.onProductFormViewBefore', 'status' => 1, 'sort_order' => -100]);
		$this->model_setting_event->addEvent(['code' => 'termopab_admin_product', 'description' => 'Termopab: save view_360 (addProduct)', 'trigger' => 'admin/model/catalog/product.addProduct/after', 'action' => 'extension/termopab/event/product.onAddProductAfter', 'status' => 1, 'sort_order' => 0]);
		$this->model_setting_event->addEvent(['code' => 'termopab_admin_product', 'description' => 'Termopab: save view_360 (editProduct)', 'trigger' => 'admin/model/catalog/product.editProduct/after', 'action' => 'extension/termopab/event/product.onEditProductAfter', 'status' => 1, 'sort_order' => 0]);
		$this->model_setting_event->deleteEventByCode('termopab_catalog_product');
		$this->model_setting_event->addEvent(['code' => 'termopab_catalog_product', 'description' => 'Termopab: product view view_360 + button_cart', 'trigger' => 'catalog/view/product/product/before', 'action' => 'extension/termopab/event/product_view.onProductViewBefore', 'status' => 1, 'sort_order' => 0]);
		$this->addGlbToAllowedUploads();
		$this->session->data['success'] = 'Події Termopab зареєстровано.';
		$this->response->redirect($this->url->link('extension/termopab/theme/termopab', 'user_token=' . $this->session->data['user_token']));
	}

	/**
	 * Run full install (project + brewery + category columns) from theme settings.
	 * Uses theme permission — no need for extension/termopab/install access.
	 */
	public function runFullInstall(): void {
		$this->load->language('extension/termopab/theme/termopab');

		if (!$this->user->hasPermission('modify', 'extension/termopab/theme/termopab')) {
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('extension/termopab/theme/termopab', 'user_token=' . $this->session->data['user_token']));
			return;
		}

		$this->load->controller('extension/termopab/install');
	}

	/**
	 * Save
	 *
	 * @return void
	 */
	public function save(): void {
		$this->load->language('extension/termopab/theme/termopab');

		$json = [];

		if (isset($this->request->get['store_id'])) {
			$store_id = (int)$this->request->get['store_id'];
		} else {
			$store_id = 0;
		}

		if (!$this->user->hasPermission('modify', 'extension/termopab/theme/termopab')) {
			$json['error'] = $this->language->get('error_permission');
		}

		if (!$json) {
			$this->load->model('setting/setting');

			$social_defaults = [];
			foreach (['instagram', 'whatsapp', 'telegram', 'facebook', 'youtube'] as $key) {
				$social_defaults['theme_termopab_social_' . $key] = '';
				$social_defaults['theme_termopab_header_social_' . $key] = 1;
				$social_defaults['theme_termopab_footer_social_' . $key] = 1;
			}
			$post = array_merge([
				'theme_termopab_status'   => 0,
				'theme_termopab_brand'   => [],
				'theme_termopab_address' => [],
				'theme_termopab_telephone' => '',
				'theme_termopab_email'   => '',
				'theme_termopab_schedule'=> [],
				'theme_termopab_worknote'=> [],
				'theme_termopab_delivery_payment' => [],
				'theme_termopab_exchange_return' => [],
				'theme_termopab_modal_payment_address'  => 0,
				'theme_termopab_modal_shipping_address' => 0,
				'theme_termopab_modal_field_country'   => 1,
				'theme_termopab_modal_field_zone'     => 1,
				'theme_termopab_modal_field_city'     => 1,
				'theme_termopab_modal_field_address_1' => 1,
				'theme_termopab_modal_field_address_2' => 1,
				'theme_termopab_modal_field_company'   => 1,
				'theme_termopab_modal_field_postcode'  => 1,
				'theme_termopab_footer_menu_use_main' => 1,
				'theme_termopab_footer_menu' => [],
				'theme_termopab_menu_column1' => [],
				'theme_termopab_menu_column2' => [],
				'theme_termopab_menu_column3' => [],
			], $social_defaults, $this->request->post);

			if (isset($post['theme_termopab_footer_menu_use_main'])) {
				$post['theme_termopab_footer_menu_use_main'] = (int)$post['theme_termopab_footer_menu_use_main'];
			}
			if (isset($post['theme_termopab_footer_menu']) && is_array($post['theme_termopab_footer_menu'])) {
				$post['theme_termopab_footer_menu'] = json_encode(array_values($post['theme_termopab_footer_menu']));
			}

			foreach ([1, 2, 3] as $col) {
				$key = 'theme_termopab_menu_column' . $col;
				if (isset($post[$key]) && is_array($post[$key])) {
					$post[$key] = json_encode(array_values($post[$key]));
				}
			}

			if (isset($post['theme_termopab_telephone']) && is_array($post['theme_termopab_telephone'])) {
				$post['theme_termopab_telephone'] = implode("\n", array_filter(array_map('trim', $post['theme_termopab_telephone'])));
			}
			if (isset($post['theme_termopab_email']) && is_array($post['theme_termopab_email'])) {
				$post['theme_termopab_email'] = (string)reset($post['theme_termopab_email']);
			}
			// Checkboxes: unchecked = not sent in POST, so check request->post directly (merge had defaults 1)
			foreach (['instagram', 'whatsapp', 'telegram', 'facebook', 'youtube'] as $key) {
				$post['theme_termopab_header_social_' . $key] = isset($this->request->post['theme_termopab_header_social_' . $key]) && $this->request->post['theme_termopab_header_social_' . $key] ? 1 : 0;
				$post['theme_termopab_footer_social_' . $key] = isset($this->request->post['theme_termopab_footer_social_' . $key]) && $this->request->post['theme_termopab_footer_social_' . $key] ? 1 : 0;
			}

			$post['theme_termopab_modal_payment_address']  = isset($this->request->post['theme_termopab_modal_payment_address']) && $this->request->post['theme_termopab_modal_payment_address'] ? 1 : 0;
			$post['theme_termopab_modal_shipping_address'] = isset($this->request->post['theme_termopab_modal_shipping_address']) && $this->request->post['theme_termopab_modal_shipping_address'] ? 1 : 0;
			foreach (['country', 'zone', 'city', 'address_1', 'address_2', 'company', 'postcode'] as $key) {
				$post['theme_termopab_modal_field_' . $key] = isset($this->request->post['theme_termopab_modal_field_' . $key]) && $this->request->post['theme_termopab_modal_field_' . $key] ? 1 : 0;
			}
			$post['theme_termopab_modal_address_field_order'] = isset($this->request->post['theme_termopab_modal_address_field_order']) ? trim((string)$this->request->post['theme_termopab_modal_address_field_order']) : '';

			$this->model_setting_setting->editSetting('theme_termopab', $post, $store_id);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Install - startup + event for admin menu
	 *
	 * @return void
	 */
	public function install(): void {
		if ($this->user->hasPermission('modify', 'extension/theme')) {
			$prefix = defined('DB_PREFIX') ? DB_PREFIX : 'oc_';
			try {
				$this->db->query("CREATE TABLE IF NOT EXISTS `" . $prefix . "category_content` (
					`category_content_id` int(11) NOT NULL AUTO_INCREMENT,
					`category_id` int(11) NOT NULL,
					`store_id` int(11) NOT NULL DEFAULT 0,
					`layout_type` varchar(32) NOT NULL,
					`position` varchar(32) NOT NULL,
					`code` varchar(64) NOT NULL,
					`sort_order` int(11) NOT NULL DEFAULT 0,
					PRIMARY KEY (`category_content_id`),
					KEY `category_store_layout` (`category_id`,`store_id`,`layout_type`,`position`,`sort_order`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
			} catch (\Throwable $e) {
			}

			$category_alters = [
				"ALTER TABLE `" . $prefix . "category` ADD COLUMN `hero_image` varchar(255) DEFAULT NULL",
				"ALTER TABLE `" . $prefix . "category` ADD COLUMN `hero_image_mobile` varchar(255) DEFAULT NULL",
				"ALTER TABLE `" . $prefix . "category` ADD COLUMN `breadcrumb_background` varchar(32) DEFAULT 'black'",
			];
			foreach ($category_alters as $query) {
				try {
					$this->db->query($query);
				} catch (\Throwable $e) {
				}
			}
			try {
				$this->db->query("ALTER TABLE `" . $prefix . "product` ADD COLUMN `view_360` varchar(255) DEFAULT NULL");
			} catch (\Throwable $e) {
			}
			try {
				$this->db->query("ALTER TABLE `" . $prefix . "product` ADD COLUMN `main_video` varchar(255) DEFAULT NULL");
			} catch (\Throwable $e) {
			}
			try {
				$this->db->query("ALTER TABLE `" . $prefix . "product` ADD COLUMN `main_video_poster` varchar(255) DEFAULT NULL");
			} catch (\Throwable $e) {
			}
			try {
				$this->db->query("ALTER TABLE `" . $prefix . "product` ADD COLUMN `video_review` text DEFAULT NULL");
			} catch (\Throwable $e) {
			}
			try {
				$this->db->query("ALTER TABLE `" . $prefix . "product_description` ADD COLUMN `short_description` text DEFAULT NULL");
			} catch (\Throwable $e) {
			}
			try {
				$this->db->query("ALTER TABLE `" . $prefix . "product_description` ADD COLUMN `description_characteristics` text DEFAULT NULL");
			} catch (\Throwable $e) {
			}

			// Ensure termopab is in extension_install so autoloader registers paths (required for event menu)
			$this->load->model('setting/extension');
			if (!$this->model_setting_extension->getInstallByCode('termopab')) {
				$id = $this->model_setting_extension->addInstall([
					'extension_id' => 0, 'extension_download_id' => 0,
					'name' => 'Termopab', 'description' => 'Termopab theme', 'code' => 'termopab',
					'version' => '1.0', 'author' => '', 'link' => '',
				]);
				$this->model_setting_extension->editStatus($id, true);
			}

			// Add permissions for all user groups (whoever can manage themes gets termopab access)
			$this->load->model('user/user_group');
			$routes = [
				'extension/termopab/theme/termopab', 'extension/termopab/project', 'extension/termopab/brewery_review', 'extension/termopab/brewery_review_category',
				'extension/termopab/install',
				'extension/termopab/module/projects_slider',
				'extension/termopab/module/brewery_reviews_slider',
				'extension/termopab/module/callback_form',
				'extension/termopab/module/cta_barrel',
				'extension/termopab/module/cta_spikelet',
				'extension/termopab/module/cat_promo_thin',
				'extension/termopab/module/subcat_promo_lg',
				'extension/termopab/module/cta_thin_cap',
				'extension/termopab/module/subcat_promo',
				'extension/termopab/module/cta_barometer',
			];
			$groups = $this->db->query("SELECT user_group_id, name, permission FROM `" . DB_PREFIX . "user_group`")->rows;
			foreach ($groups as $row) {
				$perm = $row['permission'] ? json_decode($row['permission'], true) : ['access' => [], 'modify' => []];
				foreach (['access', 'modify'] as $type) {
					$list = $perm[$type] ?? [];
					foreach ($routes as $route) {
						if (!in_array($route, $list, true)) {
							$list[] = $route;
						}
					}
					$perm[$type] = $list;
				}
				$this->db->query("UPDATE `" . DB_PREFIX . "user_group` SET `permission` = '" . $this->db->escape(json_encode($perm)) . "' WHERE `user_group_id` = '" . (int)$row['user_group_id'] . "'");
			}

			// Add startup to catalog
			$this->load->model('setting/startup');
			$this->model_setting_startup->addStartup([
				'code'        => 'termopab',
				'description' => 'termopab theme extension',
				'action'      => 'catalog/extension/termopab/startup/termopab',
				'status'      => 1,
				'sort_order'  => 2
			]);

			// Event: admin menu (Тема Termopab + Проекти) — no OCMOD
			$this->load->model('setting/event');
			$this->model_setting_event->deleteEventByCode('termopab_admin_menu');
			$this->model_setting_event->addEvent([
				'code'        => 'termopab_admin_menu',
				'description' => 'Termopab: меню «Тема Termopab» і «Проекти» в розділі Дизайн',
				'trigger'     => 'admin/view/common/column_left/before',
				'action'      => 'extension/termopab/event/menu.onColumnLeft',
				'status'      => 1,
				'sort_order'  => 0,
			]);

			// Events: category form custom fields (hero_image, breadcrumb_background)
			$this->model_setting_event->deleteEventByCode('termopab_admin_category');
			$this->model_setting_event->addEvent([
				'code'        => 'termopab_admin_category',
				'description' => 'Termopab: category form custom fields',
				'trigger'     => 'admin/view/catalog/category_form/before',
				'action'      => 'extension/termopab/event/category.onCategoryFormBefore',
				'status'      => 1,
				'sort_order'  => 0,
			]);
			$this->model_setting_event->addEvent([
				'code'        => 'termopab_admin_category',
				'description' => 'Termopab: category save custom fields (add)',
				'trigger'     => 'admin/model/catalog/category.addCategory/after',
				'action'      => 'extension/termopab/event/category.onAddCategoryAfter',
				'status'      => 1,
				'sort_order'  => 0,
			]);
			$this->model_setting_event->addEvent([
				'code'        => 'termopab_admin_category',
				'description' => 'Termopab: category save custom fields (edit)',
				'trigger'     => 'admin/model/catalog/category.editCategory/after',
				'action'      => 'extension/termopab/event/category.onEditCategoryAfter',
				'status'      => 1,
				'sort_order'  => 0,
			]);

			$this->model_setting_event->deleteEventByCode('termopab_admin_category_builder');
			$this->model_setting_event->addEvent(['code' => 'termopab_admin_category_builder', 'description' => 'Termopab: category builder form data', 'trigger' => 'admin/view/catalog/category_form/before', 'action' => 'extension/termopab/event/category_builder.onCategoryFormBefore', 'status' => 1, 'sort_order' => 1]);
			$this->model_setting_event->addEvent(['code' => 'termopab_admin_category_builder', 'description' => 'Termopab: category builder save (add)', 'trigger' => 'admin/model/catalog/category.addCategory/after', 'action' => 'extension/termopab/event/category_builder.onAddCategoryAfter', 'status' => 1, 'sort_order' => 0]);
			$this->model_setting_event->addEvent(['code' => 'termopab_admin_category_builder', 'description' => 'Termopab: category builder save (edit)', 'trigger' => 'admin/model/catalog/category.editCategory/after', 'action' => 'extension/termopab/event/category_builder.onEditCategoryAfter', 'status' => 1, 'sort_order' => 0]);

			// Event: catalog category layout switch + per-category modules
			$this->model_setting_event->deleteEventByCode('termopab_catalog_category');
			$this->model_setting_event->addEvent([
				'code'        => 'termopab_catalog_category',
				'description' => 'Termopab: category layout parent/child + per-category modules',
				'trigger'     => 'catalog/view/product/category/before',
				'action'      => 'extension/termopab/event/category.onCategoryViewBefore',
				'status'      => 1,
				'sort_order'  => 50,
			]);
			// Event: override admin catalog/product controller (for 360-view tab etc.)
			$this->model_setting_event->deleteEventByCode('termopab_admin_product');
			$this->model_setting_event->addEvent([
				'code'        => 'termopab_admin_product',
				'description' => 'Termopab: override admin catalog/product controller',
				'trigger'     => 'admin/controller/catalog/product*/before',
				'action'      => 'extension/termopab/event/product.onAdminProductBefore',
				'status'      => 1,
				'sort_order'  => -100,
			]);
			$this->model_setting_event->addEvent([
				'code'        => 'termopab_admin_product',
				'description' => 'Termopab: product_form view override (360-view tab)',
				'trigger'     => 'admin/view/catalog/product_form/before',
				'action'      => 'extension/termopab/event/product.onProductFormViewBefore',
				'status'      => 1,
				'sort_order'  => -100,
			]);
			$this->model_setting_event->addEvent(['code' => 'termopab_admin_product', 'description' => 'Termopab: save view_360 (addProduct)', 'trigger' => 'admin/model/catalog/product.addProduct/after', 'action' => 'extension/termopab/event/product.onAddProductAfter', 'status' => 1, 'sort_order' => 0]);
			$this->model_setting_event->addEvent(['code' => 'termopab_admin_product', 'description' => 'Termopab: save view_360 (editProduct)', 'trigger' => 'admin/model/catalog/product.editProduct/after', 'action' => 'extension/termopab/event/product.onEditProductAfter', 'status' => 1, 'sort_order' => 0]);
			$this->model_setting_event->deleteEventByCode('termopab_catalog_product');
			$this->model_setting_event->addEvent(['code' => 'termopab_catalog_product', 'description' => 'Termopab: product view view_360 + button_cart', 'trigger' => 'catalog/view/product/product/before', 'action' => 'extension/termopab/event/product_view.onProductViewBefore', 'status' => 1, 'sort_order' => 0]);
			$this->addGlbToAllowedUploads();
		}
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

	/**
	 * Uninstall - remove startup and event
	 *
	 * @return void
	 */
	public function uninstall(): void {
		$this->load->model('setting/startup');
		$this->model_setting_startup->deleteStartupByCode('termopab');

		$this->load->model('setting/event');
		$this->model_setting_event->deleteEventByCode('termopab_admin_menu');
		$this->model_setting_event->deleteEventByCode('termopab_admin_category');
		$this->model_setting_event->deleteEventByCode('termopab_admin_category_builder');
		$this->model_setting_event->deleteEventByCode('termopab_catalog_category');
		$this->model_setting_event->deleteEventByCode('termopab_admin_product');
		$this->model_setting_event->deleteEventByCode('termopab_catalog_product');
	}

}
