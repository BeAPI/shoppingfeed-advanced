<?php

namespace ShoppingFeed\ShoppingFeedWCAdvanced\Admin;

// Exit on direct access
defined( 'ABSPATH' ) || exit;

class Order {

	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'register_sfa_tracking_details_metabox' ), 100 );
		add_action( 'save_post', array( $this, 'save_sfa_tracking_details_metabox' ) );
		add_action( 'save_post', array( $this, 'save_sfa_empty_ean_field' ) );
	}

	public function save_sfa_tracking_details_metabox( $post_id ) {
		// Check if we can save
		if ( ! $this->sfa_should_save_post( $post_id ) ) {
			return;
		}

		if ( isset( $_POST[ TRACKING_NUMBER_FIELD_SLUG ] ) ) {
			update_post_meta(
				$post_id,
				TRACKING_NUMBER_FIELD_SLUG,
				sanitize_text_field( wp_unslash( $_POST[ TRACKING_NUMBER_FIELD_SLUG ] ) )
			);
		}
		if ( isset( $_POST[ TRACKING_LINK_FIELD_SLUG ] ) ) {
			update_post_meta(
				$post_id,
				TRACKING_LINK_FIELD_SLUG,
				sanitize_text_field( wp_unslash( $_POST[ TRACKING_LINK_FIELD_SLUG ] ) )
			);
		}
	}

	public function register_sfa_tracking_details_metabox() {
		global $post;
		$screen = get_current_screen();
		if ( is_null( $screen ) || 'shop_order' !== $screen->post_type ) {
			return;
		}

		$order = wc_get_order( $post );
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
			'shop_order',
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

	/**
     * Save the EAN field even if it's empty.
     * @see https://support.beapi.fr/issues/62938
     *
	 * @param $post_id
	 *
	 * @return void
	 */
	public function save_sfa_empty_ean_field( $post_id ) {
		// Check if we can save
		if ( ! $this->sfa_should_save_post( $post_id ) ) {
			return;
		}

		// Save only if empty (to bypass WC default)
		if ( isset( $_POST[ EAN_FIELD_SLUG ] ) && empty( $_POST[ EAN_FIELD_SLUG ] ) ) {
			update_post_meta(
				$post_id,
				EAN_FIELD_SLUG,
				sanitize_text_field( $_POST[ EAN_FIELD_SLUG ] )
			);
		}
	}

	/**
     * Check if we can save the SF post
     *
	 * @param $post_id
	 *
	 * @return bool
	 */
	private function sfa_should_save_post( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		return true;
	}
}
