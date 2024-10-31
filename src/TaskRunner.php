<?php

namespace SeamlessSchedule;

class TaskRunner {

    private $tasks = array();

    public function add_task( callable $task ): void {
        $this->tasks[] = $task;
    }

    public function execute(): void {
        foreach( $this->tasks as $task ){
            try{
                call_user_func( $task );
            } catch ( \Exception $e ) {
                error_log( $e->getMessage() );
            }
        }
    }
}