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

		if (isset($this->request->get['language_id'])) {
			$this->config->set('config_language_id', $this->request->get['language_id']);
		}

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');

		$shop_name  = (string)$this->config->get('eleads_yml_shop_name');
		$shop_email = (string)$this->config->get('eleads_yml_email');
		$shop_url   = rtrim((string)$this->config->get('eleads_yml_url'), '/');

		$currency_id = (string)$this->config->get('eleads_yml_currency_id');

		$pictures_limit = (int)$this->config->get('eleads_yml_pictures_limit');
		if ($pictures_limit <= 0) $pictures_limit = 10;

		$short_source = (string)$this->config->get('eleads_yml_short_source');
		if ($short_source !== 'description') $short_source = 'meta_description';

		$price_mode = (string)$this->config->get('eleads_yml_price_mode');
		if ($price_mode !== 'base_only') $price_mode = 'special_as_price';

		// -----------------------------------------
		// Categories filter (from admin settings)
		// -----------------------------------------
		$selected_categories = $this->config->get('eleads_yml_categories');
		if (!is_array($selected_categories)) $selected_categories = array();

		$allowed_category_ids = array();
		if (!empty($selected_categories)) {
			// include selected + all their children
			$allowed_category_ids = $this->getCategoriesWithChildrenIds($selected_categories);
		}

		$this->response->addHeader('Content-Type: application/xml; charset=UTF-8');

		$xml = new XMLWriter();
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');

		$xml->startElement('yml_catalog');
		$xml->writeAttribute('date', date('Y-m-d H:i'));

		$xml->startElement('shop');

		$this->writeElementAlways($xml, 'shopName', $shop_name);
		$this->writeElementAlways($xml, 'email', $shop_email);
		$this->writeElementAlways($xml, 'url', $shop_url);

		$xml->startElement('categories');
		if (!empty($allowed_category_ids)) {
			$this->writeSelectedCategoriesTree($xml, $selected_categories, $allowed_category_ids);
		} else {
			$this->writeCategoriesRecursive($xml, 0);
		}
		$xml->endElement(); // categories

		$xml->startElement('offers');

		// -----------------------------------------
		// Products query:
		// - if categories selected -> take only products from these categories
		// - else -> default getProducts()
		// -----------------------------------------
		if (!empty($allowed_category_ids)) {
			$products = $this->getProductsByCategoryIds($allowed_category_ids);
		} else {
			$filter = array(
				'sort'  => 'p.product_id',
				'order' => 'ASC',
				'start' => 0,
				'limit' => 100000
			);

			$products = $this->model_catalog_product->getProducts($filter);
		}

		foreach ($products as $p) {
			$product_id = (int)$p['product_id'];

			$product_info = $this->model_catalog_product->getProduct($product_id);
			if (!$product_info) continue;

			$category_id = $this->getFirstProductCategoryId($product_id);

			$base_price = (float)$product_info['price'];
			$special_price = $this->getSpecialPrice($product_id); // float|null

			$common = array(
				'product_id' => $product_id,
				'product_info' => $product_info,
				'category_id' => $category_id,
				'currency_id' => $currency_id,
				'images' => $this->getProductImages($product_info, $product_id, $shop_url, $pictures_limit),
				'short_source' => $short_source,
				'price_mode' => $price_mode,
				'base_price' => $base_price,
				'special_price' => $special_price,
			);

			$attributes_params = $this->getAttributesParams($product_id);

			$options = $this->model_catalog_product->getProductOptions($product_id);
			$variant_option = $this->getVariantOption($options);

			if ($variant_option) {
				$opt_name = trim((string)$variant_option['name']);

				foreach ($variant_option['option_value'] as $ov) {
					$product_option_value_id = isset($ov['product_option_value_id']) ? (int)$ov['product_option_value_id'] : 0;
					$option_value_id = isset($ov['option_value_id']) ? (int)$ov['option_value_id'] : 0;

					$val_name = isset($ov['name']) ? trim((string)$ov['name']) : '';
					if ($val_name === '') continue;

					$variant_id = $product_id . '-' . ($product_option_value_id ? $product_option_value_id : $option_value_id);

					$qty = isset($ov['quantity']) ? (int)$ov['quantity'] : (int)$product_info['quantity'];

					$adj = $this->getOptionPriceAdjustment($ov);

					$base_with_opt = $base_price + $adj;
					$special_with_opt = ($special_price !== null) ? ((float)$special_price + $adj) : null;

					$price = $base_with_opt;
					$old_price = '';

					if ($price_mode === 'special_as_price' && $special_with_opt !== null) {
						$price = $special_with_opt;
						$old_price = $this->formatMoney($base_with_opt);
					}

					$available = ($qty > 0) ? 'true' : 'false';
					$stock_status = ($qty > 0) ? 'На складе' : 'Нет в наличии';

					$name = $this->sanitizeText($product_info['name'] . ' (' . $opt_name . ': ' . $val_name . ')');

					$xml->startElement('offer');
					$xml->writeAttribute('id', (string)$variant_id);
					$xml->writeAttribute('group_id', (string)$product_id);
					$xml->writeAttribute('available', $available);

					$this->writeElementAlways($xml, 'url', $this->url->link('product/product', 'product_id=' . $product_id, ''));
					$this->writeElementAlways($xml, 'name', $name);
					$this->writeElementAlways($xml, 'price', $this->formatMoney($price));
					$this->writeElementAlways($xml, 'old_price', $old_price);
					$this->writeElementAlways($xml, 'currencyId', $currency_id);
					$this->writeElementAlways($xml, 'categoryId', $category_id ? (string)$category_id : '');
					$this->writeElementAlways($xml, 'quantity', (string)$qty);
					$this->writeElementAlways($xml, 'stock_status', $stock_status);

					if (!empty($common['images'])) {
						foreach ($common['images'] as $u) {
							$this->writeElementAlways($xml, 'picture', $u);
						}
					} else {
						$this->writeElementAlways($xml, 'picture', '');
					}

					$this->writeElementAlways($xml, 'vendor', !empty($product_info['manufacturer']) ? $this->sanitizeText($product_info['manufacturer']) : '');

					$vendor_code = !empty($product_info['model']) ? $this->sanitizeText($product_info['model']) : '';
					if ($vendor_code !== '') $vendor_code .= '-' . ($product_option_value_id ? $product_option_value_id : $option_value_id);
					$this->writeElementAlways($xml, 'vendorCode', $vendor_code);

					$desc = $this->plainText($product_info['description']);
					$this->writeElementAlways($xml, 'description', $this->sanitizeText($desc));

					$short = '';
					if ($short_source === 'meta_description' && !empty($product_info['meta_description'])) {
						$short = trim(html_entity_decode($product_info['meta_description'], ENT_QUOTES, 'UTF-8'));
					} else {
						$short = $desc;
						$short = ($short !== '') ? mb_substr($short, 0, 140, 'UTF-8') : '';
					}
					$this->writeElementAlways($xml, 'short_description', ($short !== '') ? $this->sanitizeText($short) : '');

					$this->writeParams($xml, $attributes_params);

					$xml->startElement('param');
					$xml->writeAttribute('name', $this->sanitizeText($opt_name));
					$xml->text($this->sanitizeText($val_name));
					$xml->endElement();

					$xml->endElement(); // offer
				}
			} else {
				$qty = (int)$product_info['quantity'];
				$available = ($qty > 0) ? 'true' : 'false';
				$stock_status = ($qty > 0) ? 'На складе' : 'Нет в наличии';

				$price = $base_price;
				$old_price = '';

				if ($price_mode === 'special_as_price' && $special_price !== null) {
					$price = (float)$special_price;
					$old_price = $this->formatMoney($base_price);
				}

				$xml->startElement('offer');
				$xml->writeAttribute('id', (string)$product_id);
				$xml->writeAttribute('available', $available);

				$this->writeElementAlways($xml, 'url', $this->url->link('product/product', 'product_id=' . $product_id, ''));
				$this->writeElementAlways($xml, 'name', $this->sanitizeText($product_info['name']));
				$this->writeElementAlways($xml, 'price', $this->formatMoney($price));
				$this->writeElementAlways($xml, 'old_price', $old_price);
				$this->writeElementAlways($xml, 'currencyId', $currency_id);
				$this->writeElementAlways($xml, 'categoryId', $category_id ? (string)$category_id : '');
				$this->writeElementAlways($xml, 'quantity', (string)$qty);
				$this->writeElementAlways($xml, 'stock_status', $stock_status);

				if (!empty($common['images'])) {
					foreach ($common['images'] as $u) {
						$this->writeElementAlways($xml, 'picture', $u);
					}
				} else {
					$this->writeElementAlways($xml, 'picture', '');
				}

				$this->writeElementAlways($xml, 'vendor', !empty($product_info['manufacturer']) ? $this->sanitizeText($product_info['manufacturer']) : '');
				$this->writeElementAlways($xml, 'vendorCode', !empty($product_info['model']) ? $this->sanitizeText($product_info['model']) : '');

				$desc = $this->plainText($product_info['description']);
				$this->writeElementAlways($xml, 'description', $this->sanitizeText($desc));

				$short = '';
				if ($short_source === 'meta_description' && !empty($product_info['meta_description'])) {
					$short = trim(html_entity_decode($product_info['meta_description'], ENT_QUOTES, 'UTF-8'));
				} else {
					$short = $desc;
					$short = ($short !== '') ? mb_substr($short, 0, 140, 'UTF-8') : '';
				}
				$this->writeElementAlways($xml, 'short_description', ($short !== '') ? $this->sanitizeText($short) : '');

				$this->writeParams($xml, $attributes_params);

				$xml->endElement(); // offer
			}
		}

		$xml->endElement(); // offers
		$xml->endElement(); // shop
		$xml->endElement(); // yml_catalog
		$xml->endDocument();

		$this->response->setOutput($xml->outputMemory());
	}

	private function writeCategoriesRecursive(XMLWriter $xml, $parent_id)
	{
		$categories = $this->model_catalog_category->getCategories($parent_id);

		foreach ($categories as $cat) {
			$id = (int)$cat['category_id'];

			$xml->startElement('category');
			$xml->writeAttribute('id', (string)$id);

			if ((int)$parent_id > 0) {
				$xml->writeAttribute('parentId', (string)$parent_id);
			}

			$cat_url = $this->url->link('product/category', 'path=' . $id, '');
			$xml->writeAttribute('url', $cat_url);

			$xml->text($this->sanitizeText($cat['name']));
			$xml->endElement();

			$this->writeCategoriesRecursive($xml, $id);
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

	private function getVariantOption($options)
	{
		if (empty($options) || !is_array($options)) return null;

		foreach ($options as $opt) {
			if (!empty($opt['option_value']) && is_array($opt['option_value'])) {
				return $opt;
			}
		}

		return null;
	}

	private function getOptionPriceAdjustment($ov)
	{
		$price = isset($ov['price']) ? (float)$ov['price'] : 0.0;
		$prefix = isset($ov['price_prefix']) ? (string)$ov['price_prefix'] : '+';

		if ($price == 0.0) return 0.0;

		return ($prefix === '-') ? (0.0 - $price) : $price;
	}

	private function getAttributesParams($product_id)
	{
		$params = array();

		$attributes = $this->model_catalog_product->getProductAttributes($product_id);
		foreach ($attributes as $group) {
			if (empty($group['attribute'])) continue;

			foreach ($group['attribute'] as $attr) {
				$name  = isset($attr['name']) ? trim($attr['name']) : '';
				$value = isset($attr['text']) ? trim($attr['text']) : '';
				if ($name === '' || $value === '') continue;

				$params[] = array(
					'name' => $name,
					'value' => $value,
				);
			}
		}

		return $params;
	}

	private function writeParams(XMLWriter $xml, $params)
	{
		if (empty($params)) return;

		foreach ($params as $p) {
			$xml->startElement('param');
			$xml->writeAttribute('name', $this->sanitizeText($p['name']));
			$xml->text($this->sanitizeText($p['value']));
			$xml->endElement();
		}
	}

	private function writeElementAlways(XMLWriter $xml, $name, $value = '')
	{
		$xml->startElement($name);
		if ($value !== '' && $value !== null) {
			$xml->text($value);
		}
		$xml->endElement();
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

	// -----------------------------
	// Category filter helpers
	// -----------------------------

	private function getProductsByCategoryIds($category_ids)
	{
		$category_ids = array_values(array_unique(array_map('intval', (array)$category_ids)));
		$category_ids = array_filter($category_ids, function($v){ return $v > 0; });

		if (empty($category_ids)) {
			return array();
		}

		$sql = "
			SELECT DISTINCT p.product_id
			FROM " . DB_PREFIX . "product p
			INNER JOIN " . DB_PREFIX . "product_to_category p2c ON (p.product_id = p2c.product_id)
			WHERE p.status = '1'
			  AND p2c.category_id IN (" . implode(',', $category_ids) . ")
			ORDER BY p.product_id ASC
			LIMIT 100000
		";

		$q = $this->db->query($sql);

		$products = array();
		foreach ($q->rows as $row) {
			$products[] = array('product_id' => (int)$row['product_id']);
		}

		return $products;
	}

	private function getCategoriesWithChildrenIds($root_ids)
	{
		$all = array();

		foreach ((array)$root_ids as $id) {
			$id = (int)$id;
			if ($id <= 0) continue;

			$all[] = $id;
			$this->collectChildCategoryIds($id, $all);
		}

		return array_values(array_unique($all));
	}

	private function collectChildCategoryIds($parent_id, &$result)
	{
		$q = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "category WHERE parent_id=" . (int)$parent_id);
		if (!$q->num_rows) return;

		foreach ($q->rows as $row) {
			$cid = (int)$row['category_id'];

			if (!in_array($cid, $result)) {
				$result[] = $cid;
				$this->collectChildCategoryIds($cid, $result);
			}
		}
	}

	private function writeSelectedCategoriesTree(XMLWriter $xml, $root_ids, $allowed_ids)
	{
		$root_ids = array_values(array_unique(array_map('intval', (array)$root_ids)));

		foreach ($root_ids as $root_id) {
			if ($root_id <= 0) continue;
			if (!in_array($root_id, $allowed_ids)) continue;

			$this->writeCategoryNode($xml, $root_id, 0, $allowed_ids);
		}
	}

	private function writeCategoryNode(XMLWriter $xml, $category_id, $parent_id_for_feed, $allowed_ids)
	{
		$cat = $this->getCategoryRow($category_id);
		if (!$cat) return;

		$xml->startElement('category');
		$xml->writeAttribute('id', (string)$category_id);

		if ((int)$parent_id_for_feed > 0) {
			$xml->writeAttribute('parentId', (string)$parent_id_for_feed);
		}

		$cat_url = $this->url->link('product/category', 'path=' . (int)$category_id, '');
		$xml->writeAttribute('url', $cat_url);

		$xml->text($this->sanitizeText($cat['name']));
		$xml->endElement();

		$children = $this->getChildCategoryIds($category_id);
		foreach ($children as $child_id) {
			if (!in_array((int)$child_id, $allowed_ids)) continue;
			$this->writeCategoryNode($xml, (int)$child_id, (int)$category_id, $allowed_ids);
		}
	}

	private function getCategoryRow($category_id)
	{
		$lang_id = (int)$this->config->get('config_language_id');

		$q = $this->db->query("
			SELECT c.category_id, cd.name
			FROM " . DB_PREFIX . "category c
			LEFT JOIN " . DB_PREFIX . "category_description cd
				ON (c.category_id = cd.category_id AND cd.language_id = " . $lang_id . ")
			WHERE c.category_id = " . (int)$category_id . "
			LIMIT 1
		");

		return $q->num_rows ? $q->row : null;
	}

	private function getChildCategoryIds($parent_id)
	{
		$q = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "category WHERE parent_id=" . (int)$parent_id . " ORDER BY sort_order ASC, category_id ASC");
		$ids = array();

		foreach ($q->rows as $r) {
			$ids[] = (int)$r['category_id'];
		}

		return $ids;
	}

}
