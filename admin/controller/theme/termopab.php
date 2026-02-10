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

		$this->load->model('setting/setting');
		$this->load->model('localisation/language');
		$this->load->model('catalog/category');
		$this->load->model('catalog/information');

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

		$social_keys = ['instagram', 'whatsapp', 'telegram', 'facebook', 'youtube'];
		foreach ($social_keys as $key) {
			$data['theme_termopab_social_' . $key] = isset($setting_info['theme_termopab_social_' . $key]) ? $setting_info['theme_termopab_social_' . $key] : '';
			// Header/footer display: default 1 (show) when not set
			$data['theme_termopab_header_social_' . $key] = isset($setting_info['theme_termopab_header_social_' . $key]) ? (int)$setting_info['theme_termopab_header_social_' . $key] : 1;
			$data['theme_termopab_footer_social_' . $key] = isset($setting_info['theme_termopab_footer_social_' . $key]) ? (int)$setting_info['theme_termopab_footer_social_' . $key] : 1;
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

			$this->model_setting_setting->editSetting('theme_termopab', $post, $store_id);

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	/**
	 * Install - set default theme status
	 *
	 * @return void
	 */
	public function install(): void {
	    if ($this->user->hasPermission('modify', 'extension/theme')) {
	      // Add startup to catalog
	      $startup_data = [
	        'code'        => 'termopab',
	        'description' => 'termopab theme extension',
	        'action'      => 'catalog/extension/termopab/startup/termopab',
	        'status'      => 1,
	        'sort_order'  => 2
	      ];

	      // Add startup for admin
	      $this->load->model('setting/startup');

	      $this->model_setting_startup->addStartup($startup_data);
	    }
	  }
}
