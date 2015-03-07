<?php
/**
 * Plugin Name: Facebook Recent Comments
 * Plugin URI: http://bishoy.me/wp-plugins/facebook-recent-comments/
 * Description: Adds <a href="widgets.php">a widget</a> for recent Facebook comments made using the Facebook comments box. If you like this free plugin, please <a href="http://bishoy.me/donate" target="_blank">consider a donation</a>.
 * Version: 1.1
 * Author: Bishoy A.
 * Author URI: http://bishoy.me
 * License: GPL2
 */

/*  Copyright 2014  Bishoy A.  (email : hi@bishoy.me)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined( 'ABSPATH' ) or die( 'Go away..' );

function frc_no_curl_notice() {
	?>
	<div class="error">
		<p><strong><?php _e( 'Facebook Recent Comments Disabled:' ); ?></strong> <?php _e( 'Curl extension is not found on this server. The Curl extension is a requirement to call the Facebook API.' ); ?></strong></p>
	</div>
	<?php
}

if ( ! function_exists( 'curl_init' ) ) {
	add_action( 'admin_notices', 'frc_no_curl_notice' );
	return false;
}

require_once 'functions.php';
require_once 'widget.php';