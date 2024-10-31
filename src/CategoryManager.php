<?php

namespace SeamlessSchedule;

class CategoryManager {
    /**
     * @var \WPDB
     */
    private $db;

    public function __construct( \WPDB $db ) {
        $this->db = $db;
    }

    public function get_expired_terms( string $field, string $current_time ): array {
        $terseamless_meta_table = $this->db->termmeta;
        $ids = $this->db->get_results( "SELECT term_id FROM $terseamless_meta_table
            WHERE meta_key='$field' AND meta_value <= '$current_time' AND meta_value <> ''", ARRAY_A );
        if( $ids ){
            return array_map( function( $row ){ return (int) $row['term_id']; }, $ids );
        }
        return array();
    }

    public function get_scheduled_terms( string $field, string $current_time ): array {
        $terseamless_meta_table = $this->db->termmeta;
        $ids = $this->db->get_results( "SELECT term_id FROM $terseamless_meta_table
            WHERE meta_key='$field' AND meta_value >= '$current_time'", ARRAY_A );
        if( $ids ){
            return array_map( function( $row ){ return (int) $row['term_id']; }, $ids );
        }
        return array();
    }
}