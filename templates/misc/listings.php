<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo WP_CarDealer_Template_Loader::get_template_part('loop/listing/archive-inner', array('listings' => $listings));
