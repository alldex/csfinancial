<?php
namespace AffiliateLTP\ProfileBuilder;

use AffiliateLTP\mailchimp\MailChimp_Service;
use Psr\Log\LoggerInterface;

/**
 * Copyright MyCommonSenseFinancial @2017
 * All rights reserved.
 */
/**
 * Handles extensions to functionality with the profile-builder plugin. This
 * enables users to change their email and username which is normally not allowed
 * on the profile builder page.
 *
 * @author snielson
 */
class Profile_Builder_Extensions {

    /**
     *
     * @var MailChimp_Service
     */
    private $service;
    
    /**
     *
     * @var LoggerInterface
     */
    private $logger;
    
    public function __construct(LoggerInterface $logger, MailChimp_Service $mailchimp) {
        $this->service = $mailchimp;
        $this->logger = $logger;
        // we tie in to where we check to see if we should send an email
        // this feels a bit hacky, but I can't find a better hook than this.
        add_filter('send_email_change_email', array($this, 'update_email_address'), 10, 3);
        add_filter('wppb_edit_profile_success', array($this, 'check_username_update'), 10, 3);
        
        $this->logger->info("in profile builder constructor");
        
        add_filter('wppb_edit_profile_username', array($this, 'enable_username_edit'), 10, 6 );
        

    }
    
    public function check_username_update($form_request, $form_name, $user_id) {
        $this->logger->info("check_username_update called for user($user_id)");
        
        $user_data = get_userdata($user_id);
        if (empty($user_data->user_login) || empty($form_request['username'])) {
            $this->logger->info("check_username_update user_login or username is missing");
            // nothing to do here.
            return;
        }
        $old_username = $user_data->user_login;
        $new_username = $form_request['username'];
        
        if ($old_username == $new_username) {
            return;
        }
        
        $this->logger->info("check_username_update changing username from $old_username to $new_username");
        // from the username plugin.  @see: https://github.com/section214/username-changer.git
        if (function_exists('username_changer_process')) {
            if (!username_changer_process($old_username, $new_username)) {
                $this->logger->error("Failed to change username from $old_username to $new_username");
            }
            $this->logger->info("check_username_update username changed! "
                    . " user($user_id) now has username $new_username.  "
                        . "User will now be logged out and redirected to the home page");
            $this->email_username_change($user_data, $new_username);
            
            echo "<p class='alert alert-info'>Your login is no longer valid and you have been logged out.  Please login again.  Click <a href='" 
            . get_site_url() . "'>here</a> to login<p>";
            add_filter('wppb_no_form_after_profile_update', array($this, 'skip_wppb_form'), 1);
            return;
        }
        else {
            $this->logger->error("username_changer_process function is missing.  Check that WP plugin username-changer is installed and activated!"
                    . "  See https://github.com/section214/username-changer.git");
        }
    }
    
    /**
     * Skips the form of the profile builder from displaying when the username has changed.
     * @param boolean $skip_form
     * @return boolean
     */
    public function skip_wppb_form($skip_form) {
        return true;
    }
    
    public function enable_username_edit( $output, $form_location, $field, $user_id, $field_check_errors, $request_data ) {
        return str_replace('disabled="disabled"', '', $output);
    }
    
    public function update_email_address($should_change_email, $user, $userdata) {
        // verify again that we need to change it.
        $this->logger->info("in update_email_address");
        
        try {
            if ( isset( $userdata['user_email'] ) && $user['user_email'] !== $userdata['user_email'] ) {
                
                $old_email = $user['user_email'];
                $new_email = $userdata['user_email'];
                $this->logger->info("running update_email_address for $old_email to $new_email");
                $this->service->update_subscription($old_email, $new_email);
                $this->logger->info("subscription updated");
            }
        } catch (\Exception $ex) {
            $this->logger->error($ex);
        }
        
        return $should_change_email;
        
    }
    
    private function email_username_change($user, $new_username) {
        $username_change_text = __( 'Hi,

This notice confirms that your username was changed on ###SITENAME###.

If you did not change your username, please contact the Site Administrator at
###ADMIN_EMAIL###

This email has been sent to ###EMAIL###

Regards,
All at ###SITENAME###
###SITEURL###' );

        $username_change_email = array(
                'to'      => $user->user_email,
                'subject' => __( '[%s] Notice of Username Change' ),
                'message' => $username_change_text,
                'headers' => '',
        );


        $username_change_email = apply_filters( 'username_change_email', $username_change_email, $user, $new_username );
        $blog_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
        $pass_change_email['message'] = str_replace( '###USERNAME###', $new_username, $pass_change_email['message'] );
        $pass_change_email['message'] = str_replace( '###ADMIN_EMAIL###', get_option( 'admin_email' ), $pass_change_email['message'] );
        $pass_change_email['message'] = str_replace( '###EMAIL###', $user->user_email, $pass_change_email['message'] );
        $pass_change_email['message'] = str_replace( '###SITENAME###', $blog_name, $pass_change_email['message'] );
        $pass_change_email['message'] = str_replace( '###SITEURL###', home_url(), $pass_change_email['message'] );

        wp_mail( $pass_change_email['to'], sprintf( $pass_change_email['subject'], $blog_name ), $pass_change_email['message'], $pass_change_email['headers'] );
    }
}