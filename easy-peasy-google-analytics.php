<?php
/*
Plugin Name: Easy Peasy Google Analytics
Plugin URI: http://themesdepot.org
Description: Easy Peasy Google Analytics allows you easily include your google analytics tracking code at the bottom of the footer of your website. All you need is your UA code.
Author: Alessandro Tesoro
Version: 1.0.0
Author URI: http://alessandrotesoro.me
Requires at least: 3.8
Tested up to: 3.8
Text Domain: easy-peasy-google-analytics
Domain Path: /languages
License: GPLv2 or later
*/

/*
Copyright 2014  Alessandro Tesoro

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Easy Google Analytics class.
 */
class Easy_Google_Analytics {

	/**
	 * Constructor - get the plugin hooked in and ready
	 * @since    1.0.0
	 */
	public function __construct() {
		
		// Define constants
		define( 'EGA_VERSION', '1.0.0' );
		define( 'EGA_SLUG', plugin_basename(__FILE__));
		define( 'EGA_PLUGIN_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		define( 'EGA_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );

		//Filters
		add_filter( "plugin_action_links_".EGA_SLUG , array( $this,'ega_add_settings_link') );

		//Actions
		add_action('admin_init', array($this,'ega_add_settings_to_admin'));
		add_action('admin_head-options-general.php', array($this,'add_settings_help_tab'), 20 );
		add_action('wp_footer', array($this,'ega_add_analytics_code'), 9999);
		add_action('plugins_loaded', array($this,'ega_load_plugin_textdomain'));
	}

	/**
	 * Add Settings Link To WP-Plugin Page
	 * @since    1.0.0
	 */
	public function ega_add_settings_link( $links ) {
	    $settings_link = '<a href="options-general.php#ega_ua_code">'.__('Settings','easy-peasy-google-analytics').'</a>';
	  	array_push( $links, $settings_link );
	  	return $links;
	}

	/**
	 * Add New Setting Field To Settings->General 
	 * @since    1.0.0
	 */
	public function ega_add_settings_to_admin(){
		
		register_setting(
			'general',	// settings page
			'ega_options',	// option name
			array(&$this,'ega_validate_options')	// validation callback
		);
		
		add_settings_field(
			'ega_ua_code',	// id
			__('Google Analytics UA Tracking ID', 'easy-peasy-google-analytics'),	// setting title
			array(&$this, 'ega_setting_input'),	// display callback
			'general',	// settings page
			'default'	// settings section
		);

	}

	/**
	 * New Setting Field Output 
	 * @since    1.0.0
	 */
	public function ega_setting_input() {
		
		// get option 'ega_ua_code' value from the database
		$options = get_option( 'ega_options' );
		$value = $options['ega_ua_code'];

		?>
		
		<input id="ega_ua_code" name="ega_options[ega_ua_code]" type="text" class="regular-text ltr" value="<?php echo esc_attr( $value ); ?>" />
		<p class="description"><?php _e('Enter your Google UA Code/ID here. If you don\'t know how to get the UA code, click the &quot;Help&quot; button at the top of this page.','easy-peasy-google-analytics');?></p>
		<?php
	}

	/**
	 * Validate New Setting Field
	 * @since    1.0.0
	 */
	public function ega_validate_options( $input ) {
		$valid = array();
		$valid['ega_ua_code'] = sanitize_text_field( $input['ega_ua_code'] );

		// Something dirty entered? Warn user.
		if( $valid['ega_ua_code'] != $input['ega_ua_code'] ) {
			add_settings_error(
				'ega_ega_ua_code',	// setting title
				'ega_texterror',	// error ID
				__('Something went wrong make sure you have entered a valid UA code.','easy-peasy-google-analytics'),	// error message
				'error'	// type of message
			);		
		}

		return $valid;
	}

	/**
	 * Add Help Tab To Admin Page
	 * @since    1.0.0
	 */
	public function add_settings_help_tab() {

		$help_tab_content = '<ul>';
		$help_tab_content .= '<li>'.__('To get your analytics UA code you need to login into your Google Analytics control panel','easy-peasy-google-analytics').'</li>';
		$help_tab_content .= '<li>'.__('Into the topbar of the page click the &quot;Admin&quot; link.','easy-peasy-google-analytics').'</li>';
		$help_tab_content .= '<li>'.__('Click the &quot;Tracking Info&quot; link.','easy-peasy-google-analytics').'</li>';
		$help_tab_content .= '<li>'.__('Then click the &quot;Tracking Code&quot; link.','easy-peasy-google-analytics').'</li>';
		$help_tab_content .= '<li>'.__('Copy and paste the Tracking ID number into the option in your WordPress admin panel here.','easy-peasy-google-analytics').'</li>';
		$help_tab_content .= '</ul>';
		$help_tab_content .= '<p>'.__('A tracking ID number looks like this UA-2986XXXX-X','easy-peasy-google-analytics').'</p>';
		
		get_current_screen()->add_help_tab( array(
			'id'       => 'ega_settings_help',
			'title'    => __('Google Analytics UA Code', 'easy-peasy-google-analytics'),
			'content'  => '<br/>'.$help_tab_content
		));

	}

	/**
	 * Add Tracking Code To Footer
	 * @since    1.0.0
	 */
	public function ega_add_analytics_code() {

		$options = get_option( 'ega_options' );
		$ua_id = $options['ega_ua_code'];

		//Get current website url and format it to work with the google api
		$home_url = get_home_url();
		$find = array( 'http://', 'https://', 'www.');
		$replace = '';
		$output = str_replace( $find, $replace, $home_url );

		if($ua_id !== '') {
			echo "
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', '".$ua_id."', '".$output."');
ga('send', 'pageview');
</script>";

		}

	}

	/**
	 * Localization
	 */
	public function ega_load_plugin_textdomain() {
		load_plugin_textdomain( 'easy-peasy-google-analytics', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

}

$GLOBALS['easy_google_analytics'] = new Easy_Google_Analytics();