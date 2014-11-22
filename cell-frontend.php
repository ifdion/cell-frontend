<?php
/*
Plugin Name: Cell Frontend
Plugin URI: http://google.com
Description: Add front end registration, profile editing
Version: 0.0.1
Author: Dion
Author URI: http://google.com
Author Email: ifdion@gmail.com
License:

	Copyright 2013 (ifdion@gmail.com)

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

//set constant values
define( 'CELL_FRONTEND_FILE', __FILE__ );
define( 'CELL_FRONTEND', dirname( __FILE__ ) );
define( 'CELL_FRONTEND_PATH', plugin_dir_path(__FILE__) );
define( 'CELL_FRONTEND_TEXT_DOMAIN', 'cell-frontend' );

// set for internationalization
function cell_frontend_init() {
	load_plugin_textdomain('cell-frontend', false, basename( dirname( __FILE__ ) ) . '/lang' );
}
add_action('plugins_loaded', 'cell_frontend_init');


/* session
---------------------------------------------------------------
*/

	if (!session_id()) {
		session_start();
	}

/* global 
---------------------------------------------------------------
*/

	// include_once ('settings.php');

	// include_once ('common-functions.php');

	include_once ('frontend/frontend.php');

