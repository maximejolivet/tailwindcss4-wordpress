<?php
/**
 * The template for displaying the static front page
 */

namespace App\Theme;

use Timber\Timber;

$context = Timber::context();

Timber::render('templates/front-page.twig', $context);
