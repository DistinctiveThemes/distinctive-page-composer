<?php

define( 'SITEORIGIN_WIDGETS_ICONS', true );

function distinctivethemes_widgets_icon_families_filter( $families ){
	$bundled = array(
		'fontawesome' => __( 'Font Awesome', 'dapper-pro' ),
	);

	foreach ( $bundled as $font => $name) {
		include_once plugin_dir_path(__FILE__) . $font . '/filter.php';
		$families[$font] = array(
			'name' => $name,
			'style_uri' => '',
			'icons' => apply_filters('distinctivethemes_widgets_icons_' . $font, array() ),
		);
	}

	return $families;
}
add_filter( 'distinctivethemes_widgets_icon_families', 'distinctivethemes_widgets_icon_families_filter' );