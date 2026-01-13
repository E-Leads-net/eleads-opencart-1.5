<?php
class ControllerFeedEleadsYml extends Controller
{
	public function index()
	{
		if (!(int)$this->config->get('eleads_yml_status')) {
			$this->response->addHeader('HTTP/1.1 404 Not Found');
			$this->response->setOutput('Not Found');
			return;
		}

		$feed_key = (string)$this->config->get('eleads_yml_key');
		if ($feed_key !== '') {
			$key = isset($this->request->get['key']) ? (string)$this->request->get['key'] : '';
			if ($key !== $feed_key) {
				$this->response->addHeader('HTTP/1.1 403 Forbidden');
				$this->response->setOutput('Forbidden');
				return;
			}
		}

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');

		$shop_agency  = (string)$this->config->get('eleads_yml_agency');
		$shop_email   = (string)$this->config->get('eleads_yml_email');
		$shop_url	 = rtrim((string)$this->config->get('eleads_yml_url'), '/');
		$shop_platform= (string)$this->config->get('eleads_yml_platform');
		$shop_version = (string)$this->config->get('eleads_yml_version');

		$currency_id   = (string)$this->config->get('eleads_yml_currency_id');

		$pictures_limit = (int)$this->config->get('eleads_yml_pictures_limit');
		if ($pictures_limit <= 0) $pictures_limit = 10;

		$export_description = (int)$this->config->get('eleads_yml_export_description') ? true : false;
		$export_short_description = (int)$this->config->get('eleads_yml_export_short_description') ? true : false;
		$short_source = (string)$this->config->get('eleads_yml_short_source');
		if ($short_source !== 'description') $short_source = 'meta_description';

		$price_mode = (string)$this->config->get('eleads_yml_price_mode');
		if ($price_mode !== 'base_only') $price_mode = 'special_as_price';

		$this->response->addHeader('Content-Type: application/xml; charset=UTF-8');

		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');

		$xml->startElement('yml_catalog');
		$xml->writeAttribute('date', date('Y-m-d H:i'));

		$xml->startElement('shop');

		$xml->writeElement('agency', $shop_agency);
		$xml->writeElement('email', $shop_email);
		$xml->writeElement('url', $shop_url);
		$xml->endElement();

		$xml->startElement('categories');
		$this->writeCategoriesRecursive($xml, 0, $shop_url);
		$xml->endElement();

		$xml->startElement('offers');

		$filter = array(
			'sort'  => 'p.product_id',
			'order' => 'ASC',
			'start' => 0,
			'limit' => 100000
		);

		$products = $this->model_catalog_product->getProducts($filter);

		foreach ($products as $p) {
			$product_id = (int)$p['product_id'];
			$product_info = $this->model_catalog_product->getProduct($product_id);
			if (!$product_info) continue;

			$category_id = $this->getFirstProductCategoryId($product_id);

			$quantity = (int)$product_info['quantity'];
			$available = ($quantity > 0) ? 'true' : 'false';

			$stock_status = ($quantity > 0) ? 'На складе' : 'Нет в наличии';

			$base_price = (float)$product_info['price'];
			$special_price = $this->getSpecialPrice($product_id);

			$price = $base_price;
			$old_price = null;

			if ($price_mode === 'special_as_price' && $special_price !== null) {
				$price = (float)$special_price;
				$old_price = $base_price;
			}

			$xml->startElement('offer');
			$xml->writeAttribute('id', (string)$product_id);
			$xml->writeAttribute('group_id', '1');
			$xml->writeAttribute('available', $available);

			$xml->writeElement('url', $this->url->link('product/product', 'product_id=' . $product_id, ''));
			$xml->writeElement('name', $this->sanitizeText($product_info['name']));
			$xml->writeElement('price', $this->formatMoney($price));

			if ($old_price !== null) {
				$xml->writeElement('old_price', $this->formatMoney($old_price));
			} else {
				$xml->startElement('old_price'); $xml->endElement();
			}

			$xml->writeElement('currencyId', $currency_id);

			if ($category_id) {
				$xml->writeElement('categoryId', (string)$category_id);
			}

			$xml->writeElement('quantity', (string)$quantity);
			$xml->writeElement('stock_status', $stock_status);

			$imgs = $this->getProductImages($product_info, $product_id, $shop_url, $pictures_limit);
			foreach ($imgs as $u) {
				$xml->writeElement('picture', $u);
			}

			if (!empty($product_info['manufacturer'])) {
				$xml->writeElement('vendor', $this->sanitizeText($product_info['manufacturer']));
			}
			if (!empty($product_info['model'])) {
				$xml->writeElement('vendorCode', $this->sanitizeText($product_info['model']));
			}

			if ($export_description) {
				$desc = $this->plainText($product_info['description']);
				if ($desc !== '') $xml->writeElement('description', $this->sanitizeText($desc));
				else { $xml->startElement('description'); $xml->endElement(); }
			} else {
				$xml->startElement('description'); $xml->endElement();
			}

			if ($export_short_description) {
				$short = '';
				if ($short_source === 'meta_description' && !empty($product_info['meta_description'])) {
					$short = trim(html_entity_decode($product_info['meta_description'], ENT_QUOTES, 'UTF-8'));
				} else {
					$short = $this->plainText($product_info['description']);
					$short = ($short !== '') ? mb_substr($short, 0, 140, 'UTF-8') : '';
				}

				if ($short !== '') $xml->writeElement('short_description', $this->sanitizeText($short));
				else { $xml->startElement('short_description'); $xml->endElement(); }
			} else {
				$xml->startElement('short_description'); $xml->endElement();
			}

			$attributes = $this->model_catalog_product->getProductAttributes($product_id);
			foreach ($attributes as $group) {
				if (empty($group['attribute'])) continue;
				foreach ($group['attribute'] as $attr) {
					$name  = isset($attr['name']) ? trim($attr['name']) : '';
					$value = isset($attr['text']) ? trim($attr['text']) : '';
					if ($name === '' || $value === '') continue;

					$xml->startElement('param');
					$xml->writeAttribute('name', $this->sanitizeText($name));
					$xml->text($this->sanitizeText($value));
					$xml->endElement();
				}
			}

			$xml->endElement(); // offer
		}

		$xml->endElement(); // offers
		$xml->endElement(); // shop
		$xml->endElement(); // yml_catalog
		$xml->endDocument();

		$this->response->setOutput($xml->outputMemory());
	}

	private function writeCategoriesRecursive(XMLWriter $xml, $parent_id, $shop_url)
	{
		$categories = $this->model_catalog_category->getCategories($parent_id);
		foreach ($categories as $cat) {
			$id = (int)$cat['category_id'];

			$xml->startElement('category');
			$xml->writeAttribute('id', (string)$id);
			if ((int)$parent_id > 0) $xml->writeAttribute('parentId', (string)$parent_id);

			$cat_url = $this->url->link('product/category', 'path=' . $id, '');
			$xml->writeAttribute('url', $cat_url);

			$xml->text($this->sanitizeText($cat['name']));
			$xml->endElement();

			$this->writeCategoriesRecursive($xml, $id, $shop_url);
		}
	}

	private function getFirstProductCategoryId($product_id)
	{
		$q = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "product_to_category WHERE product_id=" . (int)$product_id . " ORDER BY category_id ASC LIMIT 1");
		return $q->num_rows ? (int)$q->row['category_id'] : 0;
	}

	private function getSpecialPrice($product_id)
	{
		$q = $this->db->query("
			SELECT price FROM " . DB_PREFIX . "product_special
			WHERE product_id=" . (int)$product_id . "
			  AND (date_start='0000-00-00' OR date_start <= NOW())
			  AND (date_end='0000-00-00' OR date_end >= NOW())
			ORDER BY priority ASC, price ASC
			LIMIT 1
		");
		return $q->num_rows ? (float)$q->row['price'] : null;
	}

	private function getProductImages($product_info, $product_id, $shop_url, $limit)
	{
		$images = array();

		if (!empty($product_info['image'])) {
			$images[] = $shop_url . '/image/' . ltrim($product_info['image'], '/');
		}

		$this->load->model('catalog/product');
		$additional = $this->model_catalog_product->getProductImages($product_id);
		foreach ($additional as $img) {
			if (!empty($img['image'])) {
				$images[] = $shop_url . '/image/' . ltrim($img['image'], '/');
			}
		}

		$images = array_values(array_unique($images));

		if ($limit > 0 && count($images) > $limit) {
			$images = array_slice($images, 0, $limit);
		}

		return $images;
	}

	private function boolText($v)
	{
		return ((int)$v) ? 'true' : 'false';
	}

	private function formatMoney($value)
	{
		$v = (float)$value;
		if (abs($v - round($v)) < 0.00001) return (string)round($v);
		return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
	}

	private function plainText($html)
	{
		$t = html_entity_decode((string)$html, ENT_QUOTES, 'UTF-8');
		$t = trim(strip_tags($t));
		return $t;
	}

	private function sanitizeText($text)
	{
		return trim((string)$text);
	}
}
