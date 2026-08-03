<?php

/*
Plugin Name:  Sentry
Description:  Initializes the Sentry PHP SDK for error tracking. No-op if SENTRY_DSN is unset (e.g. local dev).
Version:      1.0.0
*/

use function Env\env;

if (! env('SENTRY_DSN')) {
    return;
}

\Sentry\init([
    'dsn' => env('SENTRY_DSN'),
    'environment' => defined('WP_ENV') ? WP_ENV : 'production',
]);
