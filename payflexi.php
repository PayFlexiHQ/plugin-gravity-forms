<?php
/*
Plugin Name: PayFlexi Installment Payment Plans for Gravity Forms
Plugin URI: https://developers.payflexi.co
Description: PayFlexi payment plans add-on for Gravity Forms is a payment option that lets your customers to spread the amount of payment into several installments.
Version: 1.3.0
Author: PayFlexi
Author URI: https://payflexi.co
License: GPL-2.0+
Text Domain: gravityformspayflexi
Domain Path: /languages

------------------------------------------------------------------------
Copyright 2021 PayFlexi

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
*/

defined('ABSPATH') || die();

define('GF_PAYFLEXI_VERSION', '1.3.0');

add_action('gform_loaded', array('GF_Payflexi_Bootstrap', 'load'), 5);

class GF_Payflexi_Bootstrap
{
	public static function load()
	{
		if (!method_exists('GFForms', 'include_payment_addon_framework')) {
			return;
		}

		require_once('class-gf-payflexi.php');

		require_once('class-gf-payflexi-api.php');

		GFAddOn::register('GFPayflexi');
	}
}

function gf_payflexi()
{
	return GFPayflexi::get_instance();
}
