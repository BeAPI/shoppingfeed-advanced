<?php

namespace ShoppingFeed\ShoppingFeedWCAdvanced;

use ShoppingFeed\ShoppingFeedWCAdvanced\Admin\Options;

// Exit on direct access
defined( 'ABSPATH' ) || exit;

/**
 * Define All needed methods
 * Helper class.
 *
 * @package ShoppingFeed
 */
class ShoppingFeedAdvancedHelper {

	public static function get_last_action_data() {
		$last_action_id = get_option( LAST_MIGRATION_ACTION );
		if ( empty( $last_action_id ) ) {
			return array();
		}

		try {
			return array(
				'date'   => \ActionScheduler::store()->get_date( $last_action_id )->format( 'Y-m-d H:i:s' ),
				'status' => \ActionScheduler::store()->get_status( $last_action_id ),
			);
		} catch ( \Exception $exception ) {
			return array();
		}
	}

	public static function get_sfa_settings( $param ) {
		$options = get_option( Options::SFA_OPTIONS );
		if ( empty( $param ) ) {
			return $options;
		}

		if ( empty( $options[ $param ] ) ) {
			return false;
		}

		return $options[ $param ];
	}

	/**
	 * Return the settings link for plugin
	 * @return string
	 */
	public static function get_setting_link() {
		return admin_url( 'admin.php?page=shopping-feed-advanced' );
	}

	/**
	 * Handle back-compat with EAN meta saved with a key containing an index.
	 *
	 * @param \WC_Product $wc_product
	 *
	 * @return string
	 */
	public static function find_old_variation_ean_meta_key( $wc_product ) {
		$meta_key = '';
		if ( 'variation' !== $wc_product->get_type() ) {
			return $meta_key;
		}

		/* @var \WC_Meta_Data[] $meta_data */
		$meta_data = $wc_product->get_meta_data();
		foreach ( $meta_data as $meta_datum ) {
			if ( false !== stripos( $meta_datum->get_data()['key'], EAN_FIELD_SLUG ) ) {
				$meta_key = $meta_datum->get_data()['key'];
				break;
			}
		}

		return $meta_key;
	}
}
