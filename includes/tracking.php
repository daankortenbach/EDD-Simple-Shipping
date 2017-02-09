<?php

class EDD_Simple_Shipping_Tracking {

	public function __construct() {
		add_action( 'edd_view_order_details_billing_after', array( $this, 'payment_tracking' ) );
		add_action( 'edd_updated_edited_purchase',          array( $this, 'save_edited_payment' ), 10, 1 );
		add_action( 'edd_add_email_tags',                   array( $this, 'add_email_tag' ), 100 );
		add_action( 'edd_send-tracking',                    array( $this, 'send_tracking' ), 10, 1 );
		add_action( 'edd_purchase_history_header_after',    array( $this, 'order_details_header' ), 10, 1 );
		add_action( 'edd_purchase_history_row_end',         array( $this, 'order_details_row' ), 10, 2 );
	}

	public function payment_tracking( $payment_id ) {
		$needs_shipping = edd_simple_shipping()->payment_needs_shipping( $payment_id );

		if( ! $needs_shipping ) {
			return;
		}

		$tracking_ids = $this->get_payment_tracking( $payment_id );
		$was_sent     = $this->payment_tracking_last_sent( $payment_id );
		?>
		<div id="edd-payment-tracking" class="postbox">
			<h3 class="hndle"><span><?php _e( 'Tracking Info', 'edd-simple-shipping' ); ?></span></h3>
			<div class="inside">
				<div id="edd-tracking-fields" class="edd_meta_table_wrap">
					<table class="widefat edd_repeatable_table" width="100%" cellpadding="0" cellspacing="0">
						<thead>
							<tr>
								<th><?php _e( 'Parcel Name', 'edd-simple-shipping' ); ?></th>
								<th><?php _e( 'Tracking ID', 'edd-simple-shipping' ); ?></th>
								<th><?php _e( 'View Tracking', 'edd-simple-shipping' ); ?></th>
								<th style="width: 2%"></th>
							</tr>
						</thead>
						<tbody>
							<?php
								if ( ! empty( $tracking_ids ) ) :

									foreach ( $tracking_ids as $key => $args ) :
										?>
										<tr class="edd_tracking_ids_wrapper edd_repeatable_row" data-key="<?php echo esc_attr( $key ); ?>">
											<?php $this->tracking_input_field( $key, $args ); ?>
										</tr>
									<?php
									endforeach;
								else :
							?>
								<tr class="edd_tracking_ids_wrapper edd_repeatable_row" data-key="1">
									<?php $this->tracking_input_field( 0 ); ?>
								</tr>
							<?php endif; ?>

							<tr>
								<td class="submit" colspan="4" style="float: none; clear:both; background:#fff;">
									<button class="button-secondary edd_add_repeatable" style="margin: 6px 0;"><?php _e( 'Add New Tracking ID', 'edd-simple-shipping' ); ?></button>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<?php if ( ! empty( $tracking_ids ) ) : ?>
				<p>
					<?php wp_nonce_field( 'edd-ti-send-tracking', 'edd-ti-send-tracking', false, true ); ?>
					<?php $notify_button_text = empty( $was_sent ) ? __( 'Send Tracking Info', 'edd-simple-shipping' ) : __( 'Resend Tracking Info', 'edd-simple-shipping' ); ?>
					<span class="button-secondary" id="edd-tracking-info-notify-customer" data-payment="<?php echo $payment_id; ?>"><?php echo $notify_button_text; ?></span>
					<span class="edd-tracking-info-email-message"></span>
					<span class="spinner"></span>
				</p>
				<?php endif; ?>
				<div class="clear"></div>
			</div><!-- /.inside -->
		</div><!-- /#edd-payment-notes -->
		<?php
	}

	private function tracking_input_field( $key, $args = array() ) {
		$defaults = array( 'name' => sprintf( __( 'Parcel %s', 'edd-simple-shipping' ), $key + 1 ), 'tracking_id' => '' );
		$args = wp_parse_args( $args, $defaults );
		?>
		<td>
			<?php echo EDD()->html->text( array(
				'name'  => 'edd_tracking_ids[' . $key . '][name]',
				'value' => esc_attr( $args['name'] ),
				'placeholder' => __( 'Package Name', 'edd-simple-shipping' ),
				'class' => 'edd-simple-shipping-name-input large-text'
			) ); ?>
		</td>

		<td>
			<?php echo EDD()->html->text( array(
				'name'  => 'edd_tracking_ids[' . $key . '][tracking_id]',
				'value' => esc_attr( $args['tracking_id'] ),
				'placeholder' => __( 'Tracking ID', 'edd-simple-shipping' ),
				'class' => 'edd-simple-shipping-tracking-input large-text'
			) ); ?>
		</td>

		<td>
			<?php if ( ! empty( $args['tracking_id'] ) ) : ?>
				<a href="<?php echo $this->get_tracking_link( $args['tracking_id'] ); ?>" target="_blank"><?php _e( 'Track Parcel', 'edd-simple-shipping' ); ?></a>
			<?php endif; ?>
		</td>

		<td>
			<button class="edd_remove_repeatable" data-type="price" style="background: url(<?php echo admin_url('/images/xit.gif'); ?>) no-repeat;"><span class="screen-reader-text"><?php printf( __( 'Remove tracking ID %s', 'edd-simple-shipping' ), $value ); ?></span><span aria-hidden="true">&times;</span></button>
		</td>
		<?php
	}

	public function save_edited_payment( $payment_id ) {
		$tracking_ids = $_POST['edd_tracking_ids'];

		foreach ( $tracking_ids as $key => $tracking_id ) {
			if ( empty( $tracking_id['tracking_id'] ) ) {
				unset( $tracking_ids[ $key ] );
			}
		}

		if ( empty( $tracking_ids ) ) {
			delete_post_meta( $payment_id, '_edd_payment_tracking_ids' );
		} else {
			edd_update_payment_meta( $payment_id, '_edd_payment_tracking_ids', $tracking_ids );
		}
	}

	public function add_email_tag() {
		edd_add_email_tag( 'tracking_ids', __( 'Show saved tracking ids for payment.', 'edd-simple-shipping' ), array( $this, 'output_tracking_ids_tag' ) );
	}

	public function output_tracking_ids_tag( $payment_id = 0 ) {

		// Start a buffer so we don't output any errors into the email.
		ob_start();
		$output = '';
		$tracking_ids = $this->get_payment_tracking( $payment_id );

		if ( $tracking_ids ) {
			$output = '<ul>';
			foreach ( $tracking_ids as $key => $tracking_info ) {
				$output .= '<li>' . $tracking_info['name'] . '&mdash;<a href="' . $this->get_tracking_link( $tracking_info['tracking_id'] ) . '">' . $tracking_info['tracking_id'] . '</a></li>';
			}
			$output .= '</ul>';
		}
		ob_end_clean();
		return $output;

	}

	public function filter_template_tags( $message, $payment_id ) {
		$tracking_ids = $this->output_tracking_ids_tag( $payment_id );
		$message      = str_replace( '{tracking_ids}', $tracking_ids, $message );

		return $message;
	}

	public function send_tracking( $post ) {
		$nonce = ! empty( $post['nonce'] ) ? $post['nonce'] : false;
		if ( ! wp_verify_nonce( $nonce, 'edd-ti-send-tracking' ) ) { wp_die(); }

		$has_tracking = $this->payment_has_tracking( $post['payment_id'] );
		if ( false === $has_tracking ) {
			return;
		}

		$from_name    = edd_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
		$from_email   = edd_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
		$to_email     = edd_get_payment_user_email( $post['payment_id'] );

		$subject      = edd_get_option( 'tracking_ids_subject', __( 'Your order has shipped!', 'edd-simple-shipping' ) );
		$heading      = edd_get_option( 'tracking_ids_heading', __( 'Your order has shipped!', 'edd-simple-shipping' ) );
		$message      = edd_get_option( 'tracking_ids_email', '' );

		if ( empty( $message ) ) {
			$message = $this->get_default_tracking_email_message();
		}

		$message = EDD()->email_tags->do_tags( $message, $post['payment_id'] );

		$headers  = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
		$headers .= "Reply-To: ". $from_email . "\r\n";
		$headers .= "MIME-Version: 1.0\r\n";
		$headers .= "Content-Type: text/html; charset=utf-8\r\n";

		$attachments = array();

		$emails = EDD()->emails;

		$emails->__set( 'from_name', $from_name );
		$emails->__set( 'from_email', $from_email );
		$emails->__set( 'heading', $heading );
		$emails->__set( 'headers', $headers );

		$result = $emails->send( $to_email, $subject, $message, $attachments );

		$response = array( 'success' => $result );
		$response['message'] = $result ? __( 'Email sent.', 'edd-simple-shipping' ) : __( 'Error sending email. Try again later.', 'edd-simple-shipping' );

		if ( $result ) {
			add_post_meta( $post['payment_id'], '_edd_payment_tracking_sent', current_time( 'timestamp' ) );
			edd_insert_payment_note( $post['payment_id'], sprintf( __( 'Tracking information sent to %s.', 'edd-simple-shipping' ), $to_email ) );
		}

		echo json_encode( $response );
		die();
	}

	public function payment_has_tracking( $payment_id = 0 ) {
		$payment = new EDD_Payment( $payment_id );

		if ( empty( $payment->ID ) ) {
			return false;
		}

		$has_tracking = $payment->get_meta( '_edd_payment_tracking_ids' );

		return ! empty( $has_tracking ) ? true : false;
	}

	public function get_payment_tracking( $payment_id = 0 ) {
		$payment = new EDD_Payment( $payment_id );

		if ( empty( $payment->ID ) ) {
			return false;
		}

		$has_tracking = $payment->get_meta( '_edd_payment_tracking_ids' );

		return ! empty( $has_tracking ) ? $has_tracking : false;
	}

	public function payment_tracking_last_sent( $payment_id = 0 ) {
		$payment = new EDD_Payment( $payment_id );
		$tracking_sent = $payment->get_meta( '_edd_payment_tracking_sent' );
		if ( empty( $tracking_sent ) ) {
			return false;
		}

		if ( is_array( $tracking_sent ) ) {
			$tracking_sent = array_shift( arsort( $tracking_sent ) );
		}

		return $tracking_sent;
	}

	public function output_tracking_links( $payment_id ) {
		$tracking = $this->get_payment_tracking( $payment_id );

		if ( ! empty( $tracking ) ) {
			?>
			<ul class="edd-shipping-tracking-ids">
			<?php
			foreach( $tracking as $tracking_id ) {
				?>
				<li>
					<a href="<?php echo $this->get_tracking_link( $tracking_id['tracking_id'] ); ?>" target="_blank"><?php echo $tracking_id['name']; ?></a>
				</li>
				<?php
			}
			?>
			</ul>
			<?php
		}
	}

	public function get_tracking_link( $tracking_id ) {
		return apply_filters( 'edd_simple_shipping_tracking_link', 'https://track.aftership.com/' . $tracking_id, $tracking_id);
	}

	public function order_details_header() {
		?>
		<th class="edd_purchase_tracking"><?php _e( 'Tracking', 'edd-tracking-info' ); ?></th>
		<?
	}

	public function order_details_row( $payment_id, $purchase_data ) {
		$tracking_ids = $this->get_payment_tracking( $payment_id );
		?>
		<td class="edd-order-details-tracking-ids">
			<?php if ( $tracking_ids ) : ?>
				<ul>
				<?php foreach ( $tracking_ids as $tracking_id ) : ?>
					<li><a href="<?php echo $this->get_tracking_link( $tracking_id['tracking_id'] ); ?>" target="_blank"><?php echo $tracking_id['tracking_id']; ?></a></li>
				<?php endforeach; ?>
				</ul>
			<?php else : ?>
				&mdash;
			<?php endif; ?>
		</td>
		<?php
	}

	public function get_default_tracking_email_message() {
		return __( "Dear {name},\n\nYour recent order {payment_id} has been shipped. Your tracking information is below.\n\n{tracking_ids}\n\n{sitename}", "edd-simple-shipping" );
	}

}