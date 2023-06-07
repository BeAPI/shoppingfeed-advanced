<?php

namespace ShoppingFeed\ShoppingFeedWCAdvanced\Admin;

use ShoppingFeed\ShoppingFeedWCAdvanced\ShoppingFeedAdvancedHelper;

// Exit on direct access
defined( 'ABSPATH' ) || exit;

class Fields {

	/** @var array */
	private $fields;

	public function __construct() {

		add_action(
			'woocommerce_product_options_inventory_product_data',
			array(
				$this,
				'fields_product_inventory_tab',
			)
		);
		add_action(
			'woocommerce_admin_process_product_object',
			array(
				$this,
				'save_fields_product_option',
			)
		);

		$this->set_fields();

		add_action(
			'woocommerce_product_after_variable_attributes',
			array(
				$this,
				'fields_product_option_variation',
			),
			10,
			3
		);
		add_action(
			'woocommerce_save_product_variation',
			array(
				$this,
				'save_fields_product_option_variation',
			),
			10,
			2
		);
	}

	public function set_fields() {
		$this->fields = array(
			array(
				'type'                => 'text',
				'id'                  => EAN_FIELD_SLUG,
				'label'               => 'EAN',
				'placeholder'         => '',
				'description'         => '',
				'is_variation_option' => true,
			),
			array(
				'type'                => 'select',
				'id'                  => BRAND_FIELD_SLUG,
				'label'               => 'Brand',
				'placeholder'         => '',
				'description'         => '',
				'is_variation_option' => false,
				'taxonomy'            => Taxonomies::BRAND_TAXONOMY_SLUG,
				'options_callback'    => 'get_brand_options',
			),
		);
	}

	public function fields_product_inventory_tab() {
		if ( empty( $this->fields ) ) {
			return;
		}

		echo '<div class="options_group">';
		foreach ( $this->fields as $field ) {
			$this->add_field( $field );
		}
		echo '</div>';
	}

	/**
	 * @param $field array
	 */
	public function add_field( $field ) {
		$input = array(
			'id'            => $field['id'],
			'label'         => $field['label'],
			'placeholder'   => $field['label'],
			'desc_tip'      => ! empty( $field['description'] ),
			'description'   => $field['description'],
			'class'         => isset( $field['class'] ) ? $field['class'] : '',
			'wrapper_class' => isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '',
		);

		if ( 'text' === $field['type'] ) {
			if ( isset( $field['value'] ) ) {
				$input['value'] = $field['value'];
			}
			woocommerce_wp_text_input( $input );
		} elseif ( 'select' === $field['type'] ) {
			$input['options'] = call_user_func( array( $this, $field['options_callback'] ) );
			$field['class']   = 'select short';
			woocommerce_wp_select( $input );
		}
	}

	public function get_brand_options() {
		$brands = get_terms(
			array(
				'taxonomy'   => Taxonomies::BRAND_TAXONOMY_SLUG,
				'hide_empty' => false,
			)
		);
		if ( ! is_array( $brands ) || ! taxonomy_exists( Taxonomies::BRAND_TAXONOMY_SLUG ) ) {
			return array();
		}

		$options     = array();
		$options[''] = '';
		foreach ( $brands as $brand ) {
			$options[ $brand->term_id ] = $brand->name;
		}

		return $options;
	}


	/**
	 * @param $wc_product \WC_Product|\WC_Product_Variable
	 */
	public function save_fields_product_option( $wc_product ) {
		if ( empty( $this->fields ) ) {
			return;
		}

		foreach ( $this->fields as $field ) {
			if ( ! isset( $_POST[ $field['id'] ] ) || ! isset( $field['taxonomy'] ) ) {
				continue;
			}

			if ( ! taxonomy_exists( $field['taxonomy'] ) ) {
				continue;
			}

			$field_post_data = $_POST[ $field['id'] ]; //phpcs:ignore

			$term = get_term( $field_post_data, $field['taxonomy'] );
			if ( is_wp_error( $term ) || is_null( $term ) ) {
				continue;
			}
			wp_set_object_terms( $wc_product->get_id(), array( $term->term_id ), $field['taxonomy'] );

			$wc_product->update_meta_data( $field['id'], wc_clean( wp_unslash( $field_post_data ) ) );
			$wc_product->save_meta_data();
		}
	}

	/**
	 * @param $index int
	 * @param $variation_data array
	 * @param $variation \WP_Post
	 */
	public function fields_product_option_variation( $index, $variation_data, $variation ) {
		if ( empty( $this->fields ) || empty( $variation_data ) ) {
			return;
		}

		$wc_product = wc_get_product( $variation );
		if ( ! $wc_product ) {
			return;
		}

		foreach ( $this->fields as $field ) {
			if ( empty( $field['is_variation_option'] ) ) {
				continue;
			}

			$meta_key = $field['id'];
			if ( EAN_FIELD_SLUG === $field['id'] ) {
				$meta_key = EAN_FIELD_SLUG;

				// Handle EAN meta key with index for variations
				if ( empty( $wc_product->get_meta( $meta_key ) ) ) {
					$old_meta_key = ShoppingFeedAdvancedHelper::find_old_variation_ean_meta_key( $wc_product );
					if ( ! empty( $old_meta_key ) ) {
						$meta_key = $old_meta_key;
					}
				}
			}

			$field_id               = $field['id'] . '_' . $index;
			$field['value']         = $wc_product->get_meta( $meta_key );
			$field['id']            = $field_id;
			$field['class']         = 'short';
			$field['wrapper_class'] = 'form-row form-row-full form-field';
			$this->add_field( $field );
		}
	}


	/**
	 * @param $variation_id int
	 */
	public function save_fields_product_option_variation( $variation_id, $index ) {
		$wc_product = wc_get_product( $variation_id );

		foreach ( $this->fields as $field ) {
			if ( empty( $field['is_variation_option'] ) ) {
				continue;
			}

			$meta_key        = $field['id'];
			$post_data_key   = $field['id'] . '_' . $index;
			$field_post_data = $_POST[ $post_data_key ]; //phpcs:ignore

			$wc_product->update_meta_data( $meta_key, wc_clean( wp_unslash( $field_post_data ) ) );
			$wc_product->save_meta_data();
		}
	}
}
