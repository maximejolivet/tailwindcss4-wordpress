<?php
/**
 * Site class
 *
 * Theme bootstrap: template supports, navigation menus and Timber context.
 */

namespace App\Theme;

use Timber\Site as TimberSite;
use Timber\Timber;

class Site extends TimberSite
{
    public function __construct()
    {
        add_action('after_setup_theme', [$this, 'theme_supports']);
        add_filter('timber/context', [$this, 'add_to_context']);

        parent::__construct();
    }

    public function theme_supports(): void
    {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('automatic-feed-links');
        add_theme_support('editor-styles');
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
        ]);

        register_nav_menus([
            'primary' => __('Primary Menu', 'tailwind'),
        ]);
    }

    public function add_to_context(array $context): array
    {
        $context['site'] = $this;
        $context['menu'] = Timber::get_menu('primary');

        return $context;
    }
}
