<?php

class Affiliate_WP_Upgrades {

	/**
	 * Whether debug mode is enabled.
	 *
	 * @since 1.8.6
	 * @access private
	 * @var bool
	 */
	private $debug;

	/**
	 * Affiliate_WP_Logging instance.
	 *
	 * @since 1.8.6
	 * @access private
	 * @var Affiliate_WP_Logging
	 */
	private $logs;

	private $upgraded = false;

	public function __construct() {

		add_action( 'admin_init', array( $this, 'init' ), -9999 );

		$settings = new Affiliate_WP_Settings;
		$this->debug = (bool) $settings->get( 'debug_mode', false );

		if ( $this->debug ) {
			require_once AFFILIATEWP_PLUGIN_DIR . 'includes/class-logging.php';

			$this->logs = new Affiliate_WP_Logging;
		}
	}

	public function init() {

		$version = get_option( 'affwp_version' );
		if ( empty( $version ) ) {
			$version = '1.0.6'; // last version that didn't have the version option set
		}

		if ( version_compare( $version, '1.1', '<' ) ) {
			$this->v11_upgrades();
		}

		if ( version_compare( $version, '1.2.1', '<' ) ) {
			$this->v121_upgrades();
		}

		if ( version_compare( $version, '1.3', '<' ) ) {
			$this->v13_upgrades();
		}

		if ( version_compare( $version, '1.6', '<' ) ) {
			$this->v16_upgrades();
		}

		if ( version_compare( $version, '1.7', '<' ) ) {
			$this->v17_upgrades();
		}

		if ( version_compare( $version, '1.7.3', '<' ) ) {
			$this->v173_upgrades();
		}

		if ( version_compare( $version, '1.7.11', '<' ) ) {
			$this->v1711_upgrades();
		}

		if ( version_compare( $version, '1.7.14', '<' ) ) {
			$this->v1714_upgrades();
		}

		if ( version_compare( $version, '1.8.6', '<' ) ) {
			$this->v186_upgrades();
		}

		// Inconsistency between current and saved version.
		if ( version_compare( $version, AFFILIATEWP_VERSION, '<>' ) ) {
			$this->upgraded = true;
		}

		// If upgrades have occurred
		if ( $this->upgraded ) {
			update_option( 'affwp_version_upgraded_from', $version );
			update_option( 'affwp_version', AFFILIATEWP_VERSION );
		}

	}

	/**
	 * Writes a log message.
	 *
	 * @access private
	 * @since 1.8.6
	 *
	 * @param string $message Optional. Message to log.
	 */
	private function log( $message = '' ) {
		if ( $this->debug ) {
			$this->logs->log( $message );
		}
	}

	/**
	 * Perform database upgrades for version 1.1
	 *
	 * @access  private
	 * @since   1.1
	*/
	private function v11_upgrades() {

		@affiliate_wp()->affiliates->create_table();

		$this->upgraded = true;

	}

	/**
	 * Perform database upgrades for version 1.2.1
	 *
	 * @access  private
	 * @since   1.2.1
	*/
	private function v121_upgrades() {

		@affiliate_wp()->creatives->create_table();

		$this->upgraded = true;

	}

	/**
	 * Perform database upgrades for version 1.3
	 *
	 * @access  private
	 * @since   1.3
	 */
	private function v13_upgrades() {

		@affiliate_wp()->creatives->create_table();

		// Clear rewrite rules
		flush_rewrite_rules();

		$this->upgraded = true;

	}

	/**
	 * Perform database upgrades for version 1.6
	 *
	 * @access  private
	 * @since   1.6
	 */
	private function v16_upgrades() {

		@affiliate_wp()->affiliate_meta->create_table();
		@affiliate_wp()->referrals->create_table();

		$this->upgraded = true;

	}

	/**
	 * Perform database upgrades for version 1.7
	 *
	 * @access  private
	 * @since   1.7
	 */
	private function v17_upgrades() {

		@affiliate_wp()->referrals->create_table();
		@affiliate_wp()->visits->create_table();
		@affiliate_wp()->campaigns->create_view();

		$this->v17_upgrade_referral_rates();

		$this->v17_upgrade_gforms();

		$this->v17_upgrade_nforms();

		$this->upgraded = true;

	}

	/**
	 * Perform database upgrades for version 1.7.3
	 *
	 * @access  private
	 * @since   1.7.3
	 */
	private function v173_upgrades() {

		$this->v17_upgrade_referral_rates();

		$this->upgraded = true;

	}

	/**
	 * Perform database upgrades for referral rates in version 1.7
	 *
	 * @access  private
	 * @since   1.7
	 */
	private function v17_upgrade_referral_rates() {

		global $wpdb;

		$prefix  = ( defined( 'AFFILIATE_WP_NETWORK_WIDE' ) && AFFILIATE_WP_NETWORK_WIDE ) ? null : $wpdb->prefix;
		$results = $wpdb->get_results( "SELECT affiliate_id, rate FROM {$prefix}affiliate_wp_affiliates WHERE rate_type = 'percentage' AND rate > 0 AND rate <= 1;" );

		if ( $results ) {
			foreach ( $results as $result ) {
				$wpdb->update(
					"{$prefix}affiliate_wp_affiliates",
					array( 'rate' => floatval( $result->rate ) * 100 ),
					array( 'affiliate_id' => $result->affiliate_id ),
					array( '%d' ),
					array( '%d' )
				);
			}
		}

		$settings  = get_option( 'affwp_settings' );
		$rate_type = ! empty( $settings['referral_rate_type'] ) ? $settings['referral_rate_type'] : null;
		$rate      = isset( $settings['referral_rate'] ) ? $settings['referral_rate'] : 20;

		if ( 'percentage' !== $rate_type ) {
			return;
		}

		if ( $rate > 0 && $rate <= 1 ) {
			$settings['referral_rate'] = floatval( $rate ) * 100;
		} elseif ( '' === $rate || '0' === $rate || '0.00' === $rate ) {
			$settings['referral_rate'] = 0;
		} else {
			$settings['referral_rate'] = floatval( $rate );
		}

		// Update settings.
		affiliate_wp()->settings->set( $settings, $save = true );
	}

	/**
	 * Perform database upgrades for Gravity Forms in version 1.7
	 *
	 * @access  private
	 * @since   1.7
	 */
	private function v17_upgrade_gforms() {

		$settings = get_option( 'affwp_settings' );

		if ( empty( $settings['integrations'] ) || ! array_key_exists( 'gravityforms', $settings['integrations'] ) ) {
			return;
		}

		global $wpdb;

		$tables = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}rg_form%';" );

		if ( ! $tables ) {
			return;
		}

		$forms = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}rg_form;" );

		if ( ! $forms ) {
			return;
		}

		foreach ( $forms as $form ) {

			$meta = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT display_meta FROM {$wpdb->prefix}rg_form_meta WHERE form_id = %d;",
					$form->id
				)
			);

			$meta = json_decode( $meta );

			if ( isset( $meta->gform_allow_referrals ) ) {
				continue;
			}

			$meta->gform_allow_referrals = 1;

			$meta = json_encode( $meta );

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}rg_form_meta SET display_meta = %s WHERE form_id = %d;",
					$meta,
					$form->id
				)
			);

		}

	}

	/**
	 * Perform database upgrades for Ninja Forms in version 1.7
	 *
	 * @access  private
	 * @since   1.7
	 */
	private function v17_upgrade_nforms() {

		$settings = get_option( 'affwp_settings' );

		if ( empty( $settings['integrations'] ) || ! array_key_exists( 'ninja-forms', $settings['integrations'] ) ) {
			return;
		}

		global $wpdb;

		$tables = $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}nf_object%';" );

		if ( ! $tables ) {
			return;
		}

		$forms = $wpdb->get_results( "SELECT id FROM {$wpdb->prefix}nf_objects WHERE type = 'form';" );

		if ( ! $forms ) {
			return;
		}

		// There could be forms that already have this meta saved in the DB, we will ignore those
		$_forms = $wpdb->get_results( "SELECT object_id FROM {$wpdb->prefix}nf_objectmeta WHERE meta_key = 'affwp_allow_referrals';" );

		$forms  = wp_list_pluck( $forms, 'id' );
		$_forms = wp_list_pluck( $_forms, 'object_id' );
		$forms  = array_diff( $forms, $_forms );

		if ( ! $forms ) {
			return;
		}

		foreach ( $forms as $form_id ) {

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$wpdb->prefix}nf_objectmeta (object_id,meta_key,meta_value) VALUES (%d,'affwp_allow_referrals','1');",
					$form_id
				)
			);

		}

	}

	/**
	 * Perform database upgrades for version 1.7.11
	 *
	 * @access  private
	 * @since   1.7.11
	 */
	private function v1711_upgrades() {

		$settings = affiliate_wp()->settings->get_all();

		// Ensures settings are not lost if the duplicate email/subject fields were used before they were removed
		if( ! empty( $settings['rejected_email'] ) && empty( $settings['rejection_email'] ) ) {
			$settings['rejection_email'] = $settings['rejected_email'];
			unset( $settings['rejected_email'] );
		}

		if( ! empty( $settings['rejected_subject'] ) && empty( $settings['rejection_subject'] ) ) {
			$settings['rejection_subject'] = $settings['rejected_subject'];
			unset( $settings['rejected_subject'] );
		}

		// Update settings.
		affiliate_wp()->settings->set( $settings, $save = true );

		$this->upgraded = true;

	}

	/**
	 * Perform database upgrades for version 1.7.14
	 *
	 * @access  private
	 * @since   1.7.14
	 */
	private function v1714_upgrades() {

		@affiliate_wp()->visits->create_table();

		$this->upgraded = true;

	}

	/**
	 * Perform database upgrades for version 1.8.6.
	 *
	 * Attempts to update core table charsets to utf8mb4.
	 *
	 * @access private
	 * @since 1.8.6
	 */
	private function v186_upgrades() {

		/**
		 * Filters whether to skip the utf8mb4 table upgrade.
		 *
		 * Explicitly passing a true value to this hook will exit the table upgrade routine early.
		 *
		 * @since 1.8.6
		 *
		 * @param bool $skip Whether to skip the utf8mb4 upgrade routine. Default false.
		 */
		$skip_upgrade = apply_filters( 'affwp_skip_utf8mb4_upgrade', false );

		// If skipping, log that it was skipped.
		if ( true === $skip_upgrade ) {
			$this->log( __( 'The utf8mb4 upgrade was skipped.', 'affiliate-wp' ) );

			return;
		}

		$tables = array(
			@affiliate_wp()->affiliate_meta->table_name,
			@affiliate_wp()->affiliates->table_name,
			@affiliate_wp()->creatives->table_name,
			@affiliate_wp()->referrals->table_name,
			@affiliate_wp()->visits->table_name
		);

		$success = __( "The character set for the %s table was successfully upgraded to utf8mb4.", 'affiliate-wp' );
		$failure = __( "The character set for the %s table could not be upgraded or was already using utf8mb4.", 'affiliate-wp' );

		// Upgrade core table charsets and log success or failure for each.
		if ( ! empty( $tables ) ) {
			/** Load WordPress Administration Upgrade API */
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			foreach ( $tables as $table ) {
				$result = maybe_convert_table_to_utf8mb4( $table );

				$message = sprintf( true === $result ? $success : $failure, $table );

				$this->log( $message );
			}
		}

		$this->upgraded = true;
	}

}
new Affiliate_WP_Upgrades;