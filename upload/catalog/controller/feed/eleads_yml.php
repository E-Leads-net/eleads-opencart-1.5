<?php
class ControllerFeedEleadsYml extends Controller
{
	public function index()
	{
		// -----------------------------
		// Status / Key
		// -----------------------------
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

		// -----------------------------
		// Language (by language_id)
		// Example: ?route=feed/eleads_yml&language_id=3
		// -----------------------------
		if (isset($this->request->get['language_id'])) {
			$this->config->set('config_language_id', (int)$this->request->get['language_id']);
		}

		$language_code = 'uk';
		if (isset($this->request->get['language_code'])) {
			$language_code = $this->request->get['language_code'];
		}

		$this->load->model('catalog/category');
		$this->load->model('catalog/product');

		// -----------------------------
		// Settings
		// -----------------------------
		$shop_name  = (string)$this->config->get('eleads_yml_shop_name');
		$shop_email = (string)$this->config->get('eleads_yml_email');
		$shop_url   = rtrim((string)$this->config->get('eleads_yml_url'), '/');

		$currency = (string)$this->config->get('eleads_yml_currency');

		$pictures_limit = (int)$this->config->get('eleads_yml_pictures_limit');
		if ($pictures_limit <= 0) $pictures_limit = 10;

		$short_source = (string)$this->config->get('eleads_yml_short_source');
		if ($short_source !== 'description') $short_source = 'meta_description';

		$price_mode = (string)$this->config->get('eleads_yml_price_mode');
		if ($price_mode !== 'base_only') $price_mode = 'special_as_price';

		// -----------------------------
		// Category filter (selected + children)
		// -----------------------------
		$selected_categories = $this->config->get('eleads_yml_categories');
		if (!is_array($selected_categories)) $selected_categories = array();

		$allowed_category_ids = array();
		if (!empty($selected_categories)) {
			$allowed_category_ids = $this->getCategoriesWithChildrenIds($selected_categories);
		}

		// -----------------------------
		// Filterable params (admin settings)
		// eleads_yml_filter_attributes => array(attribute_id, ...)
		// eleads_yml_filter_options    => array(option_id, ...)
		// -----------------------------
		$filter_attribute_ids = $this->config->get('eleads_yml_filter_attributes');
		if (!is_array($filter_attribute_ids)) $filter_attribute_ids = array();
		$filter_attribute_ids = $this->normalizeIntList($filter_attribute_ids);

		$filter_option_ids = $this->config->get('eleads_yml_filter_options');
		if (!is_array($filter_option_ids)) $filter_option_ids = array();
		$filter_option_ids = $this->normalizeIntList($filter_option_ids);

		// for fast checks
		$filter_attribute_map = array();
		foreach ($filter_attribute_ids as $id) $filter_attribute_map[$id] = true;

		$filter_option_map = array();
		foreach ($filter_option_ids as $id) $filter_option_map[$id] = true;

		// -----------------------------
		// XML start
		// -----------------------------
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
		$this->writeElementAlways($xml, 'language', $language_code);

		// -----------------------------
		// Categories
		// -----------------------------
		$xml->startElement('categories');
		if (!empty($allowed_category_ids)) {
			$this->writeSelectedCategoriesTree($xml, $selected_categories, $allowed_category_ids);
		} else {
			$this->writeCategoriesRecursive($xml, 0);
		}
		$xml->endElement(); // categories

		// -----------------------------
		// Offers
		// -----------------------------
		$xml->startElement('offers');

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

			$sort_order = (int)$product_info['sort_order'];

			$category_id = $this->getFirstProductCategoryId($product_id);

			$base_price    = (float)$product_info['price'];
			$special_price = $this->getSpecialPrice($product_id); // float|null

			$images = $this->getProductImages($product_info, $product_id, $shop_url, $pictures_limit);

			// attributes -> params (with optional filter="true")
			$attributes_params = $this->getAttributesParams($product_id, $filter_attribute_map);

			// options -> params (with optional filter="true")
			$options = $this->model_catalog_product->getProductOptions($product_id);
			$variant_option = $this->getVariantOption($options); // first option that has option_value

			if ($variant_option) {
				// One product => many offers (variants)
				$variant_option_id = isset($variant_option['option_id']) ? (int)$variant_option['option_id'] : 0;
				$opt_name = trim((string)$variant_option['name']);

				foreach ($variant_option['option_value'] as $ov) {
					$product_option_value_id = isset($ov['product_option_value_id']) ? (int)$ov['product_option_value_id'] : 0;
					$option_value_id         = isset($ov['option_value_id']) ? (int)$ov['option_value_id'] : 0;

					$val_name = isset($ov['name']) ? trim((string)$ov['name']) : '';
					if ($val_name === '') continue;

					$variant_id = $product_id . '-' . ($product_option_value_id ? $product_option_value_id : $option_value_id);

					$qty = isset($ov['quantity']) ? (int)$ov['quantity'] : (int)$product_info['quantity'];

					$adj = $this->getOptionPriceAdjustment($ov);

					$base_with_opt    = $base_price + $adj;
					$special_with_opt = ($special_price !== null) ? ((float)$special_price + $adj) : null;

					$price = $base_with_opt;
					$old_price = '';

					if ($price_mode === 'special_as_price' && $special_with_opt !== null) {
						$price = $special_with_opt;
						$old_price = $this->formatMoney($base_with_opt);
					}

					$available    = ($qty > 0) ? 'true' : 'false';
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
					$this->writeElementAlways($xml, 'currency', $currency);
					$this->writeElementAlways($xml, 'categoryId', $category_id ? (string)$category_id : '');
					$this->writeElementAlways($xml, 'quantity', (string)$qty);
					$this->writeElementAlways($xml, 'stock_status', $stock_status);

					// pictures (at least one tag)
					if (!empty($images)) {
						foreach ($images as $u) $this->writeElementAlways($xml, 'picture', $u);
					} else {
						$this->writeElementAlways($xml, 'picture', '');
					}

					$this->writeElementAlways($xml, 'vendor', !empty($product_info['manufacturer']) ? $this->sanitizeText($product_info['manufacturer']) : '');

					$vendor_code = !empty($product_info['model']) ? $this->sanitizeText($product_info['model']) : '';
					if ($vendor_code !== '') $vendor_code .= '-' . ($product_option_value_id ? $product_option_value_id : $option_value_id);
					$this->writeElementAlways($xml, 'sku', $vendor_code);

					$this->writeElementAlways($xml, 'label', '');
					$this->writeElementAlways($xml, 'order', $sort_order);

					$desc = $this->plainText($product_info['description']);
					$this->writeElementAlways($xml, 'description', $this->sanitizeText($desc));

					$short = $this->getShortDescription($product_info, $desc, $short_source);
					$this->writeElementAlways($xml, 'short_description', ($short !== '') ? $this->sanitizeText($short) : '');

					// attributes params
					$this->writeParams($xml, $attributes_params);

					// other options params (exclude variant option itself)
					$this->writeOptionsParams($xml, $options, $filter_option_map, $variant_option_id);

					// variant option param (only chosen value), with filter="true" if option is marked
					$is_filter = ($variant_option_id > 0 && isset($filter_option_map[$variant_option_id]));
					$this->writeParam($xml, $opt_name, $val_name, $is_filter);

					$xml->endElement(); // offer
				}
			} else {
				// Single offer (no variant options)
				$qty = (int)$product_info['quantity'];
				$sort_order = (int)$product_info['sort_order'];

				$available    = ($qty > 0) ? 'true' : 'false';
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
				$this->writeElementAlways($xml, 'currency', $currency);
				$this->writeElementAlways($xml, 'categoryId', $category_id ? (string)$category_id : '');
				$this->writeElementAlways($xml, 'quantity', (string)$qty);
				$this->writeElementAlways($xml, 'stock_status', $stock_status);

				if (!empty($images)) {
					foreach ($images as $u) $this->writeElementAlways($xml, 'picture', $u);
				} else {
					$this->writeElementAlways($xml, 'picture', '');
				}

				$this->writeElementAlways($xml, 'vendor', !empty($product_info['manufacturer']) ? $this->sanitizeText($product_info['manufacturer']) : '');
				$this->writeElementAlways($xml, 'sku', !empty($product_info['model']) ? $this->sanitizeText($product_info['model']) : '');
				$this->writeElementAlways($xml, 'label', '');
				$this->writeElementAlways($xml, 'order', $sort_order);

				$desc = $this->plainText($product_info['description']);
				$this->writeElementAlways($xml, 'description', $this->sanitizeText($desc));

				$short = $this->getShortDescription($product_info, $desc, $short_source);
				$this->writeElementAlways($xml, 'short_description', ($short !== '') ? $this->sanitizeText($short) : '');

				// attributes params
				$this->writeParams($xml, $attributes_params);

				// options params (all options)
				$this->writeOptionsParams($xml, $options, $filter_option_map, 0);

				$xml->endElement(); // offer
			}
		}

		$xml->endElement(); // offers
		$xml->endElement(); // shop
		$xml->endElement(); // yml_catalog
		$xml->endDocument();

		$this->response->setOutput($xml->outputMemory());
	}

	// =========================================================
	// Categories output
	// =========================================================

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

	private function writeSelectedCategoriesTree(XMLWriter $xml, $root_ids, $allowed_ids)
	{
		$root_ids = $this->normalizeIntList($root_ids);
		$allowed_map = array();
		foreach ($allowed_ids as $id) $allowed_map[(int)$id] = true;

		foreach ($root_ids as $root_id) {
			if ($root_id <= 0) continue;
			if (!isset($allowed_map[$root_id])) continue;

			$this->writeCategoryNode($xml, $root_id, 0, $allowed_map);
		}
	}

	private function writeCategoryNode(XMLWriter $xml, $category_id, $parent_id_for_feed, $allowed_map)
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
			$child_id = (int)$child_id;
			if (!isset($allowed_map[$child_id])) continue;
			$this->writeCategoryNode($xml, $child_id, (int)$category_id, $allowed_map);
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
		$q = $this->db->query("
			SELECT category_id
			FROM " . DB_PREFIX . "category
			WHERE parent_id=" . (int)$parent_id . "
			ORDER BY sort_order ASC, category_id ASC
		");

		$ids = array();
		foreach ($q->rows as $r) $ids[] = (int)$r['category_id'];
		return $ids;
	}

	// =========================================================
	// Products / prices / images
	// =========================================================

	private function getFirstProductCategoryId($product_id)
	{
		$q = $this->db->query("
			SELECT category_id
			FROM " . DB_PREFIX . "product_to_category
			WHERE product_id=" . (int)$product_id . "
			ORDER BY category_id ASC
			LIMIT 1
		");
		return $q->num_rows ? (int)$q->row['category_id'] : 0;
	}

	private function getSpecialPrice($product_id)
	{
		$q = $this->db->query("
			SELECT price
			FROM " . DB_PREFIX . "product_special
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

	// =========================================================
	// Variants / Options
	// =========================================================

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
		$price  = isset($ov['price']) ? (float)$ov['price'] : 0.0;
		$prefix = isset($ov['price_prefix']) ? (string)$ov['price_prefix'] : '+';

		if ($price == 0.0) return 0.0;
		return ($prefix === '-') ? (0.0 - $price) : $price;
	}

	/**
	 * Write option params
	 * - if option has multiple values => output one <param> per value (same as you did before)
	 * - filter="true" if option_id selected in admin
	 * - skip_option_id allows excluding variant option when we already wrote it
	 */
	private function writeOptionsParams(XMLWriter $xml, $options, $filter_option_map, $skip_option_id = 0)
	{
		if (empty($options) || !is_array($options)) return;

		foreach ($options as $opt) {
			$option_id = isset($opt['option_id']) ? (int)$opt['option_id'] : 0;
			if ($skip_option_id > 0 && $option_id === (int)$skip_option_id) continue;

			$opt_name = isset($opt['name']) ? trim((string)$opt['name']) : '';
			if ($opt_name === '') continue;

			$is_filter = ($option_id > 0 && isset($filter_option_map[$option_id]));

			if (!empty($opt['option_value']) && is_array($opt['option_value'])) {
				foreach ($opt['option_value'] as $ov) {
					$val_name = isset($ov['name']) ? trim((string)$ov['name']) : '';
					if ($val_name === '') continue;

					$this->writeParam($xml, $opt_name, $val_name, $is_filter);
				}
			} else {
				$this->writeParam($xml, $opt_name, '', $is_filter);
			}
		}
	}

	// =========================================================
	// Attributes => params + filter="true"
	// =========================================================

	private function getAttributesParams($product_id, $filter_attribute_map)
	{
		$params = array();

		$attributes = $this->model_catalog_product->getProductAttributes($product_id);
		foreach ($attributes as $group) {
			if (empty($group['attribute'])) continue;

			foreach ($group['attribute'] as $attr) {
				$name  = isset($attr['name']) ? trim((string)$attr['name']) : '';
				$value = isset($attr['text']) ? trim((string)$attr['text']) : '';
				if ($name === '' || $value === '') continue;

				$attribute_id = isset($attr['attribute_id']) ? (int)$attr['attribute_id'] : 0;
				$is_filter = ($attribute_id > 0 && isset($filter_attribute_map[$attribute_id]));

				$params[] = array(
					'name'      => $name,
					'value'     => $value,
					'is_filter' => $is_filter,
				);
			}
		}

		return $params;
	}

	private function writeParams(XMLWriter $xml, $params)
	{
		if (empty($params)) return;

		foreach ($params as $p) {
			$this->writeParam(
				$xml,
				isset($p['name']) ? $p['name'] : '',
				isset($p['value']) ? $p['value'] : '',
				!empty($p['is_filter'])
			);
		}
	}

	// =========================================================
	// XML helpers
	// =========================================================

	private function writeParam(XMLWriter $xml, $name, $value, $is_filter = false)
	{
		$xml->startElement('param');

		if ($is_filter) {
			$xml->writeAttribute('filter', 'true');
		}

		$xml->writeAttribute('name', $this->sanitizeText($name));
		$xml->text($this->sanitizeText($value));
		$xml->endElement();
	}

	private function writeElementAlways(XMLWriter $xml, $name, $value = '')
	{
		$xml->startElement($name);
		if ($value !== '' && $value !== null) {
			$xml->text($value);
		}
		$xml->endElement();
	}

	// =========================================================
	// Text helpers
	// =========================================================

	private function getShortDescription($product_info, $desc_plain, $short_source)
	{
		if ($short_source === 'meta_description' && !empty($product_info['meta_description'])) {
			return trim(html_entity_decode($product_info['meta_description'], ENT_QUOTES, 'UTF-8'));
		}

		$short = (string)$desc_plain;
		$short = trim($short);
		if ($short === '') return '';

		return mb_substr($short, 0, 140, 'UTF-8');
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

	private function normalizeIntList($list)
	{
		$out = array();
		foreach ((array)$list as $v) {
			$i = (int)$v;
			if ($i > 0) $out[] = $i;
		}
		return array_values(array_unique($out));
	}

	// =========================================================
	// Category filter helpers
	// =========================================================

	private function getProductsByCategoryIds($category_ids)
	{
		$category_ids = $this->normalizeIntList($category_ids);
		if (empty($category_ids)) return array();

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
		$root_ids = $this->normalizeIntList($root_ids);
		$all = array();

		foreach ($root_ids as $id) {
			$all[] = $id;
			$this->collectChildCategoryIds($id, $all);
		}

		return array_values(array_unique($all));
	}

	private function collectChildCategoryIds($parent_id, &$result)
	{
		$q = $this->db->query("
			SELECT category_id
			FROM " . DB_PREFIX . "category
			WHERE parent_id=" . (int)$parent_id
		);

		if (!$q->num_rows) return;

		foreach ($q->rows as $row) {
			$cid = (int)$row['category_id'];

			if (!in_array($cid, $result)) {
				$result[] = $cid;
				$this->collectChildCategoryIds($cid, $result);
			}
		}
	}
}
