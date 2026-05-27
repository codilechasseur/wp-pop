<?php

class Wp_Pop_I18n {

public function load_plugin_textdomain() {
load_plugin_textdomain(
'wp-pop',
false,
dirname( plugin_basename( WP_POP_PLUGIN_FILE ) ) . '/languages/'
);
}
}
