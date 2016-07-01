<?php

/**
 * Class DistinctiveThemes_SiteOrigin_Widget_Field_Number
 */
class DistinctiveThemes_SiteOrigin_Widget_Field_Number extends DistinctiveThemes_SiteOrigin_Widget_Field_Text_Input_Base {

	protected function get_input_classes() {
		$input_classes = parent::get_input_classes();
		$input_classes[] = 'siteorigin-widget-input-number';
		return $input_classes;
	}

	protected function sanitize_field_input( $value, $instance ) {
		return ( $value === '' ) ? false : (float) $value;
	}
}