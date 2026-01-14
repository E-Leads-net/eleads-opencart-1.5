<?php
class ControllerFeedEleadsYml extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('feed/eleads_yml');
		$this->document->setTitle($this->language->get('heading_title_raw'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('eleads_yml', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->redirect($this->url->link('feed/eleads_yml', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$this->data['heading_title'] = $this->language->get('heading_title_raw');

		$this->data['text_enabled']  = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		$this->data['text_yes']      = $this->language->get('text_yes');
		$this->data['text_no']       = $this->language->get('text_no');
		$this->data['text_home']     = $this->language->get('text_home');

		$this->data['text_select_all']   = $this->language->get('text_select_all');
		$this->data['text_unselect_all'] = $this->language->get('text_unselect_all');

		$this->data['entry_status']      = $this->language->get('entry_status');
		$this->data['entry_categories']  = $this->language->get('entry_categories');
		$this->data['help_categories']   = $this->language->get('help_categories');
		$this->data['entry_key']         = $this->language->get('entry_key');
		$this->data['entry_shop_name']   = $this->language->get('entry_shop_name');
		$this->data['entry_email']       = $this->language->get('entry_email');
		$this->data['entry_url']         = $this->language->get('entry_url');

		$this->data['entry_pictures_limit'] = $this->language->get('entry_pictures_limit');
		$this->data['entry_short_source'] = $this->language->get('entry_short_source');

		$this->data['help_key']      = $this->language->get('help_key');
		$this->data['help_feed_url'] = $this->language->get('help_feed_url');

		// feed url (front, without /admin/)
		$key = (string)$this->config->get('eleads_yml_key');
		$base_url = (defined('HTTPS_CATALOG') && HTTPS_CATALOG) ? HTTPS_CATALOG : HTTP_CATALOG;

		// --------------------------------------------------
		// Feed URLs for all languages
		// --------------------------------------------------
		$this->load->model('localisation/language');
		$languages = $this->model_localisation_language->getLanguages();
		$key = (string)$this->config->get('eleads_yml_key');

		$base_url = (defined('HTTPS_CATALOG') && HTTPS_CATALOG) ? HTTPS_CATALOG : HTTP_CATALOG;

		$this->data['feed_urls'] = array();

		foreach ($languages as $lang) {
			if (!$lang['status']) continue;

			$lang_id = (int)$lang['language_id'];

			$url = $base_url . 'index.php?route=feed/eleads_yml&language_id=' . $lang_id;

			if ($key !== '') {
				$url .= '&key=' . urlencode($key);
			}

			$this->data['feed_urls'][] = array(
				'name' => $lang['name'],
				'code' => $lang['code'],
				'language_id' => $lang_id,
				'url'  => $url
			);
		}

		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->data['text_home'],
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_feed'),
			'href'      => $this->url->link('extension/feed', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title_raw'),
			'href'      => $this->url->link('feed/eleads_yml', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['action'] = $this->url->link('feed/eleads_yml', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['cancel'] = $this->url->link('extension/feed', 'token=' . $this->session->data['token'], 'SSL');

		// -----------------------------------------
		// Categories checkbox tree (like Yandex Market module)
		// -----------------------------------------
		$this->load->model('catalog/category');

		$selected = $this->config->get('eleads_yml_categories');
		if (!is_array($selected)) $selected = array();

		if (isset($this->request->post['eleads_yml_categories']) && is_array($this->request->post['eleads_yml_categories'])) {
			$selected = $this->request->post['eleads_yml_categories'];
		}

		$this->data['eleads_yml_categories'] = $selected;
		$this->data['categories'] = $this->getCategoriesCheckboxTree();

		// -----------------------------------------
		// Settings fields
		// -----------------------------------------
		$fields = array(
			'eleads_yml_status',
			'eleads_yml_key',
			'eleads_yml_shop_name',
			'eleads_yml_email',
			'eleads_yml_url',
			'eleads_yml_currency_id',
			'eleads_yml_pictures_limit',
			'eleads_yml_short_source',
		);

		foreach ($fields as $f) {
			if (isset($this->request->post[$f])) {
				$this->data[$f] = $this->request->post[$f];
			} else {
				$this->data[$f] = $this->config->get($f);
			}
		}

		// defaults for empty fields
		if ($this->data['eleads_yml_shop_name'] === null || $this->data['eleads_yml_shop_name'] === '') {
			$this->data['eleads_yml_shop_name'] = $this->config->get('config_name');
		}
		if ($this->data['eleads_yml_email'] === null || $this->data['eleads_yml_email'] === '') {
			$this->data['eleads_yml_email'] = $this->config->get('config_email');
		}
		if ($this->data['eleads_yml_url'] === null || $this->data['eleads_yml_url'] === '') {
			$this->data['eleads_yml_url'] = $base_url;
		}
		if ($this->data['eleads_yml_currency_id'] === null || $this->data['eleads_yml_currency_id'] === '') {
			$this->data['eleads_yml_currency_id'] = $this->config->get('config_currency') ? $this->config->get('config_currency') : 'UAH';
		}
		if ($this->data['eleads_yml_pictures_limit'] === null || (int)$this->data['eleads_yml_pictures_limit'] <= 0) {
			$this->data['eleads_yml_pictures_limit'] = 10;
		}
		if ($this->data['eleads_yml_short_source'] === null || $this->data['eleads_yml_short_source'] === '') {
			$this->data['eleads_yml_short_source'] = 'meta_description';
		}

		$this->template = 'feed/eleads_yml.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render());
	}

	public function install() {
		$this->load->model('setting/setting');

		$defaults = array(
			'eleads_yml_status' => 1,
			'eleads_yml_key' => '',
			'eleads_yml_categories' => array(),

			'eleads_yml_shop_name' => $this->config->get('config_name'),
			'eleads_yml_email' => $this->config->get('config_email'),
			'eleads_yml_url' => rtrim((defined('HTTPS_CATALOG') && HTTPS_CATALOG) ? HTTPS_CATALOG : HTTP_CATALOG, '/'),
			'eleads_yml_currency_id' => $this->config->get('config_currency') ? $this->config->get('config_currency') : 'UAH',
			'eleads_yml_pictures_limit' => 10,
			'eleads_yml_short_source' => 'meta_description'
		);

		$this->model_setting_setting->editSetting('eleads_yml', $defaults);
	}

	public function uninstall() {
		$this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('eleads_yml');
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'feed/eleads_yml')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}

	// -------------------------------------------------------
	// Categories tree for checkbox list (name like: A > B > C)
	// -------------------------------------------------------
	private function getCategoriesCheckboxTree() {
		$categories = array();

		$q = $this->db->query("
			SELECT c.category_id, cd.name, c.parent_id, c.sort_order
			FROM " . DB_PREFIX . "category c
			LEFT JOIN " . DB_PREFIX . "category_description cd
				ON (c.category_id = cd.category_id AND cd.language_id = " . (int)$this->config->get('config_language_id') . ")
			ORDER BY c.parent_id ASC, c.sort_order ASC, cd.name ASC
		");

		$by_parent = array();
		foreach ($q->rows as $row) {
			$pid = (int)$row['parent_id'];
			if (!isset($by_parent[$pid])) $by_parent[$pid] = array();
			$by_parent[$pid][] = $row;
		}

		$this->buildCategoriesCheckboxTree($categories, $by_parent, 0, '');

		return $categories;
	}

	private function buildCategoriesCheckboxTree(&$out, &$by_parent, $parent_id, $path) {
		if (!isset($by_parent[$parent_id])) return;

		foreach ($by_parent[$parent_id] as $row) {
			$id = (int)$row['category_id'];
			$name = trim((string)$row['name']);
			if ($name === '') $name = 'Category #' . $id;

			$full = ($path !== '') ? ($path . ' > ' . $name) : $name;

			$out[] = array(
				'category_id' => $id,
				'name'        => $full
			);

			$this->buildCategoriesCheckboxTree($out, $by_parent, $id, $full);
		}
	}
}
