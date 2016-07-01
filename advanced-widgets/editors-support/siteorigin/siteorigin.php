<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

if ( ! defined( 'SITEORIGIN_PANELS_VERSION' ) ) {
	return;
}

add_filter( 'distinctivethemes_panels_widgets', 'dapper_cl_distinctivethemes_panels_widgets' );
function dapper_cl_distinctivethemes_panels_widgets( $widgets ) {
	$config = dapper_cl_config( 'elements', array() );
	foreach ( $config as $name => $elm ) {
		if ( ! isset( $elm['widget_php_class'] ) OR empty( $elm['widget_php_class'] ) ) {
			$elm['widget_php_class'] = 'CL_Widget_' . ucfirst( preg_replace( '~^cl\-~', '', $name ) );
		}
		if ( empty( $widgets[ $elm['widget_php_class'] ] ) ) {
			continue;
		}
		$widgets[ $elm['widget_php_class'] ]['groups'] = array( 'dapper-pro' );
		$widgets[ $elm['widget_php_class'] ]['icon'] = 'icon-' . $name;
	}

	return $widgets;
}

function dapper_cl_siteorigin_icons_style() {
	echo '<style type="text/css" id="dapper_cl_siteorigin_icons_style">';
	foreach ( dapper_cl_config( 'elements', array() ) as $name => $elm ) {
		if ( isset( $elm['icon'] ) AND ! empty( $elm['icon'] ) ) {
			echo '.dapper-so-panels-dialog .widget-icon.icon-' . $name . ' {';
			echo '-webkit-background-size: 20px 20px;';
			echo 'background-size: 20px 20px;';
			echo 'background-image: url(' . $elm['icon'] . ');';
			echo '}';
		}
	}
	echo '}';
	echo '</style>';
}

add_filter( 'distinctivethemes_panels_widget_dialog_tabs', 'dapper_cl_distinctivethemes_panels_widget_dialog_tabs', 20 );
function dapper_cl_distinctivethemes_panels_widget_dialog_tabs( $tabs ) {
	$tabs[] = array(
		'title' => 'DistinctiveThemes',
		'filter' => array(
			'groups' => array( 'dapper-pro' ),
		),
	);

	return $tabs;
}

add_action( 'admin_enqueue_scripts', 'dapper_cl_admin_enqueue_siteorigin_scripts' );
function dapper_cl_admin_enqueue_siteorigin_scripts() {
	global $post_type, $dapper_cl_uri, $dapper_cl_version, $wp_styles;
	if ( function_exists( 'distinctivethemes_panels_setting' ) ) {
		$siteorigin_post_types = distinctivethemes_panels_setting( 'post-types' );
		if ( is_array( $siteorigin_post_types ) AND in_array( $post_type, $siteorigin_post_types ) ) {
			dapper_cl_enqueue_forms_assets();
			wp_enqueue_script( 'cl-siteorigin', $dapper_cl_uri . '/editors-support/siteorigin/siteorigin.js', array( 'jquery' ), $dapper_cl_version, TRUE );
			// Icons
			add_action( 'admin_head', 'dapper_cl_siteorigin_icons_style' );
		}
	}
}
