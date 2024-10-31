<?php

namespace SeamlessSchedule;

class PostManager {

    /**
     * @var \WPDB
     */
    private $db;

    public function __construct( \WPDB $db ) {
        $this->db = $db;
    }

    public function hide_expired_posts( string $field, string $current_time ) {
        $meta_table = $this->db->postmeta;
        $posts_table = $this->db->posts;
        return $this->db->query( 
            "UPDATE $posts_table INNER JOIN $meta_table ON post_id=ID SET post_status='draft' 
             WHERE meta_key='$field' AND meta_value <= '$current_time' AND meta_value <> '' AND post_status = 'publish'"
        );
    }

    public function publish_if_expiration_canceled( int $post_id, string $field_name ): void {
        $meta_table = $this->db->postmeta;
        $posts_table = $this->db->posts;
        $this->db->query( 
            "UPDATE $posts_table INNER JOIN $meta_table ON post_id=ID SET post_status='publish'
             WHERE post_id=$post_id AND meta_key='$field_name'" );
    }
}