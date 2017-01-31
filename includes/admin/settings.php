<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class EDD_Simple_shipping_Settings {

	public function __construct() {
		add_filter( 'edd_settings_sections_extensions',    array( $this, 'settings_section' ) );
		add_filter( 'edd_settings_extensions',             array( $this, 'settings' ), 1 );
	}

	/**
	 * Add Simple Shipping settings section
	 *
	 * @since 2.2.2
	 *
	 * @access public
	 * @return array
	 */
	public function settings_section( $sections ) {
		$sections['edd-simple-shipping-settings'] = __( 'Simple Shipping', 'edd-simple-shipping' );
		return $sections;
	}

	/**
	 * Add Simple Shipping settings
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return array
	 */
	public function settings( $settings ) {
		$simple_shipping_settings = array(
			array(
				'id' => 'edd_simple_shipping_license_header',
				'name' => '<strong>' . __( 'Simple Shipping', 'edd-simple-shipping' ) . '</strong>',
				'desc' => '',
				'type' => 'header',
				'size' => 'regular'
			),
			array(
				'id' => 'edd_simple_shipping_base_country',
				'name' => __( 'Base Region', 'edd-simple-shipping'),
				'desc' => __( 'Choose the country your store is based in', 'edd-simple-shipping'),
				'type'  => 'select',
				'options' => edd_get_country_list()
			)
		);

		if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
			$simple_shipping_settings = array( 'edd-simple-shipping-settings' => $simple_shipping_settings );
		}

		return array_merge( $settings, $simple_shipping_settings );
	}
}