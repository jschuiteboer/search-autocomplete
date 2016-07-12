<?php
/**
 * Plugin Name: ACS WooCommerce Autocomplete Search
 * Author: ACS Leeuwarden
 * Author URI: http://acservices.nl/
 * Plugin URI: http://acservices.nl/
 * Version: 4.3.0
 * Description: WooCommerce Autocomplete Search
 */

add_action('pre_get_product_search_form', 'acs_wcs_pre_search_form');
function acs_wcs_pre_search_form() {
	wp_enqueue_script('jquery-ui-autocomplete');
	
	?>
	<script>
		jQuery(document).ready(function($) {
			var inputField = $(".woocommerce-product-search input[type=search]");
			
			inputField.autocomplete({
				source: '<?php echo admin_url('admin-ajax.php?action=acs_wc_search'); ?>',
				delay: 500,
				minLength: 3,
			});
			inputField.autocomplete("instance")._renderItem = function(ul, item) {
				return $('<li>')
					.append('<img src="' + item.thumb + '">')
					.append('<a href="' + item.url + '">' + item.label + '<a>')
					.appendTo(ul);
			};
			inputField.on('focus', function() {
				$(this).autocomplete("widget").show();
			});
		});
	</script>
	<?php
}

add_action(       'wp_ajax_acs_wc_search', 'acs_wcs_do_ajax');
add_action('wp_ajax_nopriv_acs_wc_search', 'acs_wcs_do_ajax');
function acs_wcs_do_ajax() {
	if(!isset($_GET['term'])) return;
	
    $ordering_args = wc()->query->get_catalog_ordering_args( 'title', 'asc' );
	
	$query = strtolower($_GET['term']);
	$loop = new WP_Query(array(
		's' => $query,
		'post_type' => 'product',
		'orderby' => $ordering_args['orderby'],
		'order' => $ordering_args['order']
	));
	
	$responce = array();	
	while($loop->have_posts()) {
		$loop->the_post();
		
		$responce[] = array(
			'label' => get_the_title(),
			'url' => get_permalink(),
			'thumb' => acs_wcs_get_product_thumbnail(),
		);
	}
	
	wp_reset_query();
	
	get_posts();
	
	echo json_encode($responce);
	die();
}

function acs_wcs_get_product_thumbnail() {
	$thumbnail_id = get_post_thumbnail_id();
	
	if($thumbnail_id) {
		$thumbnail = wp_get_attachment_image_src($thumbnail_id, array(30, 30));
				
		if(is_array($thumbnail) && isset($thumbnail[0])) {
			return $thumbnail[0];
		}
	}
	
	return wc_placeholder_img_src();
}