<?php

add_action('admin_menu', function() {
    add_options_page(
        'Brisbane SEO Settings',
        'Brisbane SEO',
        'manage_options',
        'brisbane-seo-pro-settings',
        'brisbane_seo_pro_settings_page'
    );
});

function brisbane_seo_pro_settings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    $nonce_action = 'bsp_settings_form_nonce';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        check_admin_referer($nonce_action);

        if (isset($_POST['bsp_save_settings'])) {
            $enabled = isset($_POST['bsp_sitemap_enabled']) ? array_map('sanitize_text_field', $_POST['bsp_sitemap_enabled']) : [];
            update_option('bsp_sitemap_enabled', $enabled);

            if (isset($_POST['bsp_fallback_image_url'])) {
                update_option('bsp_fallback_image_url', esc_url_raw($_POST['bsp_fallback_image_url']));
            }

            update_option('bsp_enable_gravityforms_cleanup', isset($_POST['bsp_enable_gravityforms_cleanup']) ? '1' : '0');

            if (!empty($enabled)) {
                foreach ($enabled as $key) {
                    $title_field = 'bsp_meta_rush_title_' . $key;
                    $desc_field = 'bsp_meta_rush_description_' . $key;

                    update_option($title_field, sanitize_text_field($_POST[$title_field] ?? ''));
                    update_option($desc_field, sanitize_text_field($_POST[$desc_field] ?? ''));
                }
            }

            update_option('bsp_meta_rush_title_global_unused', sanitize_text_field($_POST['bsp_meta_rush_title_global_unused'] ?? ''));
            update_option('bsp_meta_rush_description_global_unused', sanitize_text_field($_POST['bsp_meta_rush_description_global_unused'] ?? ''));

            flush_rewrite_rules();
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }

        if (isset($_POST['bsp_regenerate_indexnow_key'])) {
            $new_key = bin2hex(random_bytes(16));
            update_option('bsp_indexnow_key', $new_key);
            echo '<div class="updated"><p>IndexNow key regenerated.</p></div>';
        }

        if (isset($_POST['bsp_save_robots'])) {
            $robots_content = isset($_POST['bsp_robots_content']) ? wp_unslash($_POST['bsp_robots_content']) : '';
            $robots_path = ABSPATH . 'robots.txt';
            file_put_contents($robots_path, $robots_content);
            echo '<div class="updated"><p>robots.txt has been saved.</p></div>';
        }
    }

    $enabled = get_option('bsp_sitemap_enabled', []);
    $post_types = get_post_types(['public' => true], 'objects');
    $taxonomies = get_taxonomies(['public' => true], 'objects');
    $indexnow_key = get_option('bsp_indexnow_key');
    $indexnow_url = home_url('/' . $indexnow_key . '.txt');
    $fallback_image_url = get_option('bsp_fallback_image_url', '');
    $robots_path = ABSPATH . 'robots.txt';
    $robots_content = file_exists($robots_path) ? file_get_contents($robots_path) : bsp_default_robots_txt('', '');

?>
<div id="bsp">
    <h1 style="margin-bottom:1em;">Brisbane SEO Settings</h1>
    <p class="description">A lightweight, fast performance SEO plugin that uses your custom fields without Yoast bloat.</p>

    <div class="bsp-settings-container" style="display:flex; gap:2em;">

        <nav class="bsp-tabs-nav" aria-label="Brisbane SEO Settings Tabs" style="flex:0 0 220px; display:flex; flex-direction:column; gap:0.5rem;">
            <button class="bsp-tab-btn active" data-tab="settings" aria-selected="true" id="tab-settings" aria-controls="panel-settings">Settings</button>
            <button class="bsp-tab-btn" data-tab="sitemaps" aria-selected="false" id="tab-sitemaps" aria-controls="panel-sitemaps">Sitemaps</button>
            <?php if (!empty($enabled)): ?>
            <button class="bsp-tab-btn" data-tab="meta" aria-selected="false" id="tab-meta" aria-controls="panel-meta">Meta Fields</button>
            <?php endif; ?>
            <button class="bsp-tab-btn" data-tab="indexnow" aria-selected="false" id="tab-indexnow" aria-controls="panel-indexnow">IndexNow</button>
            <button class="bsp-tab-btn" data-tab="redirects" aria-selected="false" id="tab-redirects" aria-controls="panel-redirects">Redirects</button>
            <button class="bsp-tab-btn" data-tab="robots" aria-selected="false" id="tab-robots" aria-controls="panel-robots">Robots.txt</button>
        </nav>

        <form method="post" class="bsp-form" novalidate id="bsp-settings-form" style="flex:1; display:flex; flex-direction:column; min-height:400px;">
            <?php wp_nonce_field($nonce_action); ?>

            <div class="bsp-tab-pane active" id="panel-settings">
                <h2>Global Fallback Featured Image URL</h2>
                <p>
                    <input type="url" name="bsp_fallback_image_url" value="<?php echo esc_attr($fallback_image_url); ?>" placeholder="https://example.com/path/to/image.jpg" />
                </p>
                <p><em>Used as fallback image URL if no featured image is set.</em></p>

                <h2>Gravity Forms Cleanup</h2>
                <p class="description">Remove base64 script tags from Gravity Forms and move inline scripts to footer.</p>
                <table class="bsp-toggle-table">
                    <tr>
                        <td><label for="bsp_enable_gravityforms_cleanup">Enable Gravity Forms Cleanup</label></td>
                        <td style="text-align:right;">
                            <label class="bsp-switch">
                                <input type="checkbox" id="bsp_enable_gravityforms_cleanup" name="bsp_enable_gravityforms_cleanup" value="1" <?php checked(get_option('bsp_enable_gravityforms_cleanup'), '1'); ?> />
                                <span class="bsp-slider"></span>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="bsp-tab-pane" id="panel-sitemaps">
                <h2>Post Types to Include in Sitemap</h2>
                <table class="bsp-toggle-table"><tbody>
                <?php foreach ($post_types as $pt): ?>
                    <tr>
                        <td><?php echo esc_html($pt->labels->singular_name); ?> (<?php echo esc_html($pt->name); ?>)</td>
                        <td style="text-align:right;">
                            <label class="bsp-switch">
                                <input type="checkbox" name="bsp_sitemap_enabled[]" value="<?php echo esc_attr('posttype_' . $pt->name); ?>" <?php checked(in_array('posttype_' . $pt->name, $enabled)); ?> />
                                <span class="bsp-slider"></span>
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table>

                <h2>Taxonomies to Include in Sitemap</h2>
                <table class="bsp-toggle-table"><tbody>
                <?php foreach ($taxonomies as $tax): ?>
                    <tr>
                        <td><?php echo esc_html($tax->labels->singular_name); ?> (<?php echo esc_html($tax->name); ?>)</td>
                        <td style="text-align:right;">
                            <label class="bsp-switch">
                                <input type="checkbox" name="bsp_sitemap_enabled[]" value="<?php echo esc_attr('taxonomy_' . $tax->name); ?>" <?php checked(in_array('taxonomy_' . $tax->name, $enabled)); ?> />
                                <span class="bsp-slider"></span>
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody></table>
            </div>

            <?php if (!empty($enabled)): ?>
            <div class="bsp-tab-pane" id="panel-meta">
                <h2>Global Meta Fields</h2>
                <p class="description">You can use the following shortcodes in your meta fields: <code>%post_title%</code>, <code>%term_title%</code>, <code>%site_name%</code>, <code>%site_url%</code>.</p>

                <table class="bsp-redirects-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($enabled as $key):
                        $raw = '';
                        if (strpos($key, 'posttype_') === 0) {
                            $raw = substr($key, 9);
                        } elseif (strpos($key, 'taxonomy_') === 0) {
                            $raw = substr($key, 9);
                        }
                        $title_field = 'bsp_meta_rush_title_' . $key;
                        $desc_field = 'bsp_meta_rush_description_' . $key;
                    ?>
                        <tr>
                            <td><input type="text" name="<?php echo esc_attr($title_field); ?>" value="<?php echo esc_attr(get_option($title_field,'')); ?>" /></td>
                            <td><input type="text" name="<?php echo esc_attr($desc_field); ?>" value="<?php echo esc_attr(get_option($desc_field,'')); ?>" /></td>
                            <td><code><?php echo esc_html($raw); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                        <tr>
                            <td><input type="text" name="bsp_meta_rush_title_global_unused" value="<?php echo esc_attr(get_option('bsp_meta_rush_title_global_unused','')); ?>" /></td>
                            <td><input type="text" name="bsp_meta_rush_description_global_unused" value="<?php echo esc_attr(get_option('bsp_meta_rush_description_global_unused','')); ?>" /></td>
                            <td><em>Global Unused</em></td>
                        </tr>
                    </tbody>
                </table>
                <div class="bsp-save-wrapper" style="margin-top:1.5em;">
                    <button type="submit" name="bsp_save_settings" class="button-secondary">Save All Meta Fields</button>
                </div>
            </div>
            <?php endif; ?>

            <div class="bsp-tab-pane" id="panel-indexnow">
                <h2>IndexNow Settings</h2>
                <?php if ($indexnow_key): ?>
                <table class="bsp-toggle-table"><tbody>
                    <tr>
                        <td><strong>Current IndexNow Key:</strong></td>
                        <td><code><?php echo esc_html($indexnow_key); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>Verification URL:</strong></td>
                        <td><a href="<?php echo esc_url($indexnow_url); ?>" target="_blank"><?php echo esc_url($indexnow_url); ?></a></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align:right;">
                            <button type="submit" name="bsp_regenerate_indexnow_key" class="button-secondary">Regenerate IndexNow Key</button>
                        </td>
                    </tr>
                </tbody></table>
                <?php else: ?>
                <p>No IndexNow key generated yet.</p>
                <?php endif; ?>
            </div>

            <div class="bsp-tab-pane" id="panel-redirects">
                <?php if (function_exists('bsp_redirects_settings_html')) bsp_redirects_settings_html(); ?>
            </div>

            <div class="bsp-tab-pane" id="panel-robots">
                <h2>Robots.txt Generator</h2>
                <textarea name="bsp_robots_content" rows="20" style="width:100%; font-family:monospace;"><?php echo esc_textarea($robots_content); ?></textarea>
                <p><button type="submit" name="bsp_save_robots" class="button button-secondary">Save Robots.txt</button></p>
            </div>

            <div class="bsp-save-wrapper" style="margin-top:2em;">
                <button type="submit" name="bsp_save_settings" class="button-primary">Save Settings</button>
            </div>
        </form>
    </div>
</div>

<script>
(function($){
    $(document).ready(function(){
        $('.bsp-tab-btn').on('click', function(){
            var tab = $(this).data('tab');
            $('.bsp-tab-btn').removeClass('active').attr('aria-selected','false');
            $(this).addClass('active').attr('aria-selected','true');
            $('.bsp-tab-pane').removeClass('active');
            $('#panel-' + tab).addClass('active');

            const newUrl = new URL(window.location);
            newUrl.searchParams.set('tab', tab);
            window.history.replaceState(null, '', newUrl.toString());
        });

        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        if(tabParam) {
            $('.bsp-tab-btn').removeClass('active').attr('aria-selected','false');
            $('.bsp-tab-pane').removeClass('active');
            $(`.bsp-tab-btn[data-tab="${tabParam}"]`).addClass('active').attr('aria-selected','true');
            $('#panel-' + tabParam).addClass('active');
        }
    });
})(jQuery);
</script>
<?php
}
?>
