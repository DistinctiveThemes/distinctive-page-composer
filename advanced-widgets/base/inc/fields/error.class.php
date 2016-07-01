<?php

/**
 *
 * This class is used when a field class can't be found to display an error message to the user.
 *
 * Class DistinctiveThemes_SiteOrigin_Widget_Field_Error
 */
class DistinctiveThemes_SiteOrigin_Widget_Field_Error extends DistinctiveThemes_SiteOrigin_Widget_Field_Base {

	/**
	 * An error message to display.
	 *
	 * @access protected
	 * @var string
	 */
	protected $message;

	protected function render_field( $value, $instance ) {
		printf( __($this->message, 'dapper-pro') );
	}

	protected function sanitize_field_input( $value, $instance ) {
		return $value;
	}
}