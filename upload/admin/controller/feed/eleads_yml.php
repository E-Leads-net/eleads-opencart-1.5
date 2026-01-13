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
		$this->data['text_yes']	  = $this->language->get('text_yes');
		$this->data['text_no']	   = $this->language->get('text_no');
		$this->data['text_home']	 = $this->language->get('text_home');

		$this->data['entry_status']  = $this->language->get('entry_status');
		$this->data['entry_key']	 = $this->language->get('entry_key');
		$this->data['entry_agency']  = $this->language->get('entry_agency');
		$this->data['entry_email']   = $this->language->get('entry_email');
		$this->data['entry_url']	 = $this->language->get('entry_url');

		$this->data['entry_pictures_limit'] = $this->language->get('entry_pictures_limit');
		$this->data['entry_export_description'] = $this->language->get('entry_export_description');
		$this->data['entry_export_short_description'] = $this->language->get('entry_export_short_description');
		$this->data['entry_short_source'] = $this->language->get('entry_short_source');

		$this->data['entry_price_mode'] = $this->language->get('entry_price_mode');
		$this->data['text_price_mode_base'] = $this->language->get('text_price_mode_base');
		$this->data['text_price_mode_special'] = $this->language->get('text_price_mode_special');

		$this->data['help_key'] = $this->language->get('help_key');
		$this->data['help_feed_url'] = $this->language->get('help_feed_url');

		$key = (string)$this->config->get('eleads_yml_key');
		$base_url = HTTPS_CATALOG;

		if ($key !== '') {
			$this->data['feed_url'] = $base_url . 'index.php?route=feed/eleads_yml&key=' . urlencode($key);
		} else {
			$this->data['feed_url'] = $base_url . 'index.php?route=feed/eleads_yml';
		}


		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}

		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'	  => $this->data['text_home'],
			'href'	  => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text'	  => $this->language->get('text_feed'),
			'href'	  => $this->url->link('extension/feed', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['breadcrumbs'][] = array(
			'text'	  => $this->language->get('heading_title_raw'),
			'href'	  => $this->url->link('feed/eleads_yml', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['action'] = $this->url->link('feed/eleads_yml', 'token=' . $this->session->data['token'], 'SSL');
		$this->data['cancel'] = $this->url->link('extension/feed', 'token=' . $this->session->data['token'], 'SSL');

		$fields = array(
			'eleads_yml_status',
			'eleads_yml_key',
			'eleads_yml_agency',
			'eleads_yml_email',
			'eleads_yml_url',
			'eleads_yml_pictures_limit',
			'eleads_yml_export_description',
			'eleads_yml_export_short_description',
			'eleads_yml_short_source',
			'eleads_yml_price_mode'
		);

		foreach ($fields as $f) {
			if (isset($this->request->post[$f])) {
				$this->data[$f] = $this->request->post[$f];
			} else {
				$this->data[$f] = $this->config->get($f);
			}
		}

		if ($this->data['eleads_yml_agency'] === null || $this->data['eleads_yml_agency'] === '') {
			$this->data['eleads_yml_agency'] = $this->config->get('config_name');
		}
		if ($this->data['eleads_yml_email'] === null || $this->data['eleads_yml_email'] === '') {
			$this->data['eleads_yml_email'] = $this->config->get('config_email');
		}
		if ($this->data['eleads_yml_url'] === null || $this->data['eleads_yml_url'] === '') {
			$this->data['eleads_yml_url'] = HTTPS_CATALOG;
		}
		if ($this->data['eleads_yml_currency_id'] === null || $this->data['eleads_yml_currency_id'] === '') {
			$this->data['eleads_yml_currency_id'] = $this->config->get('config_currency') ?: 'UAH';
		}
		if ($this->data['eleads_yml_pictures_limit'] === null || (int)$this->data['eleads_yml_pictures_limit'] <= 0) {
			$this->data['eleads_yml_pictures_limit'] = 10;
		}
		if ($this->data['eleads_yml_short_source'] === null || $this->data['eleads_yml_short_source'] === '') {
			$this->data['eleads_yml_short_source'] = 'meta_description';
		}
		if ($this->data['eleads_yml_price_mode'] === null || $this->data['eleads_yml_price_mode'] === '') {
			$this->data['eleads_yml_price_mode'] = 'special_as_price';
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
			'eleads_yml_agency' => 'E-Leads',
			'eleads_yml_email' => $this->config->get('config_email'),
			'eleads_yml_url' => rtrim($this->config->get('config_url'), '/'),
			'eleads_yml_currency_id' => $this->config->get('config_currency') ?: 'UAH',
			'eleads_yml_pictures_limit' => 10,
			'eleads_yml_export_description' => 1,
			'eleads_yml_export_short_description' => 1,
			'eleads_yml_short_source' => 'meta_description',
			'eleads_yml_price_mode' => 'special_as_price'
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
}
