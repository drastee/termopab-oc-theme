<?php
namespace Opencart\Admin\Controller\Extension\Termopab\Event;

/**
 * Event controller: adds Termopab menu (theme + projects) to admin sidebar root, after Catalog.
 * Hook: admin/view/common/column_left/before (admin startup strips "admin/" when registering).
 * @see https://stackoverflow.com/questions/73530617/create-an-admin-menu-in-opencart-4
 */
class Menu extends \Opencart\System\Engine\Controller {
	/**
	 * View event passes (&$route, &$data, &$code, &$output).
	 * Action format: extension/termopab/event/menu.onColumnLeft (dot separates method).
	 */
	public function onColumnLeft(string &$route, array &$data, string &$code = '', string &$output = ''): void {
		$menus = &$data['menus'] ?? null;
		if (!is_array($menus)) {
			return;
		}

		$new_items = $this->buildMenuItems();
		if (empty($new_items)) {
			return;
		}

		// Вставити в корінь після Каталогу (menu-catalog)
		$insert_after = 0;
		$catalog_ids  = ['menu-catalog', 'catalog'];
		foreach ($menus as $i => $menu) {
			$menu_id = $menu['id'] ?? $menu['menu_id'] ?? '';
			if (in_array($menu_id, $catalog_ids, true)) {
				$insert_after = $i + 1;
				break;
			}
		}
		array_splice($menus, $insert_after, 0, $new_items);
	}

	/** Fallback when action uses pipe (route|method) — OC Action may call index() */
	public function index(string &$route = '', array &$data = [], string &$code = '', string &$output = ''): void {
		$this->onColumnLeft($route, $data, $code, $output);
	}

	private function buildMenuItems(): array {
		$items = [];
		if (!$this->registry->has('user')) {
			return $items;
		}

		$termopab_dir = dirname(__DIR__, 2);

		$theme_file = $termopab_dir . '/controller/theme/termopab.php';
		if (is_file($theme_file)) {
			$this->load->language('extension/termopab/theme/termopab', 'termopab');
			$items[] = [
				'id'       => 'menu-termopab-theme',
				'icon'     => 'fa-solid fa-palette',
				'name'     => $this->language->get('termopab_heading_title'),
				'href'     => $this->url->link('extension/termopab/theme/termopab', 'user_token=' . $this->session->data['user_token'] . '&store_id=0'),
				'children' => [],
			];
		}

		$project_file = $termopab_dir . '/controller/project.php';
		if (is_file($project_file)) {
			$this->template->addPath('extension/termopab', $termopab_dir . '/view/template/');
			$this->language->addPath('extension/termopab', $termopab_dir . '/language/');
			$this->load->language('extension/termopab/project/list');

			$items[] = [
				'id'       => 'menu-termopab-projects',
				'icon'     => 'fa-solid fa-folder-open',
				'name'     => $this->language->get('heading_title'),
				'href'     => '',
				'children' => [
					[
						'id'       => 'menu-termopab-projects-list',
						'icon'     => '',
						'name'     => $this->language->get('text_menu_catalog') ?: $this->language->get('text_list'),
						'href'     => $this->url->link('extension/termopab/project', 'user_token=' . $this->session->data['user_token']),
						'children' => [],
					],
					[
						'id'       => 'menu-termopab-projects-add',
						'icon'     => '',
						'name'     => $this->language->get('button_add'),
						'href'     => $this->url->link('extension/termopab/project.form', 'user_token=' . $this->session->data['user_token']),
						'children' => [],
					],
				],
			];
		}

		$brewery_review_file = $termopab_dir . '/controller/brewery_review.php';
		if (is_file($brewery_review_file)) {
			if (!isset($this->template) || !$this->template->getPath('extension/termopab')) {
				$this->template->addPath('extension/termopab', $termopab_dir . '/view/template/');
			}
			if (method_exists($this->language, 'addPath')) {
				$this->language->addPath('extension/termopab', $termopab_dir . '/language/');
			}
			$this->load->language('extension/termopab/brewery_review/list');

			$items[] = [
				'id'       => 'menu-termopab-brewery-reviews',
				'icon'     => 'fa-solid fa-beer-mug-empty',
				'name'     => $this->language->get('heading_title'),
				'href'     => '',
				'children' => [
					[
						'id'       => 'menu-termopab-brewery-reviews-list',
						'icon'     => '',
						'name'     => $this->language->get('text_menu_catalog') ?: $this->language->get('text_list'),
						'href'     => $this->url->link('extension/termopab/brewery_review', 'user_token=' . $this->session->data['user_token']),
						'children' => [],
					],
					[
						'id'       => 'menu-termopab-brewery-reviews-add',
						'icon'     => '',
						'name'     => $this->language->get('button_add'),
						'href'     => $this->url->link('extension/termopab/brewery_review.form', 'user_token=' . $this->session->data['user_token']),
						'children' => [],
					],
				],
			];
		}

		return $items;
	}
}
