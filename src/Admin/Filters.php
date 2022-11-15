<?php


namespace ShoppingFeed\ShoppingFeedWCAdvanced\Admin;

use ShoppingFeed\ShoppingFeedWCAdvanced\ShoppingFeedAdvancedHelper;

// Exit on direct access
defined( 'ABSPATH' ) || exit;


class Filters {

	public function __construct() {
		$this->add_ean();
		$this->add_brand();
		$this->add_tracking_number();
		$this->add_tracking_link();
	}

	public function add_ean() {
		if ( empty( ShoppingFeedAdvancedHelper::get_sfa_settings( EAN_FIELD_SLUG ) ) ) {
			return;
		}

		add_filter(
			'shopping_feed_custom_ean',
			function ( $meta_key, $wc_product = false ) {
				$meta_key = EAN_FIELD_SLUG;

				// Handle EAN meta key with index for variations
				if ( $wc_product instanceof \WC_Product_Variation && empty( $wc_product->get_meta( $meta_key ) ) ) {
					$old_meta_key = ShoppingFeedAdvancedHelper::find_old_variation_ean_meta_key( $wc_product );
					if ( ! empty( $old_meta_key ) ) {
						$meta_key = $old_meta_key;
					}
				}

				return $meta_key;
			},
			10,
			2
		);
	}

	public function add_brand() {
		if ( empty( ShoppingFeedAdvancedHelper::get_sfa_settings( BRAND_FIELD_SLUG ) ) ) {
			return;
		}

		add_filter(
			'shopping_feed_custom_brand_taxonomy',
			function () {
				return Taxonomies::BRAND_TAXONOMY_SLUG;
			}
		);
	}

	public function add_tracking_number() {
		add_filter(
			'shopping_feed_tracking_number',
			function () {
				return TRACKING_NUMBER_FIELD_SLUG;
			}
		);
	}

	public function add_tracking_link() {
		add_filter(
			'shopping_feed_tracking_link',
			function () {
				return TRACKING_LINK_FIELD_SLUG;
			}
		);
	}
}
