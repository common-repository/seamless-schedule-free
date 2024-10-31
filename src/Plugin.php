<?php

namespace SeamlessSchedule;

class Plugin {

    private $printed_settings = array();
    private $category_cache_manager;

    static function register(){
        $plugin = new Plugin();
        $plugin->register_hooks();
        $plugin->init_scheduler();

        if( strpos( $_SERVER['REQUEST_URI'], 'wp-admin' ) === false ){
            $plugin->exclude_expired_categories();
        }
    }

    protected function register_hooks(): void {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'init', array( $this, 'save_category_meta' ), 10 );

        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        add_action( 'category_edit_form', array( $this, 'print_category_field' ), 999, 2 );
        if( strpos( $_SERVER['REQUEST_URI'], 'wp-admin' ) === false ){
            add_filter( 'the_posts', array( $this, 'filter_category_posts' ), 999, 2 );
        }
        add_action( 'admin_menu', array( $this, 'add_meta_boxes' ) );
        add_action( 'admin_bar_menu', array( $this, 'add_top_button' ), 999, 1 );
        add_action( 'admin_print_footer_scripts', array( $this, 'print_footer_script' ) );

        add_action( 'save_post', array( $this, 'save_post_meta' ) );
    }

    public function init(): void {
        global $wpdb;
        $this->category_cache_manager = CategoryCacheManager::create( new CategoryManager( $wpdb ) );
    }


    public function enqueue_scripts(): void {
        wp_enqueue_style( 'seamless_datetime_css', plugin_dir_url( __FILE__ ) . 'assets/jquery.datetimepicker.min.css', array(), '1.0.0' );
        wp_enqueue_script( 'seamless_datetime_js', plugin_dir_url( __FILE__ ) . 'assets/jquery.datetimepicker.full.min.js', array( 'jquery' ), '1.0.0' );
        wp_enqueue_script( 'seamless_main_js', plugin_dir_url( __FILE__ ) . 'assets/main.js', array( 'seamless_datetime_js' ), '1.0.1' );
    }

    public function print_category_field( \WP_Term $category ): void {
        $expiration_time = get_term_meta( $category->term_id, SEAMLESS_EXPIRATION_TIME_META, true );
        $schedule_time = get_term_meta( $category->term_id, SEAMLESS_SCHEDULE_TIME_META, true ); 
    ?>
        <table class="form-table" role="presentation">
            <tr class="form-field term-expiration-wrap">
                <th scope="row"><label for="expiration_time">Expire at</th>
                <td>
                    <div class="acf-date-time-picker acf-input-wrap">
                        <input type="text" style="width: 100%" class="seamless_datetime" value="<?php echo esc_html( $expiration_time ); ?>" id="<?php echo SEAMLESS_EXPIRATION_TIME_META; ?>"  name="<?php echo SEAMLESS_EXPIRATION_TIME_META; ?>">
                    </div>
                    <p class="description">Time when the category will be hidden</p>
                </td>
            </tr>
            <tr class="form-field term-schedule-wrap">
                <th scope="row"><label for="schedule_time">Show after</th>
                <td>
                    <div class="acf-date-time-picker acf-input-wrap">
                        <input type="text" style="width: 100%" class="seamless_datetime" value="<?php echo esc_html( $schedule_time ); ?>" id="<?php echo SEAMLESS_SCHEDULE_TIME_META; ?>"  name="<?php echo SEAMLESS_SCHEDULE_TIME_META; ?>">
                    </div>
                    <p class="description">Time when the category will be shown</p>
                </td>
            </tr>
        </table>
<?php        
    }

    public function filter_category_posts( array $posts, \WP_Query $query ): array {
        if( ! $this->category_cache_manager ) $this->init();

        $excluded_categories = $this->category_cache_manager->get_hidden_categories();
        $output = array();
        $term_query = new \WP_Term_Query();
        foreach( $posts as $post ){
            $post_categories = $term_query->query( array( 'fields' => 'ids', 'object_ids' => $post->ID ) );
            $excluded = false;
            foreach( $post_categories as $category ){
                if( in_array( $category, $excluded_categories ) ){
                    $excluded = true;
                    break;
                }
            }
            if( ! $excluded ){
                $output[] = $post;
            }
        }
        return $output;
    }

    public function save_category_meta(): void {
        if( isset( $_POST['action'] ) && $_POST['action'] === 'editedtag' ){
            $term_id = (int) $_POST['tag_ID'];
            if( empty( $_POST[SEAMLESS_EXPIRATION_TIME_META] ) ){
                delete_term_meta( 
                    $term_id, 
                    SEAMLESS_EXPIRATION_TIME_META 
                );
            } else {
                $expiration_time_str = sanitize_text_field( $_POST[SEAMLESS_EXPIRATION_TIME_META] );
                if( date_create_from_format( 'Y-m-d h:i:s', $expiration_time_str ) ){
                    update_term_meta( 
                        $term_id, 
                        SEAMLESS_EXPIRATION_TIME_META, 
                        $expiration_time_str
                    );
                }
            }
            if( empty( $_POST[SEAMLESS_SCHEDULE_TIME_META] ) ){
                delete_term_meta( 
                    $term_id,
                    SEAMLESS_SCHEDULE_TIME_META
                );
            } else {
                $schedule_time_str = sanitize_text_field( $_POST[SEAMLESS_SCHEDULE_TIME_META] );
                $now = date_create();
                if( date_create_from_format( 'Y-m-d h:i:s', $schedule_time_str ) > $now ){
                    update_term_meta(
                        $term_id,
                        SEAMLESS_SCHEDULE_TIME_META,
                        $schedule_time_str
                    );
                }
            }
        }
    }

    public function add_meta_boxes(): void {
        $types = get_post_types( array( 'public' => 1 ) );
        foreach( $types as $type ){
            add_meta_box( 
                SEAMLESS_EXPIRATION_TIME_META, 
                'Expiration time', 
                array( $this, 'print_post_meta_box' ), 
                $type, 'side', 'core', null );
        }
    }

    public function print_post_meta_box( \WP_Post $post ): void {
        $expiration_time = get_post_meta( $post->ID, SEAMLESS_EXPIRATION_TIME_META, true ); 
        ?>
        <div class="acf-date-time-picker acf-input-wrap">
            <input type="text" class="seamless_datetime" name="<?php echo SEAMLESS_EXPIRATION_TIME_META; ?>" style="width: 100%" value="<?php echo esc_html( $expiration_time ); ?>">
        </div>
<?php        
    }

    public function add_top_button( $wp_admin_bar ): void {
        $wp_admin_bar->add_node( array(
            'id' => 'seamless_flush_cache',
            'title' => 'MS Purge cache',
            'href'  => admin_url('admin-post.php?action=seamless_clean_cache')
        ) );
    }

    public function print_footer_script(): void {?>
    <script>
        jQuery('#wp-admin-bar-seamless_flush_cache a').on('click', function(e){
            e.preventDefault();
            const link = jQuery(this).attr('href');
            jQuery.ajax(link, {
                method: 'POST',
                complete: function(){
                    alert('MS Cache is updated');
                }
            });
        });
    </script>
<?php
    }

    public function save_post_meta( int $post_id ): int {

        if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
		    return $post_id;

        if ( !current_user_can( 'edit_post', $post_id ) )
            return $post_id;

        $post_type = get_post_type( $post_id );
        if( $post_type !== 'acf-field' && $post_type !== 'acf-field-group' )
        {
            if( empty( $_POST[SEAMLESS_EXPIRATION_TIME_META] ) ){
                global $wpdb;
                $post_manager = new PostManager( $wpdb );
                $post_manager->publish_if_expiration_canceled( $post_id, SEAMLESS_EXPIRATION_TIME_META );
                delete_post_meta( $post_id, SEAMLESS_EXPIRATION_TIME_META );
            } else {
                $expiration_time_str = sanitize_text_field( $_POST[SEAMLESS_EXPIRATION_TIME_META] );
                if( date_create_from_format( 'Y-m-d h:i:s', $expiration_time_str ) ){
                    update_post_meta(
                        $post_id, 
                        SEAMLESS_EXPIRATION_TIME_META, 
                        $expiration_time_str 
                    );
                }
            }
            
        }
        return $post_id;
    } 

    public function init_scheduler(): void {
        $runner = new TaskRunner();
        
        $runner->add_task( array( $this, 'clean_expired_posts' ) );
        $runner->add_task( array( $this, 'hide_expired_categories' ) );

        $scheduler = new TaskScheduler( $runner );
        $scheduler->init();
    }

    public function clean_expired_posts(): void {
        $current_time = date( 'Y-m-d H:i:s' );
        Logger::debug( 'Start cleaning ' . $current_time );
        Logger::debug( 'Cleaning expired posts...' );
        global $wpdb;

        $post_manager = new PostManager( $wpdb );
        $row_count = (int) $post_manager->hide_expired_posts( SEAMLESS_EXPIRATION_TIME_META, $current_time );

        Logger::debug( 'Hidden ' . $row_count . ' posts' );
    }

    public function hide_expired_categories(): void {
        $current_time = date( 'Y-m-d H:i:s' );
        $this->category_cache_manager->update( $current_time );
    }
    
    public function exclude_expired_categories(): void {
        add_filter( 'get_terms', array( $this, 'clean_expired_categories' ), 999, 4 );
        add_filter( 'get_term', array( $this, 'filter_term' ), 999, 2 );
    }

    public function clean_expired_categories( array $terms, array $taxonomies, array $args, $query ): array {
        if( $this->category_cache_manager ){
            if( in_array( 'category', $taxonomies ) ){
                $hidden_categories = $this->category_cache_manager->get_hidden_categories();
                foreach( $terms as $num => $term ){
                    if( in_array( $term->term_id, $hidden_categories ) ) {
                        unset( $terms[$num] );
                    }
                }
                return array_values( $terms );
            }
        }
        return $terms;
    }

    public function filter_term( \WP_Term $term, string $taxonomy ) {
        if( $this->category_cache_manager ){
            $filtered = $this->clean_expired_categories( array( $term ), array( $taxonomy ), array(), null );
            return $filtered ? $term : null;
        }
        return $term;
    }
}