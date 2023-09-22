<?php

namespace ShoppingFeed\ShoppingFeedWCAdvanced\Admin;

use ShoppingFeed\ShoppingFeedWC\Query\Query;
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

// Exit on direct access
defined( 'ABSPATH' ) || exit;

class Orders {

	/** @var string */
	private $channel_name_meta;

	/** @var string */
	private $sf_reference_meta;

	public function __construct() {

		if ( class_exists( 'Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && \wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
			$screen = \wc_get_page_screen_id( 'shop_order' );
			add_filter( "manage_{$screen}_columns", [ $this, 'custom_shop_order_column' ] );
			add_action( "manage_{$screen}_custom_column", [ $this, 'custom_orders_list_column_content' ], 10, 2 );
		} else {
			add_filter( 'manage_edit-shop_order_columns', [ $this, 'custom_shop_order_column' ] );
			add_action( 'manage_shop_order_posts_custom_column', [
				$this,
				'custom_orders_list_column_content',
			], 10, 2 );
		}

		$this->channel_name_meta = Query::WC_META_SF_CHANNEL_NAME;
		$this->sf_reference_meta = Query::WC_META_SF_REFERENCE;
	}

	public function custom_shop_order_column( $columns ) {
		$reordered_columns = array();

		// Inserting columns to a specific location
		foreach ( $columns as $key => $column ) {
			$reordered_columns[ $key ] = $column;
			if ( 'order_status' !== $key ) {
				continue;
			}
			$reordered_columns[ $this->channel_name_meta ] = __( 'Market Place', 'shopping-feed-advanced' );
			$reordered_columns[ $this->sf_reference_meta ] = __( 'Reference', 'shopping-feed-advanced' );
		}

		return $reordered_columns;
	}

	// Adding custom fields meta data for each new column (example)
	public function custom_orders_list_column_content( $column, $post_id ) {
		if ( function_exists( 'wc_get_order' ) ) {
			if ( $this->channel_name_meta === $column ) {
				$order = wc_get_order( $post_id );
				if ( false !== $order ) {
					$sf_reference = $order->get_meta( $this->channel_name_meta, true );
					echo ! empty( $sf_reference ) ? esc_html( $sf_reference ) : '<small>(<em>' . esc_html__( 'None', 'shopping-feed-advanced' ) . '</em>)</small>';
				}
			}
			if ( $this->sf_reference_meta === $column ) {
				$order = wc_get_order( $post_id );
				if ( false !== $order ) {
					$sf_reference = get_post_meta( $post_id, $this->sf_reference_meta, true );
					echo ! empty( $sf_reference ) ? esc_html( $sf_reference ) : '<small>(<em>' . esc_html__( 'None', 'shopping-feed-advanced' ) . '</em>)</small>';
				}
			}
		}
	}
}
