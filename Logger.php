<?php

class Logger {
    public static function log($message): void {
        error_log($message);
    }
}