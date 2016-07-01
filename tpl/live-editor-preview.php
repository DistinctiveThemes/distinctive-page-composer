<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
wp_enqueue_style( 'distinctivethemes-preview-style', plugin_dir_url( __FILE__ ) . '../css/live-editor-preview.css', array(), DISTINCTIVETHEMES_PANELS_VERSION );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
	<div id="content" class="site-content">
		<div class="entry-content">
			<?php
			if( !empty( $_POST['live_editor_panels_data'] ) ) {
				$data = json_decode( wp_unslash( $_POST['live_editor_panels_data'] ), true );
				if(
					!empty( $data['widgets'] ) && (
						!class_exists( 'DistinctiveThemes_Widget_Field_Class_Loader' ) ||
						method_exists( 'DistinctiveThemes_Widget_Field_Class_Loader', 'extend' )
					)
				) {
					$data['widgets'] = distinctivethemes_panels_process_raw_widgets( $data['widgets'] );
				}
				echo distinctivethemes_panels_render( 'l' . md5( serialize( $data ) ), true, $data);
			}
			?>
		</div><!-- .entry-content -->
	</div>
	<?php wp_footer(); ?>
</body>
</html>
