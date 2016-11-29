<?php
/**
 * AffiliateWP Admin Notices class
 *
 * @since 1.0
 */
class Affiliate_WP_Admin_Notices {

	/**
	 * Constructor.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function __construct() {

		add_action( 'admin_notices', array( $this, 'show_notices' ) );
		add_action( 'affwp_dismiss_notices', array( $this, 'dismiss_notices' ) );
	}

	/**
	 * Displays admin notices.
	 *
	 * @since 1.0
	 * @since 1.8.3 Notices are hidden for users lacking the 'manage_affiliates' capability
	 * @access public
	 */
	public function show_notices() {
		// Don't display notices for users who can't manage affiliates.
		if ( ! current_user_can( 'manage_affiliates' ) ) {
			return;
		}

		$integrations = affiliate_wp()->integrations->get_enabled_integrations();

		if( empty( $integrations ) && ! get_user_meta( get_current_user_id(), '_affwp_no_integrations_dismissed', true ) ) {
			echo '<div class="error">';
				echo '<p>' . sprintf( __( 'There are currently no AffiliateWP <a href="%s">integrations</a> enabled. If you are using AffiliateWP without any integrations, you may disregard this message.', 'affiliate-wp' ), admin_url( 'admin.php?page=affiliate-wp-settings&tab=integrations' ) ) . '</p>';
				echo '<p><a href="' . wp_nonce_url( add_query_arg( array( 'affwp_action' => 'dismiss_notices', 'affwp_notice' => 'no_integrations' ) ), 'affwp_dismiss_notice', 'affwp_dismiss_notice_nonce' ) . '">' . _x( 'Dismiss Notice', 'Integrations', 'affiliate-wp' ) . '</a></p>';
			echo '</div>';
		}

		$class = 'updated';

		if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] && isset( $_GET['page'] ) && $_GET['page'] == 'affiliate-wp-settings' ) {
			$message = __( 'Settings updated.', 'affiliate-wp' );
		}

		if ( isset( $_GET['affwp_notice'] ) && $_GET['affwp_notice'] ) {

			switch( $_GET['affwp_notice'] ) {

				case 'affiliate_added' :

					$message = __( 'Affiliate added successfully', 'affiliate-wp' );

					break;

				case 'affiliate_added_failed' :

					$message = __( 'Affiliate wasn\'t added, please try again.', 'affiliate-wp' );
					$class   = 'error';

					break;

				case 'affiliate_updated' :

					$message = __( 'Affiliate updated successfully', 'affiliate-wp' );

					$message .= '<p>'. sprintf( __( '<a href="%s">Back to Affiliates</a>', 'affiliate-wp' ), admin_url( 'admin.php?page=affiliate-wp-affiliates' ) ) .'</p>';

					break;

				case 'affiliate_update_failed' :

					$message = __( 'Affiliate update failed, please try again', 'affiliate-wp' );
					$class   = 'error';

					break;

				case 'affiliate_deleted' :

					$message = __( 'Affiliate account(s) deleted successfully', 'affiliate-wp' );

					break;

				case 'affiliate_delete_failed' :

					$message = __( 'Affiliate deletion failed, please try again', 'affiliate-wp' );
					$class   = 'error';

					break;

				case 'affiliate_activated' :

					$message = __( 'Affiliate account activated', 'affiliate-wp' );

					break;

				case 'affiliate_deactivated' :

					$message = __( 'Affiliate account deactivated', 'affiliate-wp' );

					break;

				case 'affiliate_accepted' :

					$message = __( 'Affiliate request was accepted', 'affiliate-wp' );

					break;

				case 'affiliate_rejected' :

					$message = __( 'Affiliate request was rejected', 'affiliate-wp' );

					break;

				case 'stats_recounted' :

					$message = __( 'Affiliate stats have been recounted!', 'affiliate-wp' );

					break;

				case 'referral_added' :

					$message = __( 'Referral added successfully', 'affiliate-wp' );

					break;

				case 'referral_updated' :

					$message = __( 'Referral updated successfully', 'affiliate-wp' );

					break;

				case 'referral_update_failed' :

					$message = __( 'Referral update failed, please try again', 'affiliate-wp' );

					break;

				case 'referral_deleted' :

					$message = __( 'Referral deleted successfully', 'affiliate-wp' );

					break;

				case 'referral_delete_failed' :

					$message = __( 'Referral deletion failed, please try again', 'affiliate-wp' );
					$class   = 'error';

					break;

				case 'creative_updated' :

					$message = __( 'Creative updated successfully', 'affiliate-wp' );

					$message .= '<p>'. sprintf( __( '<a href="%s">Back to Creatives</a>', 'affiliate-wp' ), admin_url( 'admin.php?page=affiliate-wp-creatives' ) ) .'</p>';

					break;

				case 'creative_added' :

					$message = __( 'Creative added successfully', 'affiliate-wp' );

					break;

				case 'creative_deleted' :

					$message = __( 'Creative deleted successfully', 'affiliate-wp' );

					break;

				case 'creative_activated' :

					$message = __( 'Creative activated', 'affiliate-wp' );

					break;

				case 'creative_deactivated' :

					$message = __( 'Creative deactivated', 'affiliate-wp' );

					break;

				case 'settings-imported' :

					$message = __( 'Settings successfully imported', 'affiliate-wp' );

					break;

			}
		}

		if ( ! empty( $message ) ) {
			echo '<div class="' . esc_attr( $class ) . '"><p><strong>' .  $message  . '</strong></p></div>';
		}

		$license = affiliate_wp()->settings->check_license();

		if ( ! is_wp_error( $license ) && false === get_transient( 'affwp_license_notice' ) ) {

			// Base query args.
			$notice_query_args = array(
				'affwp_action' => 'dismiss_notices'
			);

			if ( 'expired' === $license ) {
				$notice_query_args['affwp_notice'] = 'expired_license';

				echo '<div class="error info">';
					echo '<p>' . __( 'Your license key for AffiliateWP has expired. Please renew your license to re-enable automatic updates.', 'affiliate-wp' ) . '</p>';
					echo '<p><a href="' . wp_nonce_url( add_query_arg( $notice_query_args ), 'affwp_dismiss_notice', 'affwp_dismiss_notice_nonce' ) . '">' . _x( 'Dismiss Notice', 'License', 'affiliate-wp' ) . '</a></p>';
				echo '</div>';

			} elseif ( 'valid' !== $license ) {
				$notice_query_args['affwp_notice'] = 'invalid_license';

				echo '<div class="notice notice-info">';
					echo '<p>' . sprintf( __( 'Please <a href="%s">enter and activate</a> your license key for AffiliateWP to enable automatic updates.', 'affiliate-wp' ), admin_url( 'admin.php?page=affiliate-wp-settings' ) ) . '</p>';
					echo '<p><a href="' . wp_nonce_url( add_query_arg( $notice_query_args ), 'affwp_dismiss_notice', 'affwp_dismiss_notice_nonce' ) . '">' . _x( 'Dismiss Notice', 'License', 'affiliate-wp' ) . '</a></p>';
				echo '</div>';
			}

		}

	}

	/**
	 * Dismisses admin notices when Dismiss links are clicked.
	 *
	 * @since 1.7.5
	 * @access public
	 * @return void
	 */
	public function dismiss_notices() {
		if( ! isset( $_GET['affwp_dismiss_notice_nonce'] ) || ! wp_verify_nonce( $_GET['affwp_dismiss_notice_nonce'], 'affwp_dismiss_notice') ) {
			wp_die( __( 'Security check failed', 'affiliate-wp' ), __( 'Error', 'affiliate-wp' ), array( 'response' => 403 ) );
		}

		if ( isset( $_GET['affwp_notice'] ) ) {

			$notice = sanitize_key( $_GET['affwp_notice'] );

			switch( $notice ) {
				case 'no_integrations':
					update_user_meta( get_current_user_id(), "_affwp_{$notice}_dismissed", 1 );
					break;
				case 'expired_license':
				case 'invalid_license':
					set_transient( 'affwp_license_notice', true, 2 * WEEK_IN_SECONDS );
					break;
				default:
					/**
					 * Fires once a notice has been flagged for dismissal.
					 *
					 * @since 1.8
					 *
					 * @param string $notice Notice value via $_GET['affwp_notice'].
					 */
					do_action( 'affwp_dismiss_notices', $notice );
					break;
			}

			wp_redirect( remove_query_arg( array( 'affwp_action', 'affwp_notice' ) ) );
			exit;
		}
	}
}

new Affiliate_WP_Admin_Notices;

