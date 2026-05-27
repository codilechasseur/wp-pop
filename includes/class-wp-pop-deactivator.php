<?php

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Wp_Pop_Deactivator {

	public static function deactivate() {
		flush_rewrite_rules();
	}
}
