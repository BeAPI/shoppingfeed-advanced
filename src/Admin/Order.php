<?php

namespace ShoppingFeed\ShoppingFeedWCAdvanced\Admin;

// Exit on direct access
use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

defined( 'ABSPATH' ) || exit;

class Order {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_sfa_tracking_details_metabox' ), 100 );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_sfa_tracking_details_metabox' ) );
	}

	public function save_sfa_tracking_details_metabox( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( empty( $_POST['sfa_tracking_nounce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['sfa_tracking_nounce'] ) ), '_sfa_tracking_nounce' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$order = wc_get_order( $post_id );
		if ( false === $order ) {
			return;
		}
		if ( isset( $_POST[ TRACKING_NUMBER_FIELD_SLUG ] ) ) {
			$order->update_meta_data(
				TRACKING_NUMBER_FIELD_SLUG,
				sanitize_text_field( wp_unslash( $_POST[ TRACKING_NUMBER_FIELD_SLUG ] ) )
			);
		}
		if ( isset( $_POST[ TRACKING_LINK_FIELD_SLUG ] ) ) {
			$order->update_meta_data(
				TRACKING_LINK_FIELD_SLUG,
				sanitize_text_field( wp_unslash( $_POST[ TRACKING_LINK_FIELD_SLUG ] ) )
			);
		}
		if ( isset( $_POST[ TRACKING_NUMBER_FIELD_SLUG ] ) || isset( $_POST[ TRACKING_LINK_FIELD_SLUG ] ) ) {
			$order->save();
		}
	}

	public function register_sfa_tracking_details_metabox() {

		if ( class_exists( 'Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController' ) && wc_get_container()->get( CustomOrdersTableController::class )->custom_orders_table_usage_is_enabled() ) {
			/**
			 * If WC is using the new tables, returns the screen id or empty
			 */
			$screen = wc_get_page_screen_id( 'shop-order' );
			if ( empty( $screen ) ) {
				return;
			}
			if ( ! isset( $_GET['id'], $_GET['page'] ) || ! is_numeric( $_GET['id'] ) || 'wc-orders' !== $_GET['page']
			) {
				return;
			}
			$post_id = (int) $_GET['id'];
		} else {
			/**
			 * If not, we use the legacy test
			 */
			$screen = get_current_screen();
			if ( is_null( $screen ) || 'shop_order' !== $screen->post_type ) {
				return;
			}
			global $post;
			$post_id = $post->ID;
		}

		$order = wc_get_order( $post_id );

		if ( false === $order ) {
			return;
		}

		if ( ! \ShoppingFeed\ShoppingFeedWC\Orders\Order::is_sf_order( $order ) ) {
			return;
		}

		add_meta_box(
			'sfa-carrier_fields',
			__( 'ShoppingFeed Carrier Details', 'shopping-feed-advanced' ),
			array( $this, 'render' ),
			$screen,
			'side'
		);
	}

	public function render( $order ) {
		wp_nonce_field( '_sfa_tracking_nounce', 'sfa_tracking_nounce' );
		$order = wc_get_order( $order );
		if ( false === $order ) {
			return;
		}
		?>

		<p>
			<label for="sfa_tracking_number">
				<?php esc_html_e( 'Tracking Number', 'shopping-feed-advanced' ); ?>
			</label>
			<br>
			<input type="text" name="<?php echo esc_attr( TRACKING_NUMBER_FIELD_SLUG ); ?>"
					id="<?php echo esc_attr( TRACKING_NUMBER_FIELD_SLUG ); ?>"
					value="<?php echo esc_attr( $order->get_meta( TRACKING_NUMBER_FIELD_SLUG ) ); ?>">
		</p>
		<p>
			<label for="sfa_tracking_link">
				<?php esc_html_e( 'Tracking Link', 'shopping-feed-advanced' ); ?>
			</label>
			<br>
			<input type="text" name="<?php echo esc_attr( TRACKING_LINK_FIELD_SLUG ); ?>"
					id="<?php echo esc_attr( TRACKING_LINK_FIELD_SLUG ); ?>"
					value="<?php echo esc_attr( $order->get_meta( TRACKING_LINK_FIELD_SLUG ) ); ?>">
		</p>
		<?php
		submit_button( '', 'primary', 'shoppingfeed_carrier_details_submit' );
	}
}
