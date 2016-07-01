<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('DistinctiveThemes_DistinctiveThemes_LSOW_Setup')):

    class DistinctiveThemes_DistinctiveThemes_LSOW_Setup {

        public function __construct() {

            add_filter('distinctivethemes_widgets_widget_folders', array($this, 'add_widgets_collection'));
            add_filter('distinctivethemes_widgets_field_class_prefixes', array($this, 'custom_fields_class_prefixes'));
            add_filter('distinctivethemes_widgets_field_class_paths', array($this, 'custom_fields_class_paths'));

            add_filter('distinctivethemes_panels_widget_dialog_tabs', array($this, 'add_widget_tabs'), 20);

            add_filter('distinctivethemes_panels_widgets', array($this, 'add_bundle_groups'), 11);


            add_filter('distinctivethemes_panels_row_style_fields', array($this, 'row_style_fields'));


            add_filter('distinctivethemes_panels_row_style_attributes', array($this, 'row_style_attributes'), 10, 2);

            // Main filter to add any custom CSS.
            add_filter('distinctivethemes_panels_css_object', array($this, 'filter_css_object'), 10, 3);


        }

        function row_style_fields($fields) {

            $fields['top_padding'] = array(
                'name' => __('Top Padding', 'dapper-pro'),
                'type' => 'measurement',
                'group' => 'layout',
                'description' => __('Top Padding for the row.', 'dapper-pro'),
                'priority' => 21,
                'multiple' => true
            );

            $fields['bottom_padding'] = array(
                'name' => __('Bottom Padding', 'dapper-pro'),
                'type' => 'measurement',
                'group' => 'layout',
                'description' => __('Bottom Padding for the row.', 'dapper-pro'),
                'priority' => 22,
                'multiple' => true
            );

            $fields['tablet_top_padding'] = array(
                'name' => __('Top Padding in Tablet resolution', 'dapper-pro'),
                'type' => 'measurement',
                'group' => 'layout',
                'description' => __('Top Padding for the row in tablet resolutions.', 'dapper-pro'),
                'priority' => 23,
                'multiple' => true
            );

            $fields['tablet_bottom_padding'] = array(
                'name' => __('Bottom Padding in Tablet resolution', 'dapper-pro'),
                'type' => 'measurement',
                'group' => 'layout',
                'description' => __('Bottom Padding for the row in tablet resolutions.', 'dapper-pro'),
                'priority' => 24,
                'multiple' => true
            );

            $fields['mobile_top_padding'] = array(
                'name' => __('Top Padding in Mobile resolution', 'dapper-pro'),
                'type' => 'measurement',
                'group' => 'layout',
                'description' => __('Top Padding for the row in mobile resolutions.', 'dapper-pro'),
                'priority' => 25,
                'multiple' => true
            );

            $fields['mobile_bottom_padding'] = array(
                'name' => __('Bottom Padding in Mobile resolution', 'dapper-pro'),
                'type' => 'measurement',
                'group' => 'layout',
                'description' => __('Bottom Padding for the row in mobile resolutions.', 'dapper-pro'),
                'priority' => 26,
                'multiple' => true
            );

            /* Add design fields */

            $fields['dapper_dark_bg'] = array(
                'name' => __('Dark Background?', 'dapper-pro'),
                'type' => 'checkbox',
                'group' => 'design',
                'label' => __('Indicate if this row has a dark background color. Dark color scheme will be applied for all widgets in this row.', 'dapper-pro'),
                'default' => false,
                'priority' => 4,
            );


            return $fields;
        }

        function row_style_attributes($attributes, $args) {

            if (!empty($args['dapper_dark_bg'])) {
                if (empty($attributes['class']))
                    $attributes['class'] = array();

                $attributes['class'][] = 'dapper-dark-bg';
            }

            if (!empty($args['top_padding']) || !empty($args['bottom_padding']) || !empty($args['tablet_top_padding']) || !empty($args['tablet_bottom_padding']) || !empty($args['mobile_top_padding']) || !empty($args['mobile_bottom_padding'])) {
                if (empty($attributes['class']))
                    $attributes['class'] = array();

                $attributes['class'][] = 'dapper-row'; // force creation of a row wrapper so that the styles can be applied.
            }

            return $attributes;
        }

        function filter_css_object($css, $panels_data, $post_id) {

            foreach ($panels_data['grids'] as $gi => $grid) {

                $grid_id = !empty($grid['style']['id']) ? (string)sanitize_html_class($grid['style']['id']) : intval($gi);

                $top_padding = (isset($grid['style']['top_padding']) ? $grid['style']['top_padding'] : null);
                $bottom_padding = (isset($grid['style']['bottom_padding']) ? $grid['style']['bottom_padding'] : null);;

                // Filter the bottom margin for this row with the arguments
                if ($top_padding)
                    $css->add_row_css($post_id, $grid_id, '.dapper-row', array('padding-top' => $top_padding), 1920);
                if ($bottom_padding)
                    $css->add_row_css($post_id, $grid_id, '.dapper-row', array('padding-bottom' => $bottom_padding), 1920);

                $top_padding = (isset($grid['style']['tablet_top_padding']) ? $grid['style']['tablet_top_padding'] : null);
                $bottom_padding = (isset($grid['style']['tablet_bottom_padding']) ? $grid['style']['tablet_bottom_padding'] : null);;

                // Filter the bottom margin for this row with the arguments
                if ($top_padding)
                    $css->add_row_css($post_id, $grid_id, '.dapper-row', array('padding-top' => $top_padding), 960);
                if ($bottom_padding)
                    $css->add_row_css($post_id, $grid_id, '.dapper-row', array('padding-bottom' => $bottom_padding), 960);


                $top_padding = (isset($grid['style']['mobile_top_padding']) ? $grid['style']['mobile_top_padding'] : null);
                $bottom_padding = (isset($grid['style']['mobile_bottom_padding']) ? $grid['style']['mobile_bottom_padding'] : null);;

                // Filter the bottom margin for this row with the arguments
                if ($top_padding)
                    $css->add_row_css($post_id, $grid_id, '.dapper-row', array('padding-top' => $top_padding), 478);
                if ($bottom_padding)
                    $css->add_row_css($post_id, $grid_id, '.dapper-row', array('padding-bottom' => $bottom_padding), 478);


            }
            return $css;
        }

        function add_widgets_collection($folders) {
            $folders[] = dapper . 'includes/widgets/';
            return $folders;
        }


        // Placing all widgets under the 'SiteOrigin Widgets' Tab
        function add_widget_tabs($tabs) {
            $tabs[] = array(
                'title' => __('DistinctiveThemes :SiteOrigin Widgets', 'dapper-pro'),
                'filter' => array(
                    'groups' => array('dapper-widgets')
                )
            );
            return $tabs;
        }


        // Adding group for all Widgets
        function add_bundle_groups($widgets) {
            foreach ($widgets as $class => &$widget) {
                if (preg_match('/DistinctiveThemes_LSOW_(.*)_Widget/', $class, $matches)) {
                    $widget['groups'] = array('dapper-widgets');
                }
            }
            return $widgets;
        }


        function custom_fields_class_prefixes($class_prefixes) {
            $class_prefixes[] = 'DistinctiveThemes_LSOW_Custom_Field_';
            return $class_prefixes;
        }

        function custom_fields_class_paths($class_paths) {
            $class_paths[] = dapper . 'includes/fields/';
            return $class_paths;
        }

    }

endif;

new DistinctiveThemes_DistinctiveThemes_LSOW_Setup();
