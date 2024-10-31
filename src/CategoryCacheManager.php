<?php

namespace SeamlessSchedule;

class CategoryCacheManager {
    const OPTION_NAME = 'seamless_hidden_categories';

    private $category_mgr;
    private $hidden_categories;

    private function __construct( CategoryManager $mgr ){
        $this->category_mgr = $mgr;
    }

    public function update( $current_time ): void {
        Logger::debug( 'Cleaning expired categories...' );
        $expired_categories = $this->category_mgr->get_expired_terms( SEAMLESS_EXPIRATION_TIME_META, $current_time );
        foreach( $expired_categories as $key => $category_id ){
            $schedule_time = get_term_meta( $category_id, SEAMLESS_SCHEDULE_TIME_META, true );
            if( $schedule_time && date( $schedule_time ) <= $current_time ){
                unset( $expired_categories[$key] );
            }
        }
        $scheduled_categories = $this->category_mgr->get_scheduled_terms( SEAMLESS_SCHEDULE_TIME_META, $current_time );
        $excluded_terms = array_merge( $expired_categories, $scheduled_categories );
        $excluded_terms = array_unique( $excluded_terms );
        update_option( self::OPTION_NAME, $excluded_terms );
        Logger::debug( 'Hidden ' . count( $excluded_terms ) . ' categories' );
    }


    public function load(): void {
        $this->hidden_categories = get_option( self::OPTION_NAME, array() );
    }
    public function get_hidden_categories(): array {
        return $this->hidden_categories;
    }

    public static function create( CategoryManager $mgr ): CategoryCacheManager {
        $cache_mgr = new CategoryCacheManager( $mgr );
        $cache_mgr->load();
        return $cache_mgr;
    }
}