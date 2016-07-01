<?php

/**
 * Go through all the old PB widgets and change them into far better visual editor widgets
 *
 * @param array $panels_data
 *
 * @return array
 */
function distinctivethemes_panels_legacy_widget_migration($panels_data){

	if( !empty($panels_data['widgets']) && is_array($panels_data['widgets']) ) {

		foreach( $panels_data['widgets'] as &$widget ) {

			switch($widget['panels_info']['class']) {
				case 'DistinctiveThemes_Panels_Widgets_Gallery':
					$shortcode = '[gallery ';
					if( !empty($widget['ids']) ) $shortcode .= 'ids="' . esc_attr( $widget['ids'] ) . '" ';
					$shortcode = trim($shortcode) . ']';

					$widget = array(
						'title' => '',
						'filter' => '1',
						'type' => 'visual',
						'text' => $shortcode,
						'panels_info' => $widget['panels_info']
					);
					$widget['panels_info']['class'] = 'DistinctiveThemes_Widget_Editor_Widget';

					break;

				case 'DistinctiveThemes_Panels_Widgets_Image':

					if( class_exists('DistinctiveThemes_Panels_Widgets_Image') ) {
						ob_start();
						the_widget( 'DistinctiveThemes_Panels_Widgets_Image', $widget, array(
							'before_widget' => '',
							'after_widget' => '',
							'before_title' => '',
							'after_title' => '',
						) );

						$widget = array(
							'title' => '',
							'filter' => '1',
							'type' => 'visual',
							'text' => ob_get_clean(),
							'panels_info' => $widget['panels_info']
						);

						$widget['panels_info']['class'] = 'DistinctiveThemes_Widget_Editor_Widget';
					}

					break;
			}

		}

	}

	return $panels_data;
}
add_filter('distinctivethemes_panels_data', 'distinctivethemes_panels_legacy_widget_migration');