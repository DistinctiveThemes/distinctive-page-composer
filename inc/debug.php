<?php

/**
 * If we're in debug mode, display the panels data.
 */
function distinctivethemes_panels_dump(){
	echo "<!--\n\n";
	echo "// Page Builder Data\n\n";

	if(isset($_GET['page']) && $_GET['page'] == 'so_panels_home_page') {
		var_export( get_option( 'distinctivethemes_panels_home_page', null ) );
	}
	else{
		global $post;
		var_export( get_post_meta($post->ID, 'panels_data', true));
	}
	echo "\n\n-->";
}
add_action('distinctivethemes_panels_metabox_end', 'distinctivethemes_panels_dump');