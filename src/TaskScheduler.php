<?php

namespace SeamlessSchedule;

class TaskScheduler {

    const SCHEDULER_ACTION = "seamless_task_execute";

    /**
     * @var TaskRunner
     */
    private $runner;

    public function __construct( TaskRunner $runner ) {
        $this->runner = $runner;
    }

    public static function register(): void {
        wp_schedule_event( time() + 3600, 4 * 3600, self::SCHEDULER_ACTION );
    }

    public function init(): void {
        add_action( self::SCHEDULER_ACTION, array( $this, 'execute' ) );
    }

    public function execute(): void {
        $this->runner->execute();
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook( self::SCHEDULER_ACTION, array() );
    }
}