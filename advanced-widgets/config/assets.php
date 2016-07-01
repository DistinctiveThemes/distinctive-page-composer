<?php defined( 'ABSPATH' ) OR die( 'This script cannot be accessed directly.' );

/**
 * Needed assets, used by us_enqueue_assets function.
 *
 * Dev note: the same keys for styles and scripts should stand for the same element, as they are loaded together.
 */

global $dapper_cl_uri, $dapper_cl_version;

return array(

	/**
	 * Each style entry contains params for wp_enqueue_style function:
	 * $handle => array( $src, $deps, $ver, $media )
	 */
	'styles' => array(
		'font-awesome' => array( DAPPERCOMPOSER_PLUGIN_URL , array(), '4.5.0', 'all' ),
		'cl-core' => array( DAPPERCOMPOSER_PLUGIN_URL . '/css/cl-core.css', array(), $dapper_cl_version, 'all' ),
		'cl-counter' => array( DAPPERCOMPOSER_PLUGIN_URL . '/css/cl-counter.css', array( 'cl-core' ), $dapper_cl_version, 'all' ),
		'cl-flipbox' => array( DAPPERCOMPOSER_PLUGIN_URL . '/css/cl-flipbox.css', array( 'cl-core' ), $dapper_cl_version, 'all' ),
		'cl-ib' => array( DAPPERCOMPOSER_PLUGIN_URL . '/css/cl-ib.css', array( 'cl-core' ), $dapper_cl_version, 'all' ),
		'cl-itext' => array( DAPPERCOMPOSER_PLUGIN_URL . '/css/cl-itext.css', array( 'cl-core' ), $dapper_cl_version, 'all' ),
		'cl-popup' => array( DAPPERCOMPOSER_PLUGIN_URL . '/css/cl-popup.css', array( 'cl-core' ), $dapper_cl_version, 'all' ),
		'cl-review' => array( DAPPERCOMPOSER_PLUGIN_URL . '/css/cl-review.css', array(), $dapper_cl_version, 'all' ),
	),
	/**
	 * Each script entry contains params for wp_enqueue_script function:
	 * $handle => array( $src, $deps, $ver, $in_footer )
	 */
	'scripts' => array(
		'cl-core' => array( DAPPERCOMPOSER_PLUGIN_URL . '/js/cl-core.js', array( 'jquery' ), $dapper_cl_version, TRUE ),
		'cl-counter' => array( DAPPERCOMPOSER_PLUGIN_URL . '/js/cl-counter.js', array( 'cl-core' ), $dapper_cl_version, TRUE ),
		'cl-flipbox' => array( DAPPERCOMPOSER_PLUGIN_URL . '/js/cl-flipbox.js', array( 'cl-core' ), $dapper_cl_version, TRUE ),
		'cl-ib' => array( DAPPERCOMPOSER_PLUGIN_URL . '/js/cl-ib.js', array( 'cl-core' ), $dapper_cl_version, TRUE ),
		'cl-itext' => array( DAPPERCOMPOSER_PLUGIN_URL . '/js/cl-itext.js', array( 'cl-core' ), $dapper_cl_version, TRUE ),
		'cl-popup' => array( DAPPERCOMPOSER_PLUGIN_URL . '/js/cl-popup.js', array( 'cl-core' ), $dapper_cl_version, TRUE ),
	),

);
