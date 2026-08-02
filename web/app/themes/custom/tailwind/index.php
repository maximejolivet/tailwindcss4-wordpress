<?php

/**
 * The main template file
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 */

namespace App\Theme;

use Timber\Timber;

$templates = ['templates/index.twig'];

if (is_front_page()) {
    array_unshift($templates, 'templates/front-page.twig');
}

$context = Timber::context();
$context['posts'] = Timber::get_posts();

Timber::render($templates, $context);
