<?php

/**
 * Functions and definitions
 *
 * @link https://timber.github.io/docs/v2/
 */

namespace App\Theme;

use Timber\Timber;

Timber::init();

new Site();

require __DIR__ . '/inc/vite.php';
require __DIR__ . '/inc/acf-fields.php';
