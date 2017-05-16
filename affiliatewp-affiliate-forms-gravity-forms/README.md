AffiliateWP - Affiliate Forms For Gravity Forms
====================

####v1.0.11, 21st November 2016

Fix: frontend.js file not loading correctly since AffiliateWP v1.9.5

####v1.0.10, 28th October 2016

Tweak: Store the registration form ID in AffiliateWP settings to improve performance

####v1.0.9.2, 1st August 2016

Fix: Extra emails were being sent when the "Auto Register New Users" option was enabled

####v1.0.9.1, 16th June 2016

Fix: Fatal error that could occur when AffiliateWP was deactivated

####v1.0.9, 9 January 2016

Fix: Fatal error that could occur when the add-on is installed but no registration form is set, and the default affiliate registration form is submitted

Fix: Removed submission row on the edit affiliate screen for manually added affiliates

Tweak: AffiliateWP related form elements and options will only be shown if the Gravity Forms integration is enabled

Tweak: When an affiliate registration form has been set, a message + link to that form will be shown on all other forms

####v1.0.8, 18th December 2015

New: affiliatewp_afgf_insert_user filter for modifying wp_insert_user args

Fix: Field data was not showing on the affiliate review screen due to a caching issue on some hosts

####v1.0.7, August 25, 2015

Fix: Prevented the affiliate registration form from appearing outside of the shortcodes it was embedded in.

Fix: Form validation when a confirm email address field is present

####v1.0.6, August 20, 2015

Fix: bug with activation that could cause a fatal error in some instances

####v1.0.5, 24 June 2015

Fix: Last name field was not correctly populating the user's profile

Fix: Add-on updates were not working.

####v1.0.4, 22 June 2015

Fix: Prevent login details email from being sent to the affiliate if they already have a WP user account

Fix: Incorrect text domains in some strings

####v1.0.3

Fix: Registration form could not be submitted by logged in user due to validation errors

Fix: Undefined index PHP Notice when submitting the form without entering a first name

Fix: When Gravity Forms was not active, the activation notice's URL to Gravity Forms site did not work

####v1.0.2

Fix: If the affiliate registration form had no password field, the password sent to the affiliate via email contained an erroneous exclamation mark which caused issues when logging in

####v1.0.1
Fix: If the affiliate registration form did not include the required email field, it displayed an error message on all other forms

####v1.0

* Initial release
