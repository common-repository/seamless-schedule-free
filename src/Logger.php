<?php

namespace SeamlessSchedule;

class Logger {
    const TAG = '[SeamlessSchedule]';

    public static function debug( string $message ): void {
        if( SEAMLESS_DEBUG ){
            error_log( sprintf( '%s[DEBUG]: %s', self::TAG, $message ) );
        }
    }

    public static function error( string $message ): void {
        if( SEAMLESS_DEBUG ){
            error_log( sprintf( '%s[ERROR]: %s', self::TAG, $message ) );
        }
    }
}