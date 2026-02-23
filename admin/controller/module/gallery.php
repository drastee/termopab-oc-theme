<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Module;

class Gallery extends \Opencart\System\Engine\Controller {
	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');

		$this->load->language('extension/termopab/module/gallery');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['breadcrumbs'] = [];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
		];

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
		];

		$module_id = isset($this->request->get['module_id']) ? (int)$this->request->get['module_id'] : 0;

		$data['breadcrumbs'][] = [
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/termopab/module/gallery', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''))
		];

		$data['save'] = $this->url->link('extension/termopab/module/gallery.save', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''));
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$module_info = [];
		if ($module_id) {
			$this->load->model('setting/module');
			$module_info = $this->model_setting_module->getModule($module_id);
		}

		$data['name'] = $module_info['name'] ?? '';
		$data['status'] = $module_info['status'] ?? 1;
		$data['module_id'] = $module_id;

		$data['marquee_enabled'] = isset($module_info['marquee_enabled']) ? (int)$module_info['marquee_enabled'] : 1;
		$data['marquee_text'] = $module_info['marquee_text'] ?? [];

		$gallery_images = $module_info['gallery_image'] ?? [];
		if (!is_array($gallery_images)) {
			$gallery_images = [];
		}
		$this->load->model('tool/image');
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		$data['gallery_image'] = [];
		foreach ($gallery_images as $row) {
			$img = $row['image'] ?? '';
			$thumb = ($img && is_file(DIR_IMAGE . html_entity_decode($img, ENT_QUOTES, 'UTF-8')))
				? $this->model_tool_image->resize($img, 100, 100) : $data['placeholder'];
			$data['gallery_image'][] = [
				'image'       => $img,
				'image_thumb' => $thumb,
				'sort_order'  => (int)($row['sort_order'] ?? 0),
			];
		}
		usort($data['gallery_image'], function ($a, $b) {
			return $a['sort_order'] <=> $b['sort_order'];
		});

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();
		$data['user_token'] = $this->session->data['user_token'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/module/gallery', $data));
	}

	public function save(): void {
		$this->load->language('extension/termopab/module/gallery');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/module/gallery')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$name = $this->request->post['name'] ?? '';
		if (oc_strlen($name) < 3 || oc_strlen($name) > 64) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (!$json) {
			$gallery_image = [];
			$raw = $this->request->post['gallery_image'] ?? [];
			if (is_array($raw)) {
				$idx = 0;
				foreach ($raw as $row) {
					$img = trim((string)($row['image'] ?? ''));
					$gallery_image[] = [
						'image'      => $img,
						'sort_order' => $idx++,
					];
				}
			}

			$marquee_text = [];
			foreach ($this->request->post['marquee_text'] ?? [] as $lang_id => $val) {
				$marquee_text[(int)$lang_id] = trim((string)$val);
			}

			$post = [
				'name'            => $name,
				'gallery_image'   => $gallery_image,
				'marquee_enabled' => (int)($this->request->post['marquee_enabled'] ?? 0),
				'marquee_text'    => $marquee_text,
				'status'          => (int)($this->request->post['status'] ?? 0),
			];

			$this->load->model('setting/module');
			$module_id = (int)($this->request->post['module_id'] ?? 0);
			if (!$module_id) {
				$json['module_id'] = $this->model_setting_module->addModule('termopab.gallery', $post);
			} else {
				$this->model_setting_module->editModule($module_id, $post);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
