<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Module;

/**
 * Cat Promo Thin – title, description, button (text + link), two images (main + hover).
 */
class CatPromoThin extends \Opencart\System\Engine\Controller {

	public function index(): void {
		$this->template->addPath('extension/termopab', DIR_EXTENSION . 'termopab/admin/view/template/');

		$this->load->language('extension/termopab/module/cat_promo_thin');

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
			'href' => $this->url->link('extension/termopab/module/cat_promo_thin', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''))
		];

		$data['save'] = $this->url->link('extension/termopab/module/cat_promo_thin.save', 'user_token=' . $this->session->data['user_token'] . ($module_id ? '&module_id=' . $module_id : ''));
		$data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

		$module_info = [];
		if ($module_id) {
			$this->load->model('setting/module');
			$module_info = $this->model_setting_module->getModule($module_id);
		}

		$data['name'] = $module_info['name'] ?? '';
		$data['status'] = $module_info['status'] ?? 1;
		$data['module_id'] = $module_id;
		$data['title'] = $module_info['title'] ?? [];
		$data['description'] = $module_info['description'] ?? [];
		$data['link_url'] = $module_info['link_url'] ?? [];
		$data['link_text'] = $module_info['link_text'] ?? [];
		$data['image'] = $module_info['image'] ?? '';
		$data['image_hover'] = $module_info['image_hover'] ?? '';

		$this->load->model('localisation/language');
		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('tool/image');
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		$data['thumb'] = $data['placeholder'];
		$data['thumb_hover'] = $data['placeholder'];
		if ($data['image'] && is_file(DIR_IMAGE . html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8'))) {
			$data['thumb'] = $this->model_tool_image->resize($data['image'], 200, 120);
		}
		if ($data['image_hover'] && is_file(DIR_IMAGE . html_entity_decode($data['image_hover'], ENT_QUOTES, 'UTF-8'))) {
			$data['thumb_hover'] = $this->model_tool_image->resize($data['image_hover'], 200, 120);
		}

		$this->document->addScript('view/javascript/ckeditor/ckeditor.js');
		$this->document->addScript('view/javascript/ckeditor/adapters/jquery.js');
		$this->load->language('default');
		$data['ckeditor'] = $this->language->get('ckeditor') ?: 'en';

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/termopab/module/cat_promo_thin', $data));
	}

	public function save(): void {
		$this->load->language('extension/termopab/module/cat_promo_thin');

		$json = [];

		if (!$this->user->hasPermission('modify', 'extension/termopab/module/cat_promo_thin')) {
			$json['error']['warning'] = $this->language->get('error_permission');
		}

		$name = $this->request->post['name'] ?? '';
		if (oc_strlen($name) < 3 || oc_strlen($name) > 64) {
			$json['error']['name'] = $this->language->get('error_name');
		}

		if (!$json) {
			$title = [];
			foreach ($this->request->post['title'] ?? [] as $lang_id => $val) {
				$title[(int)$lang_id] = trim((string)$val);
			}
			$description = [];
			foreach ($this->request->post['description'] ?? [] as $lang_id => $val) {
				$description[(int)$lang_id] = (string)$val;
			}
			$link_url = [];
			foreach ($this->request->post['link_url'] ?? [] as $lang_id => $val) {
				$link_url[(int)$lang_id] = trim((string)$val);
			}
			$link_text = [];
			foreach ($this->request->post['link_text'] ?? [] as $lang_id => $val) {
				$link_text[(int)$lang_id] = trim((string)$val);
			}

			$post = [
				'name'        => $name,
				'title'       => $title,
				'description' => $description,
				'link_url'    => $link_url,
				'link_text'   => $link_text,
				'image'       => trim($this->request->post['image'] ?? ''),
				'image_hover' => trim($this->request->post['image_hover'] ?? ''),
				'status'      => (int)($this->request->post['status'] ?? 0),
			];

			$this->load->model('setting/module');
			$module_id = (int)($this->request->post['module_id'] ?? 0);
			if (!$module_id) {
				$json['module_id'] = $this->model_setting_module->addModule('termopab.cat_promo_thin', $post);
			} else {
				$this->model_setting_module->editModule($module_id, $post);
			}

			$json['success'] = $this->language->get('text_success');
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
