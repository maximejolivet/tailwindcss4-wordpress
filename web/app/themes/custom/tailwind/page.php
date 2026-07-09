<?php
/**
 * The template for displaying all pages
 */

namespace App\Theme;

use Timber\Timber;

$context = Timber::context();
$post = $context['post'];

Timber::render(['templates/page-' . $post->post_name . '.twig', 'templates/page.twig'], $context);
