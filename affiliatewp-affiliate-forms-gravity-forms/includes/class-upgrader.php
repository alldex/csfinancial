<?php
/**
 * Upgrader class for Affiliate_WP_Forms_For_Gravity_Forms.
 *
 * @since 1.0.10
 */
class Affiliate_WP_Forms_For_Gravity_Forms_Upgrades {

	/**
	 * Whether the db was upgraded.
	 *
	 * @access private
	 * @since  1.0.10
	 * @var    bool
	 */
	private $upgraded = false;

	/**
	 * Sets up the upgrader class.
	 *
	 * @access public
	 * @since  1.0.10
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'init' ), -9999 );
	}

	public function init() {

		$version = get_option( 'affwp_afgf_version' );

		if ( empty( $version ) ) {
			$version = '1.0.9.2'; // last version that didn't have the version option set
		}

		if ( version_compare( $version, '1.0.10', '<' ) ) {
			$this->v1010_upgrades();
		}

		// If upgrades have occurred
		if ( $this->upgraded ) {
			update_option( 'affwp_afgf_version', AFFWP_AFGF_VERSION );
		}

	}

	/**
	 * Perform database upgrades for version 1.0.10.
	 *
	 * @access private
	 * @since  1.0.10
	 */
	private function v1010_upgrades() {
		$forms = GFAPI::get_forms();

		if ( $forms ) {
			foreach ( $forms as $form ) {
				if ( isset( $form['affwp_gravity_forms_registration'] ) && true == $form['affwp_gravity_forms_registration'] ) {
					$form_id = $form['id'];
				}
			}
			if ( $form_id ) {
				@affiliate_wp()->settings->set( array( 'affwp_gravity_forms_registration_form' => $form_id ), true );
			}
		}

		$this->upgraded = true;
	}

}
new Affiliate_WP_Forms_For_Gravity_Forms_Upgrades;