<?php 
/*********add wp style and script for backend*******************/

/**
 * Register and enqueue a custom stylesheet in the WordPress admin.
 */
class amazon_search_product_result {

	public function __construct() {
		add_action('wp_ajax_amazon_query_products', array(__CLASS__, 'amazon_query_products'));
		add_filter('amazon_query_products_args', array(__CLASS__, 'query_products_args'), 10, 2);
		add_filter('amazon_query_products_response', array(__CLASS__, 'query_products_response'), 10, 2);
		add_action('wp_ajax_amazon_save_image', array(__CLASS__, 'amazon_save_image'));

	}
	
	public static function amazon_save_image() {
		$data = stripslashes_deep($_POST);

		$url = isset($data['url']) ? $data['url'] : false;
		$urlname = isset($data['urlname']) ? $data['urlname'] : false;
		$urlname = str_replace(', ','',$urlname);
		$urlname = str_replace('-','',$urlname);
		$urlname = substr($urlname,0,20);
		$urlname = str_replace(' ','',$urlname);
		if($url) {
			$response = wp_remote_get($url);

			
			if(!is_wp_error($response)) {
				$name   = preg_replace('#[^0-9A-Za-z\.]#', '', basename($url));
				$name = explode('.',$name);
				$imagename = $urlname.'.'.$name[1];
				
				$result = wp_upload_bits($imagename, null, wp_remote_retrieve_body($response));
				
				$image = wp_get_image_editor( $result['file'] ); // Return an implementation that extends WP_Image_Editor
				if ( ! is_wp_error( $image ) ) {
					$image->resize( 380, true ); //Here we set width and height to 320, 80 respectively
					$data =$image->save($upload['file']);
				}
				
				
				if(!isset($result['error']) || empty($result['error']) && isset($result['url'])) {
					$url = $result['url'];
					//print_r($url); die();
				}
			}
		}
		$base_dir = str_replace('/wp-content/plugins/amazon-product/inc','',dirname(__FILE__));
		$url =site_url().str_replace($base_dir,'',$data['path']);
		echo json_encode(array('url'=>$url));
		die();
		
	}
	
	public static function amazon_query_products() {
		$data = stripslashes_deep($_POST);

		$args     = apply_filters('amazon_query_products_args', array(), $data);

		$keywords = isset($data['keywords']) ? $data['keywords'] : '';
		$locale   = isset($data['locale']) ? $data['locale'] : 'US';
		$page     = isset($data['page']) ? $data['page'] : 1;

		$response = amazon_api_search($keywords, $page, $locale, null, $args);
		
		//print_r($response);
		
		if(is_wp_error($response)) {
			$response = array(
				'error'   => true,
				'message' => __('There was an issue with your search and no items were found.'),
				'items'   => array(),
				'locale'  => $locale,
				'page'    => 1,
				'pages'   => 1,
			);
		} else {
			$response = array_merge(array(
				'error'    => false,
				'messages' => false,
			), $response);
		}
  	
		$response = apply_filters('amazon_query_products_response', $response, $data);
		
		$html = '';
		$message = '';
		$showing = '';
		$pagination = '';
		if($response['error']==1)
		{
			$count = 0;
			$html .= '<tr>
                    <td colspan="3">No products were found</td>
                  </tr>';
			$message = $response['message'];
		}else
		{
			$final_array = array();
			$array_count = 0;
			$message = '';
			
			
			foreach($response['items'] as $product)
			{
				$final_array[$array_count]['Feature'] = $product['attributes']['Feature'];
				$final_array[$array_count]['ListPrice'] = $product['attributes']['ListPrice'];
				$final_array[$array_count]['Brand'] = $product['attributes']['Brand'];
				$final_array[$array_count]['MPN'] = $product['attributes']['MPN'];
				$final_array[$array_count]['Model'] = $product['attributes']['Model'];
				$final_array[$array_count]['Title'] = $product['attributes']['Title'];
				$final_array[$array_count]['url'] = $product['url'];
				$final_array[$array_count]['url_title'] = $product['title'];
				$final_array[$array_count]['images_setup'] = $product['images'];
				$final_array[$array_count]['offer_price'] = $product['offer']['price'];
				$final_array[$array_count]['offer_saved'] = $product['offer']['saved'];
				foreach($product['images'] as $image)
				{
					if($image['height']>530 && $image['width']>900)
					{
						$final_array[$array_count]['images'] = $image['url'];
					}
				}
				$array_count++;
			}
			$pg = 0;
			
			foreach($final_array as $fnar)
			{
				$html .='<tr id="image_pop_hide'.$pg.'">
						  <td class="amazon-search-result-column-image"><a href="'.$fnar['url'].'" target="_blank" ><img alt="'.$fnar['Title'].'" height="82" src="'.$fnar['images'].'" width="110"></a></td>
						  <td class="amazon-search-result-column-title"><a href="'.$fnar['url'].'" target="_blank" >'.$fnar['Title'].'</a></td>';
						  if($data['version']=='usa')
						  {
							  $html .='<td class="amazon-search-result-column-insert"><span><a class="amazon-select-product" data-hide_image_popup="image_pop_hide'.$pg.'" data-image_popup_id="image_pop_'.$pg.'">Select Product</a></span></td>';
						  }
						  elseif($data['version']=='uk')
						  {
							  $html .='<td class="amazon-search-result-column-insert"><span><a class="amazon-select-product-uk" data-insertLink="'.$fnar['url'].'" >Insert Product</a></span></td>';
						  }
						  
						$html .='</tr>';
				$html .='<tr id="image_pop_'.$pg.'" style="display:none;">
					  <th scope="row">Choose Image</th>
					  <td>
						<div class="wp-tab-panel" >
						  <div class="amazon-popup-state-image-choices-container">';
						  $checked = 'checked="checked"';
						  foreach($fnar['images_setup'] as $setup)
						  {
							$html .='<label class="amazon-popup-state-image-choices-choice">
								  <input type="radio" class="amazon-popup-state-image-choices-choice-selector" '.$checked.' name="amazon-popup-state-image-choices-choice-selector" value="'.$setup['url'].'">
								  <span class="amazon-popup-state-image-choices-choice-frame"></span> <span class="amazon-popup-state-image-choices-choice-image-sizer"> 
								  <img alt="'.$fnar['Title'].'" class="amazon-popup-state-image-choices-choice-image" height="'.$setup['height'].'" src="'.$setup['url'].'" width="'.$setup['width'].'"> </span> 
								  <span class="amazon-popup-state-image-choices-choice-dimensions"> <span data-bind="text: width">'.$setup['width'].'</span> x <span data-bind="text: height">'.$setup['height'].'</span> </span> </label>';
							$checked = '';
						  }
						  $locale_set = $locale=='IN'?'in':'com';
						$html .='</div>
						</div></td>
						<td scope="row"><a class="insert_product" data-hide_image_popup="image_pop_hide'.$pg.'" data-image_popup_id="image_pop_'.$pg.'" data-insertTitle="'.$fnar['Title'].'" data-searchurl="https://www.amazon.'.$locale_set.'/s/?url=search-alias&field-keywords='.$fnar['Title'].'" data-searchtitle="'.$fnar['Brand'].'" data-insertPrice="'.$fnar['ListPrice'].'" data-offerPrice="'.$fnar['offer_price'].'" data-savedPrice="'.$fnar['offer_saved'].'"  data-insertLink="'.$fnar['url'].'"  data-insertImagename="'.$fnar['Title'].'">Insert Product</a> | <span><a class="amazon-cancel-product" data-hide_image_popup="image_pop_hide'.$pg.'" data-image_popup_id="image_pop_'.$pg.'">Cancel</a></span></td>
					</tr>';
				$pg++;
			}
			$count = $response['pages'];
			
				$pagination .='<ul>';
				for($p = 1;$p<=$count;$p++)
				{
					if($page==$p)
					{
						$class = 'active';
					}else
					{
						$class = '';
					}
					$pagination .= '<li><a href="javascript:void();" class="showing_products '.$class.'" data-pages="'.$p.'">'.$p.'</a></li>';
					
				}
				$pagination .= '</ul>';
				$showing = 'showing 10 / '. $count*10 .' Products';
			
		}
		echo json_encode(array('html'=>$html, 'showing'=>$showing, 'message'=>$message,'pagination' => $pagination));
		die();
	}
	
	public static function query_products_response($response, $data) {
		if(isset($data['index'])) {
			$response['index'] = $data['index'];
		}

		if(isset($data['priceMin'])) {
			$response['priceMin'] = $data['priceMin'];
		}

		if(isset($data['priceMin'])) {
			$response['priceMin'] = $data['priceMin'];
		}

		if(isset($data['sort'])) {
			$response['sort'] = $data['sort'];
		}

		return $response;
	}
	public function query_products_args($args, $data) {

		if(isset($data['index']) && !empty($data['index'])) {
			$args['SearchIndex'] = str_replace(' ','',$data['index']);
		}
		if(isset($data['priceMin']) && !empty($data['priceMin']) && is_numeric($data['priceMin']) && $data['priceMin'] > 0) {
			$args['MinimumPrice'] = amazon_get_converted_currency($data['priceMin'], $data['locale']);
		}

		if(isset($data['priceMax']) && !empty($data['priceMax']) && is_numeric($data['priceMax']) && $data['priceMax'] > 0) {
			$args['MaximumPrice'] = amazon_get_converted_currency($data['priceMax'], $data['locale']);
		}

		if(isset($data['sort']) && !empty($data['sort'])) {
			$args['Sort'] = str_replace(' ','',$data['sort']);
		}

		return $args;
	}
	
 }
new amazon_search_product_result;

function amazon_get_image_id($image_url) {
	global $wpdb;
	$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url )); 
        return $attachment[0]; 
}
function amazon_api_search($keywords, $page = 1, $locale = null, $associate_tag = null, $args = array()) {
	
	$query = array_merge(array(
			'AssociateTag' => $associate_tag,
			'ItemPage' => $page,
			'Keywords' => urlencode($keywords),
			'Operation' => 'ItemSearch',
			'ResponseGroup' => 'BrowseNodes,Images,ItemAttributes,Offers',
			'SearchIndex' => 'All',
		), $args);

		return amazon_api_response_items(amazon_api_request($query, $locale), $locale);
	}
	function amazon_api_response_items($response, $locale) {
		if(is_wp_error($response)) {
			return $response;
		}
	
		$keywords = isset($response['Items']) && isset($response['Items']['Request']) && isset($response['Items']['Request']['ItemSearchRequest']) && isset($response['Items']['Request']['ItemSearchRequest']['Keywords']) ? $response['Items']['Request']['ItemSearchRequest']['Keywords'] : '';
		$page     = isset($response['Items']) && isset($response['Items']['Request']) && isset($response['Items']['Request']['ItemSearchRequest']) && isset($response['Items']['Request']['ItemSearchRequest']['ItemPage']) ? max(1, $response['Items']['Request']['ItemSearchRequest']['ItemPage']) : 1;
		$pages    = isset($response['Items']) && isset($response['Items']['TotalPages']) ? min(5, intval($response['Items']['TotalPages'])) : 1;
		$items    = isset($response['Items']) && isset($response['Items']['Item']) ? array_map('amazon_api_response_normalize', $response['Items']['Item']) : array();
	
		return compact(
			'keywords',
			'locale',
			'page',
			'pages',
			'items'
		);
	}

	function amazon_api_request($query, $locale = null) {

		$locale = amazon_get_locale($locale);
	
		if(!isset($query['AssociateTag']) || empty($query['AssociateTag'])) {
			$query['AssociateTag'] = amazon_get_locale_associate_tag($locale);
		}
	
		if(!isset($query['AWSAccessKeyId']) || empty($query['AWSAccessKeyId'])) {
			$query['AWSAccessKeyId'] = get_option('amazon_access_key');
		}
	
		if(!isset($query['Service']) || empty($query['Service'])) {
			$query['Service'] = 'AWSECommerceService';
		}
	
		if(!isset($query['Timestamp']) || empty($query['Timestamp'])) {
			$query['Timestamp'] = gmdate('Y-m-d\TH:i:s\Z');
		}
	
		if(!isset($query['Version']) || empty($query['Version'])) {
			$query['Version'] = '2013-08-01';
		}
		
		$request_url = amazon_api_request_sign(add_query_arg($query, amazon_get_locale_endpoint($locale)));
		$response = wp_remote_get($request_url, array(
			'timeout' => 10,
			'httpversion' => '4.0.17',
		));

		return is_wp_error($response) ? $response : amazon_api_response(wp_remote_retrieve_body($response));
	}
	function amazon_api_response($response_string) {
		$xml = @simplexml_load_string($response_string);
	
		if(!is_object($xml)) {
			$response = new WP_Error('parse_response_xml_error', __('Could not parse the response from Amazon as XML.'));
		} else if(isset($xml->Error)) {
			$response = new WP_Error((string)$xml->Error->Code, (string)$xml->Error->Message);
		} else if(isset($xml->Items->Request->Errors->Error)) {
			$response = new WP_Error((string)$xml->Items->Request->Errors->Error->Code, (string)$xml->Items->Request->Errors->Error->Message);
		} else {
			$response = json_decode(json_encode($xml), true);
			if(isset($response['Items']) && isset($response['Items']['Item'])) {
				if(isset($response['Items']['Item']) && isset($response['Items']['Item']['ASIN'])) {
					$response['Items']['Item'] = array($response['Items']['Item']);
				}
	
				foreach($response['Items']['Item'] as $item_key => $item) {
					if(!isset($item['ImageSets']) || !isset($item['ImageSets']['ImageSet']) || !is_array($item['ImageSets']['ImageSet'])) {
						$response['Items']['Item'][$item_key]['ImageSets']['ImageSet'] = array();
					}
	
					$sets = array();
	
					if(!isset($response['Items']['Item'][$item_key]['ImageSets']['ImageSet'][0])) {
						$response['Items']['Item'][$item_key]['ImageSets']['ImageSet'][] = $response['Items']['Item'][$item_key]['ImageSets']['ImageSet'];
					}
	
					foreach($response['Items']['Item'][$item_key]['ImageSets']['ImageSet'] as $set) {
						$attributes = isset($set['@attributes']) && is_array($set['@attributes']) ? $set['@attributes'] : false;
						unset($set['@attributes']);
	
						if($attributes && isset($attributes['Category']) && 'primary' === $attributes['Category']) {
							$set = array_reverse($set);
	
							foreach($set as $image_key => $image) {
								array_unshift($sets, $image);
							}
						} else {
							foreach($set as $image_key => $image) {
								array_push($sets, $image);
							}
						}
					}
	
					$response['Items']['Item'][$item_key]['ImageSets']['ImageSet'] = $sets;
				}
			}
		}
	
		if(is_wp_error($response)) {
			return $response;
		}
	
		return $response;
	}
	
	function amazon_api_request_sign($url) {
	// Decode anything already encoded
		$url = urldecode($url);
	
		// Parse the URL into $urlparts
		$urlparts = parse_url($url);
	
		// Build $params with each name/value pair
		foreach (explode('&', $urlparts['query']) as $part) {
			if (strpos($part, '=')) {
				list($name, $value) = explode('=', $part);
			} else {
				$name = $part;
				$value = '';
			}
			$params[$name] = $value;
		}
	
		// Sort the array by key
		ksort($params);
	
		// Build the canonical query string
		$canonical = '';
		foreach ($params as $key=>$val) {
			$canonical .= "{$key}=".rawurlencode($val).'&';
		}
		// Remove the trailing ampersand
		$canonical = preg_replace("/&$/", '', $canonical);
	
		// Some common replacements and ones that Amazon specifically mentions
		$canonical = str_replace(array(' ', '+', ', ', ';'), array('%20', '%20', urlencode(','), urlencode(':')), $canonical);
	
		// Build the si
		$string_to_sign = "GET\n{$urlparts['host']}\n{$urlparts['path']}\n$canonical";
		// Calculate our actual signature and base64 encode it
		$signature = base64_encode(hash_hmac('sha256', $string_to_sign, get_option('amazon_secret_key'), true));

		// Finally re-build the URL with the proper string and include the Signature
		return "{$urlparts['scheme']}://{$urlparts['host']}{$urlparts['path']}?$canonical&Signature=".rawurlencode($signature);
	}
	 function amazon_get_locale_endpoints() {
		return apply_filters(__FUNCTION__, array(
			'US' => 'https://webservices.amazon.com/onca/xml',
			'BR' => 'https://webservices.amazon.com.br/onca/xml',
			'CA' => 'https://webservices.amazon.ca/onca/xml',
			'CN' => 'https://webservices.amazon.cn/onca/xml',
			'DE' => 'https://webservices.amazon.de/onca/xml',
			'ES' => 'https://webservices.amazon.es/onca/xml',
			'FR' => 'https://webservices.amazon.fr/onca/xml',
			'IT' => 'https://webservices.amazon.it/onca/xml',
			'IN' => 'https://webservices.amazon.in/onca/xml',
			'JP' => 'https://webservices.amazon.co.jp/onca/xml',
			'UK' => 'https://webservices.amazon.co.uk/onca/xml',
		));
	}
	
	function amazon_get_locale_endpoint($locale) {
		$locale = amazon_get_locale($locale);
		$locale_endpoints = amazon_get_locale_endpoints();
	
		return isset($locale_endpoints[$locale]) ? $locale_endpoints[$locale] : current($locale_endpoints);
	}
	
	function amazon_get_locale_associate_tag($locale) {
		$locale =amazon_get_locale($locale);
		$locale_associate_tags = amazon_get_locale_associate_tags();
	
		return isset($locale_associate_tags[$locale]) ? $locale_associate_tags[$locale] : current($locale_associate_tags);
	}
	function amazon_get_locale_associate_tags() {
		return apply_filters(__FUNCTION__, array(
			//'US' => 'al24-20',
			'US' => get_option('amazon_us'),
			'BR' => 'al40-20',
			'CA' => 'al25-20',
			'CN' => 'al33-23',
			'DE' => 'al28-21',
			'ES' => 'al32-21',
			'FR' => 'al30-21',
			'IT' => 'al31-21',
			'IN' => 'amazon-20',
			'JP' => 'al32-22',
			//'UK' => 'cevaexcom-21',
			'UK' => get_option('amazon_uk'),
		));
	}
	
	function amazon_get_converted_currency($value, $locale) {
		$converted = $value;
		$locale = amazon_get_locale($locale);
	
		switch($locale) {
			case 'BR':
			case 'CA':
			case 'CN':
			case 'DE':
			case 'ES':
			case 'FR':
			case 'IN':
			case 'IT':
			case 'UK':
			case 'US':
				$converted = ($value * 100);
				break;
		}
	
		return $converted;
	}
	function amazon_get_locales() {
		return apply_filters(__FUNCTION__, array(
			'US' => __('United States'),
			'BR' => __('Brazil'),
			'CA' => __('Canada'),
			'CN' => __('China'),
			'FR' => __('France'),
			'DE' => __('Germany'),
			'IT' => __('Italy'),
			'IN' => __('India'),
			'JP' => __('Japan'),
			'ES' => __('Spain'),
			'UK' => __('United Kingdom'),
		));
	}
	
	function amazon_get_locale($locale) {
		$locale = strtoupper($locale);
		$locales = amazon_get_locales();
	
		return isset($locales[$locale]) ? $locale : key($locales);
	}	
	function amazon_api_response_normalize_attributes($attributes) {
		$normalized = array();
	
		// $attributes = array_intersect_key($attributes, amazon_get_attributes());
	
		foreach($attributes as $name => $value) {
			if(is_string($value)) {
				$normalized[$name] = $value;
			} else if(is_array($value) && preg_match('#^.*Dimensions$#', $name)) {
				$normalized[$name] = $value;
			} else if(is_array($value) && preg_match('#^.*List$#', $name)) {
				$normalized[$name] = array_values($value);
			} else if(is_array($value) && preg_match('#^.*Price$#', $name)) {
				$normalized[$name] = $value['FormattedPrice'];
			} else if(is_array($value)) {
				$normalized[$name] = array_values($value);
			}
		}
	
		return $normalized;
	}
	function amazon_api_response_normalize_browse_nodes($browse_nodes) {
	if(isset($browse_nodes['BrowseNode'])) {
		$browse_nodes = isset($browse_nodes['BrowseNode'][0]) ? $browse_nodes['BrowseNode'] : array($browse_nodes['BrowseNode']);
	} else {
		$browse_nodes = array();
	}

	$normalized = array();
	foreach($browse_nodes as $browse_node) {
		$normalized[] = array(
			'ancestors' => isset($browse_node['Ancestors']) ? (amazon_api_response_normalize_browse_nodes($browse_node['Ancestors'])) : array(),
			'children'  => isset($browse_node['Children']) ? (amazon_api_response_normalize_browse_nodes($browse_node['Children'])) : array(),
			'id'        => $browse_node['BrowseNodeId'],
			'name'      => $browse_node['Name'],
			'root'      => isset($browse_node['IsCategoryRoot']) && '1' == $browse_node['IsCategoryRoot'],
		);
	}

	return $normalized;
}
	function amazon_api_response_normalize($item) {
		$attributes = isset($item['ItemAttributes']) && is_array($item['ItemAttributes']) ? amazon_api_response_normalize_attributes($item['ItemAttributes']) : array();
		$identifier = isset($item['ASIN']) ? $item['ASIN'] : false;
		$images     = array();
		$nodes      = isset($item['BrowseNodes']) && is_array($item['BrowseNodes']) ? amazon_api_response_normalize_browse_nodes($item['BrowseNodes']) : array();
		$title      = isset($attributes['Title']) ? $attributes['Title'] : '';
		$url        = current(explode('?', urldecode($item['DetailPageURL'])));
	
		$offer = isset($item['Offers']) && is_array($item['Offers']) && isset($item['Offers']['Offer']) && is_array($item['Offers']['Offer']) ? $item['Offers']['Offer'] : array();
	
		$price = false;
		if(isset($offer['OfferListing']) && isset($offer['OfferListing']['SalePrice']) && isset($offer['OfferListing']['SalePrice']['FormattedPrice'])) {
			$price = $offer['OfferListing']['SalePrice']['FormattedPrice'];
	
			if(isset($offer['OfferListing']) && isset($offer['OfferListing']['Price']) && isset($offer['OfferListing']['Price']['FormattedPrice']) && !isset($attributes['ListPrice'])) {
				$attributes['ListPrice'] = $offer['OfferListing']['Price']['FormattedPrice'];
			}
		} else if(isset($offer['OfferListing']) && isset($offer['OfferListing']['Price']) && isset($offer['OfferListing']['Price']['FormattedPrice'])) {
			$price = $offer['OfferListing']['Price']['FormattedPrice'];
		} else {
			$price = __('N/A');
		}
	
		$offer = array(
			'condition' => isset($offer['OfferAttributes']) && isset($offer['OfferAttributes']['Condition']) ? $offer['OfferAttributes']['Condition'] : __('Unknown'),
			'price' => $price,
			'saved' => isset($offer['OfferListing']) && isset($offer['OfferListing']['AmountSaved']) && isset($offer['OfferListing']['AmountSaved']['FormattedPrice']) ? $offer['OfferListing']['AmountSaved']['FormattedPrice'] : __('N/A'),
		);
	
		$lowest_price_n = isset($item['OfferSummary']) && isset($item['OfferSummary']['LowestNewPrice']) && isset($item['OfferSummary']['LowestNewPrice']['FormattedPrice']) ? $item['OfferSummary']['LowestNewPrice']['FormattedPrice'] : false;
		$lowest_price_r = isset($item['OfferSummary']) && isset($item['OfferSummary']['LowestRefurbishedPrice']) && isset($item['OfferSummary']['LowestRefurbishedPrice']['FormattedPrice']) ? $item['OfferSummary']['LowestRefurbishedPrice']['FormattedPrice'] : false;
		$lowest_price_u = isset($item['OfferSummary']) && isset($item['OfferSummary']['LowestUsedPrice']) && isset($item['OfferSummary']['LowestUsedPrice']['FormattedPrice']) ? $item['OfferSummary']['LowestUsedPrice']['FormattedPrice'] : false;
	
		$image_urls = array();
	
		if(isset($item['ImageSets']) && is_array($item['ImageSets']) && isset($item['ImageSets']['ImageSet']) && is_array($item['ImageSets']['ImageSet'])) {
			$image_sets = isset($item['ImageSets'][0]) ? $item['ImageSets']['ImageSet'] : array($item['ImageSets']['ImageSet']);
	
			foreach($image_sets as $image_set) {
				foreach($image_set as $image_key => $image) {
					if('@attributes' === $image_key || !isset($image['URL']) || in_array($image['URL'], $image_urls)) { continue; }
	
					$image_urls[] = $image['URL'];
	
					$images[] = array(
						'url' => $image['URL'],
						'height' => $image['Height'],
						'width' => $image['Width'],
					);
				}
			}
		} else {
			if(isset($item['SmallImage']) && !in_array($item['SmallImage']['URL'], $image_urls)) {
				$image_urls[] = $item['SmallImage']['URL'];
	
				$images[] = array(
					'url' => $item['SmallImage']['URL'],
					'height' => $item['SmallImage']['Height'],
					'width' => $item['SmallImage']['Width'],
				);
			}
	
			if(isset($item['MediumImage']) && !in_array($item['MediumImage']['URL'], $image_urls)) {
				$image_urls[] = $item['MediumImage']['URL'];
	
				$images[] = array(
					'url' => $item['MediumImage']['URL'],
					'height' => $item['MediumImage']['Height'],
					'width' => $item['MediumImage']['Width'],
				);
			}
	
			if(isset($item['LargeImage']) && !in_array($item['LargeImage']['URL'], $image_urls)) {
				$image_urls[] = $item['LargeImage']['URL'];
	
				$images[] = array(
					'url' => $item['LargeImage']['URL'],
					'height' => $item['LargeImage']['Height'],
					'width' => $item['LargeImage']['Width'],
				);
			}
		}
	
		return compact(
			'attributes',
			'identifier',
			'images',
			'lowest_price_n',
			'lowest_price_r',
			'lowest_price_u',
			'nodes',
			'offer',
			'title',
			'url'
		);
	}