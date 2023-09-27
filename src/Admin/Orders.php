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
	public function custom_orders_list_column_content( $column, $post_ID_or_order_object ) {
		// Handle the cases of param being and id or a WC_Order object
		$order = false;
		if ( $post_ID_or_order_object instanceof \WC_Order ) {
			$order = $post_ID_or_order_object;
		} elseif ( is_int( $post_ID_or_order_object ) && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $post_ID_or_order_object );
		}
		if ( false === $order ) {
			return;
		}

		if ( $this->channel_name_meta === $column ) {
			$sf_name = $order->get_meta( $this->channel_name_meta, true );
			echo ! empty( $sf_name ) ? esc_html( $sf_name ) : '<small>(<em>' . esc_html__( 'None', 'shopping-feed-advanced' ) . '</em>)</small>';
		}
		if ( $this->sf_reference_meta === $column ) {
			$sf_reference = $order->get_meta( $this->sf_reference_meta, true );
			echo ! empty( $sf_reference ) ? esc_html( $sf_reference ) : '<small>(<em>' . esc_html__( 'None', 'shopping-feed-advanced' ) . '</em>)</small>';
		}
	}
}
