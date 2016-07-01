<?php

/**
 * Class DistinctiveThemes_SiteOrigin_Widget_Field_Posts
 */
class DistinctiveThemes_SiteOrigin_Widget_Field_Posts extends DistinctiveThemes_SiteOrigin_Widget_Field_Base {

	protected function render_field( $value, $instance ) {
		siteorigin_widget_post_selector_admin_form_field( is_array( $value ) ? '' : $value, $this->element_name );
	}

	protected function sanitize_field_input( $value, $instance ) {
		// Posts selector functions handle sanitization.
		return $value;
	}

}