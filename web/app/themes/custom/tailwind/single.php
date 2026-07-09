<?php
/**
 * The template for displaying all single posts
 */

namespace App\Theme;

use Timber\Timber;

$context = Timber::context();
$post = $context['post'];
$templates = ['templates/single-' . $post->post_type . '.twig', 'templates/single.twig'];

Timber::render($templates, $context);
