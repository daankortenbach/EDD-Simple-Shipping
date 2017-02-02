<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

class EDD_Simple_shipping_Settings {

	public function __construct() {
		add_filter( 'edd_settings_sections_extensions',    array( $this, 'settings_section' ) );
		add_filter( 'edd_settings_sections_emails',        array( $this, 'emails_section' ) );
		add_filter( 'edd_settings_extensions',             array( $this, 'settings' ), 1 );
		add_filter( 'edd_settings_emails',                 array( $this, 'emails' ) );
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

	public function emails_section( $sections ) {
		$sections['edd-simple-shipping-emails'] = __( 'Simple Shipping', 'edd-simple-shipping' );
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

	public function emails( $settings ) {
		$simple_shipping_settings = array(
			array(
				'id'   => 'edd_simple_shipping_emails_header',
				'name' => '<strong>' . __( 'Simple Shipping Emails', 'edd-simple-shipping' ) . '</strong>',
				'desc' => '',
				'type' => 'header',
				'size' => 'regular',
			),
			array(
				'id'          => 'tracking_ids_subject',
				'name'        => __( 'Tracking ID Email Subject Line', 'edd-simple-shipping' ),
				'desc'        => __( 'The subject line used when sending shipment tracking information to customers.','edd-simple-shipping' ),
				'type'        => 'text',
				'allow_blank' => false,
				'std'         => __( 'Your order has shipped!', 'edd-simple-shipping' ),
			),
			array(
				'id'          => 'tracking_ids_heading',
				'name'        => __( 'Tracking ID Email Heading', 'edd-simple-shipping' ),
				'desc'        => __( 'The heading used in the email body content when sending shipment tracking information to customers.','edd-simple-shipping' ),
				'type'        => 'text',
				'allow_blank' => false,
				'std'         => __( 'Your order has shipped!', 'edd-simple-shipping' ),
			),
			array(
				'id'          => 'tracking_ids_email',
				'name'        => __( 'Tracking ID Email', 'edd-simple-shipping' ),
				'desc'        => __( 'Enter the text that is used when sending shipment tracking information to customers. HTML is accepted. Available template tags:','edd-simple-shipping' ) . '<br/>' . edd_get_emails_tags_list(),
				'type'        => 'rich_editor',
				'allow_blank' => false,
				'std'         => __( "Dear", "edd-simple-shipping" ) . " {name},\n\n" . sprintf( __( "Your recent order %s has been shipped. Your tracking information is below.", "edd-simple-shipping" ), '{payment_id}' ) . "\n\n{tracking_ids}\n\n{sitename}",
			),
		);

		if ( version_compare( EDD_VERSION, 2.5, '>=' ) ) {
			$simple_shipping_settings = array( 'edd-simple-shipping-emails' => $simple_shipping_settings );
		}

		return array_merge( $settings, $simple_shipping_settings );
	}
}