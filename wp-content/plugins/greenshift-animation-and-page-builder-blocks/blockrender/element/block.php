<?php

namespace greenshiftaddon\Blocks;

defined('ABSPATH') or exit;


class Element
{

	public function __construct()
	{
		add_action('init', array($this, 'init_handler'));
	}

	/**
	 * Convert YouTube and Vimeo URLs to embed format with customizable settings
	 * 
	 * @param string $src The source URL
	 * @param array $extra_filters Additional filter options for embed parameters
	 * @return string The embed URL with applied settings
	 */
	private function embedsrc($src, $extra_filters = array()) {
		// If src already contains 'embed', return as is
		if ($src && strpos($src, 'embed') !== false) {
			return $src;
		}

		// Check if it's a YouTube link
		if ($src && (strpos($src, 'youtube.com') !== false || strpos($src, 'youtu.be') !== false)) {
			$videoId = null;
			
			// Extract video ID from different YouTube URL formats
			if (strpos($src, 'youtu.be/') !== false) {
				// Format: https://youtu.be/jyUpZ6Unpq4?si=HKt3SYuJxhefRfR0
				$parts = explode('youtu.be/', $src);
				if (isset($parts[1])) {
					$videoId = explode('?', $parts[1])[0];
				}
			} elseif (strpos($src, 'youtube.com/watch?v=') !== false) {
				// Format: https://www.youtube.com/watch?v=k4pL1i4sF7c
				$parts = explode('v=', $src);
				if (isset($parts[1])) {
					$videoId = explode('&', $parts[1])[0];
				}
			}
			
			// If we found a video ID, convert to embed format
			if ($videoId) {
				$embedUrl = 'https://www.youtube.com/embed/' . $videoId;
				$params = array();
				
				// Apply parameters based on extra_filters
				if (!empty($extra_filters['enableAutoplay'])) {
					$params[] = 'autoplay=1';
					$params[] = 'mute=1';
				}
				if (!empty($extra_filters['disableControls'])) {
					$params[] = 'controls=0';
				} else {
					$params[] = 'controls=1';
				}
				if (!empty($extra_filters['disableInformation'])) {
					$params[] = 'showinfo=0';
				}
				if (!empty($extra_filters['enableModestBranding'])) {
					$params[] = 'modestbranding=1';
				}
				if (!empty($extra_filters['enableLoop'])) {
					$params[] = 'loop=1';
				}
				
				return count($params) > 0 ? $embedUrl . '?' . implode('&', $params) : $embedUrl;
			}
		}
		
		// Check if it's a Vimeo link
		if ($src && (strpos($src, 'vimeo.com') !== false || strpos($src, 'player.vimeo.com') !== false)) {
			$videoId = null;
			
			// Extract video ID from different Vimeo URL formats
			if (strpos($src, 'vimeo.com/') !== false) {
				// Format: https://vimeo.com/123456789 or https://vimeo.com/123456789?h=abc123
				$parts = explode('vimeo.com/', $src);
				if (isset($parts[1])) {
					$videoId = explode('?', $parts[1])[0];
				}
			} elseif (strpos($src, 'player.vimeo.com/video/') !== false) {
				// Format: https://player.vimeo.com/video/123456789
				$parts = explode('player.vimeo.com/video/', $src);
				if (isset($parts[1])) {
					$videoId = explode('?', $parts[1])[0];
				}
			}
			
			// If we found a video ID, convert to embed format
			if ($videoId) {
				$embedUrl = 'https://player.vimeo.com/video/' . $videoId;
				$params = array();
				
				// Apply parameters based on extra_filters
				if (!empty($extra_filters['enableAutoplay'])) {
					$params[] = 'autoplay=1';
					$params[] = 'muted=1';
				}
				if (!empty($extra_filters['disableControls'])) {
					$params[] = 'controls=0';
				}
				if (!empty($extra_filters['enableLoop'])) {
					$params[] = 'loop=1';
				}
				
				return count($params) > 0 ? $embedUrl . '?' . implode('&', $params) : $embedUrl;
			}
		}
		
		// Return original src if not a YouTube/Vimeo link or couldn't extract ID
		return $src;
	}

	public function init_handler()
	{
		register_block_type(
			__DIR__,
			array(
				'render_callback' => array($this, 'render_block'),
			)
		);
	}


	public function render_block($settings, $inner_content, $block)
	{
		$block = (is_array($block)) ? $block : $block->parsed_block;
		$html = $inner_content;

		if(!empty($block['attrs']['styleAttributes']['hideOnFrontend_Extra'])){
			if(!is_admin()){
				return '';
			}
		}

		if (!empty($block['attrs']['localStyles']['background']['lazy'])) {
			wp_enqueue_script('greenshift-inview-bg');
		}
		if (!empty($block['attrs']['customCursor'])) {
			wp_enqueue_script('cursor-follow');
		}
		if (!empty($block['attrs']['cursorEffect'])) {
			wp_enqueue_script('cursor-shift');
		}
		if(!empty($block['attrs']['styleAttributes']['animationTimeline'])){
			wp_enqueue_script('scroll-view-polyfill');
		}
		if(!empty($block['attrs']['styleAttributes']['anchorName'])){
			wp_enqueue_script('anchor-polyfill');
		}
		if (isset($block['attrs']['tag'])) {
			if($block['attrs']['tag'] == 'table' && (!empty($block['attrs']['tableAttributes']['table']['sortable']) || !empty($block['attrs']['tableStyles']['table']['style']))){
				wp_enqueue_script('gstablesort');
			}else if($block['attrs']['tag'] == 'iframe'){
				if(!empty($block['attrs']['src'])){
					$p = new \WP_HTML_Tag_Processor( $html );
					$p->next_tag();
					$src = greenshift_dynamic_placeholders(esc_url($block['attrs']['src']));
					if(!empty($block['attrs']['isVariation']) && ($block['attrs']['isVariation'] == 'youtubeplay' || $block['attrs']['isVariation'] == 'vimeoplay')){
						$extra_filters = !empty($block['attrs']['extra_filters']) ? $block['attrs']['extra_filters'] : array();
						$src = $this->embedsrc($src, $extra_filters);
					}
					$p->set_attribute( 'src', $src);
					$html = $p->get_updated_html();
				}
			} else if($block['attrs']['tag'] == 'a'){
				if(!empty($block['attrs']['href']) && strpos($block['attrs']['href'], '{{') !== false){
					$p = new \WP_HTML_Tag_Processor( $html );
					$p->next_tag();
					$p->set_attribute( 'href', greenshift_dynamic_placeholders(esc_attr($block['attrs']['href'])));
					$html = $p->get_updated_html();
				}
				if(!empty($block['attrs']['title']) && strpos($block['attrs']['title'], '{{') !== false){
					$p = new \WP_HTML_Tag_Processor( $html );
					$p->next_tag();
					$p->set_attribute( 'title', greenshift_dynamic_placeholders(esc_attr($block['attrs']['title'])));
					$html = $p->get_updated_html();
				}
			} else if($block['attrs']['tag'] == 'video'){
				if(!empty($block['attrs']['lazyLoadVideo'])){
					wp_enqueue_script('gs-lazyloadvideo');
				}
			}
		}

		if (!empty($block['attrs']['type']) && $block['attrs']['type'] == 'repeater') {
			//Generate dynamic repeater
			// Extract content between <repeater> tags
			$pattern = '/<repeater>(.*?)<\/repeater>/s';
			if (preg_match($pattern, $html, $matches)) {
				$repeater = $matches[1];

				if(!empty($block['attrs']['repeaterType']) && $block['attrs']['repeaterType'] == 'api_request' && !empty($block['attrs']['api_filters']) && !empty($block['attrs']['api_filters']['useAjax'])){
					$p = new \WP_HTML_Tag_Processor( $html );
					$p->next_tag();
					$blockid = 'api_id_'.\greenshift_sanitize_id_key($block['attrs']['localId']);
					$blockid = str_replace('-','_', $blockid);
					$p->set_attribute( 'data-api-id', $blockid);
					$p->set_attribute( 'data-dynamic-api', 'true');
					$p->set_attribute( 'data-dynamic-api-trigger', !empty($block['attrs']['api_filters']['ajaxTrigger']) ? esc_attr($block['attrs']['api_filters']['ajaxTrigger']) : 'load');
					if(!empty($block['attrs']['api_filters']['ajaxTrigger']) && $block['attrs']['api_filters']['ajaxTrigger'] == 'form' && !empty($block['attrs']['api_filters']['ajaxSelector'])){
						$p->set_attribute( 'api-form-selector', esc_attr($block['attrs']['api_filters']['ajaxSelector']));
					}
					if(!empty($block['attrs']['api_filters']['apiReplace'])){
						$p->set_attribute( 'data-api-show-method', esc_attr($block['attrs']['api_filters']['apiReplace']));
					}
					if(!empty($block['attrs']['api_filters']['loader_selector'])){
						$p->set_attribute( 'data-api-loader-selector', esc_attr($block['attrs']['api_filters']['loader_selector']));
					}
					if(!empty($block['attrs']['api_filters']['pagination_selector'])){
						$p->set_attribute( 'data-api-pagination-selector', esc_attr($block['attrs']['api_filters']['pagination_selector']));
					}
					$html = $p->get_updated_html();
					set_transient($blockid, $block, 60 * 60 * 24 * 100);
					$rest_vars = array(
						'rest_url' => esc_url_raw(rest_url('greenshift/v1/api-connector/')),
						'nonce' => wp_create_nonce('wp_rest'),
					);
					wp_localize_script('gspb-apiconnector', 'api_connector_vars', $rest_vars);
					wp_enqueue_script('gspb-apiconnector');	

					// We clean because it will be generated dynamically
					$html = preg_replace($pattern, '', $html);

				} else{
					// Generate dynamic repeater content
					$generated_content = GSPB_generate_dynamic_repeater($repeater, $block);
					
					// Replace the <repeater> tags and their content with the generated content
					$html = preg_replace($pattern, $generated_content, $html);
				}
				
			} 
		}
		if(!empty($block['attrs']['isVariation'])){
			if($block['attrs']['isVariation'] == 'marquee'){
				$pattern = '/<div class="gspb_marquee_content">(.*?)<span class="gspb_marquee_content_end"><\/span><\/div>/s';
				$html = preg_replace_callback($pattern, function ($matches) {
					// Original div
					$originalDiv = '<div class="gspb_marquee_content">'.$matches[1].'</div>';
					
					// Duplicated div with aria-hidden="true"
					$duplicatedDiv = '<div class="gspb_marquee_content" aria-hidden="true">'.$matches[1].'</div>';
				
					// Return original and duplicated div
					return $originalDiv . $duplicatedDiv;
				}, $html);
			}else if($block['attrs']['isVariation'] == 'counter'){
				wp_enqueue_script('gs-lightcounter');
			}else if($block['attrs']['isVariation'] == 'countdown'){
				wp_enqueue_script('gs-lightcountdown');
			}else if($block['attrs']['isVariation'] == 'draggable'){
				wp_enqueue_script('greenshift-drag-init');
				if(!empty($block['attrs']['enableScrollButtons'])){
					wp_enqueue_script('greenShift-scrollable-init');
				}
			}else if($block['attrs']['isVariation'] == 'dropzone'){
				wp_enqueue_script(
					'gs-dropzone',
					GREENSHIFT_DIR_URL . 'libs/api/dropzone.js',
					array(),
					'1.0',
					true
				);
				// Add nonce to gspb_api script
				wp_localize_script('gs-dropzone', 'gspbDropzoneApiSettings', array(
					'nonce' => wp_create_nonce('wp_rest'),
					'rest_url' => esc_url_raw(rest_url('greenshift/v1/proxy-api/')),
				));
			}else if($block['attrs']['isVariation'] == 'navigation'){
				wp_enqueue_script('gs-menu');
				wp_enqueue_script('gs-greenpanel');
			}else if($block['attrs']['isVariation'] == 'menu_item_link'){
				// Check if current link matches the page URL
				if(!empty($block['attrs']['href'])){
					$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
					$link_url = $block['attrs']['href'];
					
					// Remove trailing slashes for comparison
					$current_url = rtrim($current_url, '/');
					$link_url = rtrim($link_url, '/');
					
					// Check if URLs match
					if($current_url === $link_url || home_url($link_url) === $current_url){
						$p = new \WP_HTML_Tag_Processor( $html );
						$p->next_tag();
						$current_class = $p->get_attribute( 'class' );
						$new_class = $current_class ? $current_class . ' current_item' : 'current_item';
						$p->set_attribute( 'class', $new_class );
						$html = $p->get_updated_html();
					}
				}
			}else if($block['attrs']['isVariation'] == 'darkmode-switcher'){
				wp_enqueue_script('gspbcook');
				wp_enqueue_script('gs-darkmode');
			}else if($block['attrs']['isVariation'] == 'social-share-icon'){
				wp_enqueue_script(
					'gspb-social-share',
					GREENSHIFT_DIR_URL . 'libs/social-share/social.js',
					array(),
					'1.0',
					true
				);
				$p = new \WP_HTML_Tag_Processor( $html );
				$p->next_tag();
				$service = esc_attr($block['attrs']['alt']);
				$p->set_attribute( 'data-social-service', $service);
				if($service == 'facebook'){
					global $post;
					$link = get_permalink($post->ID);
					$p->set_attribute( 'data-href', 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($link));
				}else if($service == 'twitter'){
					global $post;
					$link = get_permalink($post->ID);
					$title = $post->post_title;
					$p->set_attribute( 'data-href', 'https://twitter.com/share?url=' . urlencode($link) . '&text=' . urlencode(html_entity_decode($title, ENT_COMPAT, 'UTF-8')));
				}else if($service == 'linkedin'){
					global $post;
					$link = get_permalink($post->ID);
					$title = $post->post_title;
					$p->set_attribute( 'data-href', 'https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode($link) . '&title=' . urlencode(html_entity_decode($title, ENT_COMPAT, 'UTF-8')) . '&source=' . urlencode(html_entity_decode(get_bloginfo("name"), ENT_COMPAT, 'UTF-8')));
				}else if($service == 'whatsapp'){
					global $post;
					$link = get_permalink($post->ID);
					$title = $post->post_title;
					$p->set_attribute( 'data-href', 'whatsapp://send?&text=' . urlencode(html_entity_decode($title, ENT_COMPAT, 'UTF-8')) . ' - ' . urlencode($link));
				}else if($service == 'pinterest'){
					global $post;
					$link = get_permalink($post->ID);
					$title = $post->post_title;
					$image = wp_get_attachment_url(get_post_thumbnail_id($post->ID));
					$p->set_attribute( 'data-href', 'https://pinterest.com/pin/create/button/?url=' . urlencode($link) . '&media=' . urlencode($image) . '&description=' . urlencode($title));
				}else if($service == 'telegram'){
					global $post;
					$link = get_permalink($post->ID);
					$title = $post->post_title;
					$p->set_attribute( 'data-href', 'https://t.me/share/url?url=' . urlencode($link) . '&text=' . urlencode(html_entity_decode($title, ENT_COMPAT, 'UTF-8')));
				}else if($service == 'email'){
					global $post;
					$link = get_permalink($post->ID);
					$title = $post->post_title;
					$p->set_attribute( 'data-href', 'mailto:?subject=' . rawurlencode(html_entity_decode($title, ENT_COMPAT, 'UTF-8')) . '&body=' . urlencode($link) . ' - ' . rawurlencode(html_entity_decode(get_bloginfo("name"), ENT_COMPAT, 'UTF-8')));
				}else if($service == 'bluesky'){
					global $post;
					$link = get_permalink($post->ID);
					$title = $post->post_title;
					$p->set_attribute( 'data-href', 'https://bsky.app/intent/compose?text=' . urlencode(html_entity_decode($title, ENT_COMPAT, 'UTF-8')));
				}
				
				$html = $p->get_updated_html();
			}else if($block['attrs']['isVariation'] == 'accordion' || $block['attrs']['isVariation'] == 'tabs'){

				wp_enqueue_script('gs-greensyncpanels');
	
				$p = new \WP_HTML_Tag_Processor( $html );
				$itrigger = 0;
				while ( $p->next_tag() ) {
					// Skip an element if it's not supposed to be processed.
					if ( method_exists('WP_HTML_Tag_Processor', 'has_class') && ($p->has_class( 'gs_click_sync' ) || $p->has_class( 'gs_hover_sync' )) ) {
						$p->set_attribute( 'id', 'gs-trigger-'.$block['attrs']['id'].'-'.$itrigger);
						$p->set_attribute( 'aria-controls', 'gs-content-'.$block['attrs']['id'].'-'.$itrigger);
						$itrigger ++;
					}
				}
				$html = $p->get_updated_html();
	
				$p = new \WP_HTML_Tag_Processor( $html );
				$icontent = 0;
				while ( $p->next_tag() ) {
					// Skip an element if it's not supposed to be processed.
					if ( method_exists('WP_HTML_Tag_Processor', 'has_class') && ($p->has_class( 'gs_content' )) ) {
						$p->set_attribute( 'id', 'gs-content-'.$block['attrs']['id'].'-'.$icontent);
						$p->set_attribute( 'aria-labelledby', 'gs-trigger-'.$block['attrs']['id'].'-'.$icontent);
						$icontent ++;
					}
				}
				$html = $p->get_updated_html();
			}else if($block['attrs']['isVariation'] == 'splittest'){
				$p = \WP_HTML_Processor::create_fragment( $html );
				$index_current = $p->get_current_depth();
				$index_current_tag = $index_current + 1;
				$child = $index_current + 2;
				$child_count = 0;
				$child_indices = array();
				
				// First pass: count direct child items and store their positions
				while ( $p->next_tag() ) {
					if($p->get_current_depth() == $index_current_tag){
						$p->set_bookmark('current');
					}
					if($p->get_current_depth() == $child){
						if($p->get_tag() == 'STYLE'){
							continue;
						}
						$child_indices[] = $child_count;
						$child_count++;
					}
				}
				
				// If we have child items, rotate through them on each page load
				if($child_count > 0){
					// Use a combination of block ID, current timestamp, and request count for rotation
					$block_id = !empty($block['attrs']['id']) ? $block['attrs']['id'] : 'default';
					
					// Get current timestamp (changes every second)
					$timestamp = time();
					
					// Get request count from transient (increments on each request)
					$request_count_key = 'gspb_request_count_' . $block_id;
					$request_count = get_transient($request_count_key);
					if($request_count === false) {
						$request_count = 0;
					}
					
					// Increment request count for next time
					set_transient($request_count_key, $request_count + 1, 200000); // Expires in 1 hour
					
					// Calculate rotation index
					$selected_index = ($request_count) % $child_count;
					
					// Reset to beginning and process again
					$p->seek( 'current' );
					$current_child = 0;
					
					while ( $p->next_tag() ) {
						if($p->get_current_depth() == $child){
							if($p->get_tag() == 'STYLE'){
								continue;
							}
							
							// Hide all children except the selected one
							if($current_child != $selected_index){
								$style = $p->get_attribute( 'style' );
								if(!empty($style)){
									$style .= 'display: none !important;';
								}else{
									$style = 'display: none !important;';
								}
								$p->set_attribute( 'style', $style );
							}
							
							$current_child++;
						}
					}
					
					$p->release_bookmark( 'current' );
				}
				
				$html = $p->get_updated_html();
			}else if($block['attrs']['isVariation'] == 'stylemanager'){
				if(!is_admin()){
					return '';
				}
			}
		}
		if(!empty($block['attrs']['enableTooltip'])){
			wp_enqueue_script('gs-lighttooltip');
		}
		if(!empty($block['attrs']['textAnimated'])){
			wp_enqueue_script('gs-textanimate');
		}
		if (function_exists('GSPB_make_dynamic_text')) {
			if(!empty($block['attrs']['dynamictext']['dynamicEnable'])){
				$content = !empty($block['attrs']['textContent']) ? $block['attrs']['textContent'] : '';
				$html = GSPB_make_dynamic_text($html, $block['attrs'], $block, $block['attrs']['dynamictext'], $content);
				
				if(!empty($block['attrs']['splitText']) || (!empty($block['attrs']['isVariation']) && $block['attrs']['isVariation'] == 'splittext')){
					//ensure to split also dynamic text
					$type = !empty($block['attrs']['splitTextType']) ? $block['attrs']['splitTextType'] : 'words';
					$html = greenshift_split_dynamic_text($html, $type);
				}
			}
			if(!empty($block['attrs']['dynamiclink']['dynamicEnable'])){
				if(isset($block['attrs']['tag']) && ($block['attrs']['tag'] == 'img' || $block['attrs']['tag'] == 'video' || $block['attrs']['tag'] == 'audio')){
					$src = !empty($block['attrs']['src']) ? $block['attrs']['src'] : '';
					$p = new \WP_HTML_Tag_Processor( $html );
					$p->next_tag();
					$value = GSPB_make_dynamic_text($src, $block['attrs'], $block, $block['attrs']['dynamiclink']);
					if($value){
						if($block['attrs']['tag'] == 'video' || $block['attrs']['tag'] == 'audio'){
							$p->next_tag();
						}
						if(!empty($block['attrs']['dynamiclink']['fallbackValue'])){
							$checklink = wp_check_filetype($value);
							if(empty($checklink['type'])){
								$value = esc_url($block['attrs']['dynamiclink']['fallbackValue']);
							}
						}
						if($block['attrs']['tag'] == 'video' && !empty($block['attrs']['lazyLoadVideo'])){
							$p->set_attribute( 'data-src', $value);
						} else {
							$p->set_attribute( 'src', $value);
						}
						
						if(!empty($block['attrs']['enableSrcSet']) && !empty($type['type']) && $type['type'] == 'image'){
							$id = attachment_url_to_postid($value);
							if($id && $id > 0){
								$size = 'full';
								if(!empty($block['attrs']['dynamiclink']['dynamicPostImageSize'])){
									$size = esc_attr($block['attrs']['dynamiclink']['dynamicPostImageSize']);
								}
								$srcset = wp_get_attachment_image_srcset($id, $size);
								if($srcset){
									$p->set_attribute( 'srcset', $srcset);
								}
							}
						}
						$html = $p->get_updated_html();
					}else{
						return '';
					}
				}else if(isset($block['attrs']['tag']) && $block['attrs']['tag'] == 'a'){
					$p = new \WP_HTML_Tag_Processor( $html );
					$p->next_tag();
					$href = !empty($block['attrs']['href']) ? $block['attrs']['href'] : '';
					$value = GSPB_make_dynamic_text($href, $block['attrs'], $block, $block['attrs']['dynamiclink'], $href);
					if($value){
						$linknew = apply_filters('greenshiftseo_url_filter', $value);
						$p->set_attribute( 'href', $linknew);
						$html = $p->get_updated_html();
					}else{
						return '';
					}
				}
			}
			if(!empty($block['attrs']['dynamicextra']['dynamicEnable'])){
				if($block['attrs']['tag'] == 'video'){
					$p = new \WP_HTML_Tag_Processor( $html );
					$p->next_tag();
					$value = GSPB_make_dynamic_text($block['attrs']['poster'], $block['attrs'], $block, $block['attrs']['dynamicextra']);
					if($value){
						$p->set_attribute( 'poster', $value);
						$html = $p->get_updated_html();
					}else{
						return '';
					}
				}
			}
		}
		if(!empty($block['attrs']['dynamicAttributes'])){
			$dynamicAttributes = [];
			foreach($block['attrs']['dynamicAttributes'] as $index=>$value){
				$dynamicAttributes[$index] = $value;
				if(!empty($value['dynamicEnable']) && function_exists('GSPB_make_dynamic_text')){
					$dynamicAttributes[$index]['value'] = GSPB_make_dynamic_text($dynamicAttributes[$index]['value'], $block['attrs'], $block, $value);
				}else{
					$value = sanitize_text_field($value['value']);
					$dynamicAttributes[$index]['value'] = greenshift_dynamic_placeholders($value);
					if(!empty($value['name']) && strpos($value['name'], 'on') === 0){
						$dynamicAttributes[$index]['value'] = '';
					}
				}
			}
			if(!empty($dynamicAttributes)){
				$p = new \WP_HTML_Tag_Processor( $html );
				$p->next_tag();
				foreach($dynamicAttributes as $index=>$value){
					$p->set_attribute( $value['name'], $value['value']);
				}
				$html = $p->get_updated_html();
			}
		}
		if(!empty($block['attrs']['anchor']) && strpos($block['attrs']['anchor'], '{POST_ID}') != false){
			global $post;
			$post_id = $post->ID;
			$anchor = str_replace('{POST_ID}', $post_id, $block['attrs']['anchor']);
			$p = new \WP_HTML_Tag_Processor( $html );
			$p->next_tag();
			$p->set_attribute( 'id', $anchor);
			$html = $p->get_updated_html();
		}
		if(!empty($block['attrs']['dynamicIndexer'])){
			$p = \WP_HTML_Processor::create_fragment( $html );
			$index_current = $p->get_current_depth();
			$index_current_tag = $index_current + 1;
			$child = $index_current + 2;
			$index = 0;
			while ( $p->next_tag() ) {
				if($p->get_current_depth() == $index_current_tag){
					$p->set_bookmark('current');
				}
				if($p->get_current_depth() == $child){
					if($p->get_tag() == 'STYLE'){
						continue;
					}
					$style = $p->get_attribute( 'style' );
					if(!empty($style)){
						$style .= '--index: '.$index.';';
					}else{
						$style = '--index: '.$index.';';
					}
					$p->set_attribute( 'style', $style );
					$index++;
				}
			}

			$p->seek( 'current' );
			$style = $p->get_attribute( 'style' );
			if(!empty($style)){
				$style .= '--total-items: '.$index.';';
			}else{
				$style = '--total-items: '.$index.';';
			}
			$p->set_attribute( 'style', $style );
			$p->release_bookmark( 'current' );

			$html = $p->get_updated_html();
		}
		if(!empty($block['attrs']['styleAttributes']['cssVars_Extra'])){
			$p = new \WP_HTML_Tag_Processor( $html );
			$p->next_tag();
			$style = $p->get_attribute( 'style' );
			if(!$style){
				$style = '';
			}
			foreach($block['attrs']['styleAttributes']['cssVars_Extra'] as $index=>$value){
				$style .= $value['name'].': '.greenshift_dynamic_placeholders($value['value']).';';
			}
			$p->set_attribute( 'style', $style );
			$html = $p->get_updated_html();
		}
		if(!empty($block['attrs']['chartData']) && !empty($block['attrs']['type']) && $block['attrs']['type'] == 'chart'){
			wp_enqueue_script('gschartinit');
			if(!empty($block['attrs']['chartData']['dynamic_loading']) && !empty($block['attrs']['chartData']['csv_link'])){
				$json = $cache_time = '';
				if(!empty($block['attrs']['chartData']['cache_time'])){
					$cache_time = $block['attrs']['chartData']['cache_time'];
				}
				if($cache_time){
					$transient_name = 'gspb_chart_data_'.greenshift_sanitize_id_key($block['attrs']['localId']);
					$json = get_transient($transient_name);
				}
				if(empty($json)){
					$siteurl = site_url();
					$type = !empty($block['attrs']['chartData']['chart_type']) ? $block['attrs']['chartData']['chart_type'] : 'chart1';
					$remote = wp_safe_remote_get($siteurl.'/wp-json/greenshift/v1/get-csv-to-json?type='.$type.'&url='.$block['attrs']['chartData']['csv_link']);
					if(!is_wp_error($remote)){
						$json = wp_remote_retrieve_body($remote);
						if($cache_time){
							set_transient($transient_name, $json, $cache_time);
						}
					}
				}
				if(!empty($json)){
					$p = new \WP_HTML_Tag_Processor( $html );
					$p->next_tag();
					$p->set_attribute( 'data-extra-json', $json);
					$html = $p->get_updated_html();
				}
			}
		}
		if(!empty($block['attrs']['textContent'])){
			if(strpos($block['attrs']['textContent'], '{{') !== false){
				$html = greenshift_dynamic_placeholders($html);
			}
		}
		if(!empty($block['attrs']['interactionLayers'])){
			foreach($block['attrs']['interactionLayers'] as $layer){
				if(!empty($layer['actions'])){
					foreach($layer['actions'] as $action){
						if(!empty($action['selector'])){
							$name = $action['selector'];
							
							if(strpos($name, 'ref-') !== false){
								$id = str_replace('ref-', '', $name);
								$id = (int)$id;
								$post = get_post($id);
								if($post){
									$settings = new \GSPB_GreenShift_Settings;
									$post_content = $settings->gspb_template_shortcode_function(array('id' => $id));
									$random_id = 'gspb'.wp_generate_uuid4();
									$html = str_replace($name, '#'.$random_id, $html);
									$post_content = str_replace($name, $random_id, $post_content);
									$html = $html . $post_content;
								}
							}
						}
					}
				}
			}
		}
		if(!empty($block['attrs']['type']) && ($block['attrs']['type'] == 'rive' || $block['attrs']['type'] == 'spline' || $block['attrs']['type'] == 'lottie' || $block['attrs']['type'] == 'unicorn' || $block['attrs']['type'] == 'scrollyvideo')){

			if(!empty($block['attrs']['customCanvasControllers'])){
				$p = new \WP_HTML_Tag_Processor( $html );
				$p->next_tag();
				$data = '[';
				foreach($block['attrs']['customCanvasControllers'] as $index=>$value){
					$data .= '{"name": "'.esc_attr($value['name']).'", "value": "'.esc_attr(greenshift_dynamic_placeholders($value['value'])).'"},';
				}
				$data = rtrim($data, ',');
				$data .= ']';
				$p->set_attribute( 'data-canvas-controllers', $data );
				$html = $p->get_updated_html();
			}

			if($block['attrs']['type'] == 'spline'){
				wp_enqueue_script('gspb-canvas-spline');
			}
			if($block['attrs']['type'] == 'lottie'){
				wp_enqueue_script('gspb-canvas-lottie');
			}
			if($block['attrs']['type'] == 'rive'){
				wp_enqueue_script('gspb-canvas-rive');
			}
			if($block['attrs']['type'] == 'unicorn'){
				wp_enqueue_script('gspb-canvas-unicorn');
			}
			if($block['attrs']['type'] == 'scrollyvideo'){
				wp_enqueue_script('gspb-canvas-scrollyvideo');
			}
		}

		if(!empty($block['attrs']['customJs'])){
			$global_js = get_option('gspb_block_js');
			$id = $block['attrs']['id'];
			$smart_lazy_load = !empty($block['attrs']['smartLazyLoad']) ? $block['attrs']['smartLazyLoad'] : false;

			if(!empty($global_js[$id])){
				$js = $global_js[$id];
				$js = greenshift_dynamic_placeholders($js);
				if(!empty($block['attrs']['customJsControllers'])){
					foreach($block['attrs']['customJsControllers'] as $index=>$controller){
						$js = str_replace('{{'.esc_attr($controller['name']).'}}', esc_attr(greenshift_dynamic_placeholders($controller['value'])), $js);
					}
				}
				if($smart_lazy_load){
					$random_id = 'gspbsmartjsloaded'.wp_generate_uuid4();
					$random_function = 'onGSSmartJSInteraction'.wp_generate_uuid4();
					$random_id = str_replace('-', '', $random_id);
					$random_function = str_replace('-', '', $random_function);
					add_action('wp_footer', function() use ($js, $random_id, $random_function) {
						echo '<script type="module">
							' . gsbp_script_delay($js, $random_id, $random_function) . '
						</script>';
					}, 100);
				}else{
					if(!empty($js) && strpos($js, 'import') !== false){
						add_action('wp_footer', function() use ($js) {
							echo '<script type="module">' . $js . '</script>';
						}, 100);
					} else {
						wp_enqueue_script('gspb-js-blocks');
						wp_add_inline_script('gspb-js-blocks', $js, 'after');
					}
				}
			}
		}

		return $html;
	}
}

new Element;