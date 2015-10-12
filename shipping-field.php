<?php
class FES_Simple_Shipping_Field extends FES_Field {

	/** @var bool For 3rd parameter of get_post/user_meta */
	public $single = true;

	/** @var array Supports are things that are the same for all fields of a field type. Like whether or not a field type supports jQuery Phoenix. Stored in obj, not db. */
	public $supports = array(
		'multiple'    => true,
		'forms'       => array(
			'registration'     => false,
			'submission'       => true,
			'vendor-contact'   => false,
			'profile'          => false,
			'login'            => false,
		),
		'position'    => 'extension',
		'permissions' => array(
			'can_remove_from_formbuilder' => true,
			'can_change_meta_key'         => false,
			'can_add_to_formbuilder'      => true,
		),
		'template'	  => 'edd_simple_shipping',
		'title'       => 'Shipping', // l10n on output
		'phoenix'	   => true,
	);

	/** @var array Characteristics are things that can change from field to field of the same field type. Like the placeholder between two text fields. Stored in db. */
	public $characteristics = array(
		'name'        => 'edd_simple_shipping',
		'template'	  => 'edd_simple_shipping',
		'is_meta'     => true,  // in object as public (bool) $meta;
		'public'      => false,
		'required'    => true,
		'label'       => 'Shipping',
		'css'         => '',
		'default'     => '',
		'size'        => '',
		'help'        => '',
		'placeholder' => '',
	);

	public function extending_constructor( ){
		// exclude from submission form in admin
		add_filter( 'fes_templates_to_exclude_render_submission_form_admin', array( $this, 'exclude_from_admin' ), 10, 1  );
		add_filter( 'fes_templates_to_exclude_validate_submission_form_admin', array( $this, 'exclude_from_admin' ), 10, 1  );
		add_filter( 'fes_templates_to_exclude_save_submission_form_admin', array( $this, 'exclude_from_admin' ), 10, 1  );
	}

	public function set_title() {
		$title = _x( 'Shipping', 'FES Field title translation', 'edd_fes' );
		$title = apply_filters( 'fes_' . $this->name() . '_field_title', $title );
		$this->supports['title'] = $title;		
	}
	
	public function exclude_from_admin( $fields ){
		array_push( $fields, 'edd_simple_shipping' );
		return $fields;
	}

	/** Don't register in admin */
	public function render_field_admin( $user_id = -2, $readonly = -2 ) {
		return '';
	}

	/** Returns the HTML to render a field in frontend */
	public function render_field_frontend( $user_id = -2, $readonly = -2 ) {
		if ( $user_id === -2 ) {
			$user_id = get_current_user_id();
		}

		if ( $readonly === -2 ) {
			$readonly = $this->readonly;
		}

		$user_id   = apply_filters( 'fes_render_edd_simple_shipping_field_user_id_frontend', $user_id, $this->id );
		$readonly  = apply_filters( 'fes_render_edd_simple_shipping_field_readonly_frontend', $readonly, $user_id, $this->id );
		$value     = $this->get_field_value_frontend( $this->save_id, $user_id, $readonly );
		$required  = $this->required( $readonly );
        
        $el_name       = $this->name();
        $class_name    = $this->css();
        
        $output        = '';
        $output        .= sprintf( '<fieldset class="fes-el %s%s">', $el_name, $class_name );
        $output    	   .= $this->label( $readonly );
		$enabled       = get_post_meta( $this->save_id, '_edd_enable_shipping', true );
		$domestic      = get_post_meta( $this->save_id, '_edd_shipping_domestic', true );
		$international = get_post_meta( $this->save_id, '_edd_shipping_international', true );
        ob_start(); ?>
		<style>
		div.fes-form fieldset .fes-fields.edd_simple_shipping label { width: 100%; display:block; }
		div.fes-form fieldset .fes-fields.edd_simple_shipping .edd-fes-shipping-fields label { width: 45%; display:inline-block; }
		div.fes-form fieldset .fes-fields .edd-shipping-field { width: 45%; display:inline-block; }
		</style>
		<div class="fes-fields <?php echo sanitize_key( $this->name()); ?>">
			<label for="edd_simple_shipping[enabled]">
				<input type="checkbox" name="edd_simple_shipping[enabled]" id="edd_simple_shipping[enabled]" value="1"<?php checked( '1', $enabled ); ?>/>
				<?php _e( 'Enable Shipping', 'edd-simple-shipping' ); ?>
			</label>
			<div class="edd-fes-shipping-fields">
				<label for="edd_simple_shipping[domestic]"><?php _e( 'Domestic', 'edd-simple-shipping' ); ?></label>
				<label for="edd_simple_shipping[international]"><?php _e( 'International', 'edd-simple-shipping' ); ?></label>
				<input class="edd-shipping-field textfield<?php echo esc_attr( $required ); ?>" id="edd_simple_shipping[domestic]" type="text" data-required="<?php echo $required ?>" data-type="text" name="<?php echo esc_attr( $this->name() ); ?>[domestic]" placeholder="<?php echo __( 'Enter the domestic shipping charge amount', 'edd-simple-shipping' ); ?>" value="<?php echo esc_attr( $domestic ) ?>" size="10" <?php $this->required_html5( $readonly ); ?> <?php echo $readonly ? 'disabled' : ''; ?> />
				<input class="edd-shipping-field textfield<?php echo esc_attr( $required ); ?>" id="edd_simple_shipping[international]" type="text" data-required="<?php echo $required ?>" data-type="text" name="<?php echo esc_attr( $this->name() ); ?>[international]" placeholder="<?php echo __( 'Enter the international shipping charge amount', 'edd-simple-shipping' ); ?>" value="<?php echo esc_attr( $international ) ?>" size="10" <?php $this->required_html5( $readonly ); ?> <?php echo $readonly ? 'disabled' : ''; ?> />
			</div>
		</div> <!-- .fes-fields -->
        <?php
		$output .= ob_get_clean();
		$output .= '</fieldset>';
		return $output;
	}

	/** Returns the HTML to render a field for the formbuilder */
	public function render_formbuilder_field( $index ) {
		$removable = $this->can_remove_from_formbuilder();
		ob_start(); ?>
        <li class="edd_simple_shipping">
            <?php $this->legend( $this->title(), $this->get_label(), $removable ); ?>
            <?php FES_Formbuilder_Templates::hidden_field( "[$index][template]", $this->template() ); ?>

			<?php FES_Formbuilder_Templates::field_div( $index, $this->name(), $this->characteristics, $insert ); ?>
				<?php FES_Formbuilder_Templates::public_radio( $index, $this->characteristics, $this->form_name ); ?>
                <?php FES_Formbuilder_Templates::standard( $index, $this ); ?>
            </div>
        </li>
        <?php
		return ob_get_clean();
	}

	public function validate( $values = array(), $save_id = -2, $user_id = -2 ) {
        $name = $this->name();
		if ( !empty( $values[ $name ] ) ){
			// if the value is set
				// no specific validation
		} else { 
			// if the field is required but isn't present
			if ( $this->required() ){
				return __( 'Please fill out this field.', 'edd_fes' );
			}
		}
        return apply_filters( 'fes_validate_' . $this->template() . '_field', false, $values, $name, $save_id, $user_id );
	}
	
	public function sanitize( $values = array(), $save_id = -2, $user_id = -2 ){
        $name = $this->name();
		if ( !empty( $values[ $name ] ) ){
			$values[ $name ] = trim( $values[ $name ] );
			$values[ $name ] = sanitize_text_field( $values[ $name ] );
		}
		return apply_filters( 'fes_sanitize_' . $this->template() . '_field', $values, $name, $save_id, $user_id );
	}
}
