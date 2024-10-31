<?php
/**
 * Plugin Name:       Seamless Schedule Free
 * Plugin URI:        https://seamlessplugins.com/
 * Description:       The plugin is designed to schedule & expire posts, pages, and custom post types on any WordPress site.
 * Version:           1.0
 * **/
define( 'SEAMLESS_EXPIRATION_TIME_META', 'seamless_expiration_time' );
define( 'SEAMLESS_SCHEDULE_TIME_META', 'seamless_appearance_time' );
define( 'SEAMLESS_FIELD_EXPIRATION_TIME_META', 'seamless_field_expiration_time' );
define( 'SEAMLESS_FIELD_SCHEDULE_TIME_META', 'seamless_field_appearance_time' );

define( 'SEAMLESS_DEBUG', false );

require_once plugin_dir_path( __FILE__ ) . 'src/Logger.php';
require_once plugin_dir_path( __FILE__ ) . 'src/PostManager.php';
require_once plugin_dir_path( __FILE__ ) . 'src/CategoryManager.php';
require_once plugin_dir_path( __FILE__ ) . 'src/CategoryCacheManager.php';
require_once plugin_dir_path( __FILE__ ) . 'src/TaskRunner.php';
require_once plugin_dir_path( __FILE__ ) . 'src/TaskScheduler.php';
require_once plugin_dir_path( __FILE__ ) . 'src/Plugin.php';

SeamlessSchedule\Plugin::register();

add_action( 'admin_post_seamless_clean_cache', function() {
    do_action( SeamlessSchedule\TaskScheduler::SCHEDULER_ACTION );
} );

register_activation_hook( __FILE__, function(){
    SeamlessSchedule\TaskScheduler::register();
} );

register_deactivation_hook( __FILE__, function(){
    SeamlessSchedule\TaskScheduler::deactivate();
} );
