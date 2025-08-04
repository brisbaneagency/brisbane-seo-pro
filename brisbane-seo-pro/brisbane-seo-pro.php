<?php
/*
Plugin Name: Brisbane SEO Pro 😎
Plugin URI: https://github.com/brisbaneagency/brisbane-seo-pro/
Description: An ultra-lightweight custom SEO plugin using Rush Custom Fields.
Version: 3.0
Author: Brisbane Agency
Author URI: http://brisbaneagency.com/
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

define('BSP_PATH', plugin_dir_path(__FILE__));

require_once BSP_PATH . 'includes/settings.php';
require_once BSP_PATH . 'includes/sitemap.php';
require_once BSP_PATH . 'includes/meta-box.php';
require_once BSP_PATH . 'includes/meta-output.php';
require_once BSP_PATH . 'includes/removals.php';
require_once BSP_PATH . 'includes/indexnow.php';
require_once BSP_PATH . 'includes/performance.php';
require_once BSP_PATH . 'includes/redirects.php';
require_once BSP_PATH . 'includes/robots.php';
require_once BSP_PATH . 'includes/meta-admin-columns.php';
require_once BSP_PATH . 'includes/3rd-party-plugins.php';

add_filter('wp_sitemaps_enabled','__return_false');

add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'settings_page_brisbane-seo-pro-settings') return;
    wp_enqueue_style('brisbane-seo-admin-style', plugin_dir_url(__FILE__) . 'css/admin-styles.css', [], '1.0');
});

add_filter('plugin_action_links_' . plugin_basename(__FILE__), function($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=brisbane-seo-pro-settings') . '" style="color:#2271b1;font-weight:bold;">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
});

register_activation_hook(__FILE__, function() {
    $role = get_role('administrator');
    if ($role && !$role->has_cap('manage_options')) {
        $role->add_cap('manage_options');
    }
    flush_rewrite_rules();
    if (function_exists('rush_seo_redirects_install')) {
        rush_seo_redirects_install();
    }
});

add_action('after_setup_theme', function() {
    remove_theme_support('title-tag');
});

add_action('template_redirect', function() {
    ob_start(function ($buffer) {
        $own_tag_pattern = preg_quote('<meta name="robots" content="', '/') . '(.*?)' . preg_quote('" />', '/');
        $own_tag_match = [];
        preg_match('/' . $own_tag_pattern . '/i', $buffer, $own_tag_match);
        $own_tag = isset($own_tag_match[0]) ? $own_tag_match[0] : null;

        return preg_replace_callback('/<meta\s+name=["\']robots["\']\s+content=["\'](.*?)["\']\s*\/?>/i', function($matches) use ($own_tag) {
            return ($own_tag && $matches[0] === $own_tag) ? $matches[0] : '';
        }, $buffer);
    });
}, 0);


add_filter('auto_update_plugin', function($update, $item) {
    if ($item->slug === 'brisbane-seo-pro') return true;
    return $update;
}, 20, 2);

add_action('plugins_loaded', function() {
    if (class_exists('WPSEO_Admin_Columns')) {
        global $wpseo_admin_columns;
        if (is_object($wpseo_admin_columns)) {
            remove_filter('manage_edit-post_columns', [$wpseo_admin_columns, 'add_columns']);
            remove_action('manage_post_posts_custom_column', [$wpseo_admin_columns, 'render_column']);
            remove_filter('manage_edit-post_sortable_columns', [$wpseo_admin_columns, 'make_columns_sortable']);
            $post_types = get_post_types(['public' => true], 'names');
            foreach ($post_types as $type) {
                remove_filter("manage_edit-{$type}_columns", [$wpseo_admin_columns, 'add_columns']);
                remove_action("manage_{$type}_posts_custom_column", [$wpseo_admin_columns, 'render_column']);
                remove_filter("manage_edit-{$type}_sortable_columns", [$wpseo_admin_columns, 'make_columns_sortable']);
            }
        }
    }
});

require_once plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

$updateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://github.com/brisbaneagency/brisbane-seo-pro/',
    __FILE__,
    'brisbane-seo-pro'
);

$updateChecker->getVcs()->enableReleaseAssets();
