<?php

add_action('init', function() {
    add_rewrite_rule('^sitemap_index\.xml$', 'index.php?bsp_sitemap=index', 'top');
    add_rewrite_rule('^([a-z0-9_-]+)-sitemap\.xml$', 'index.php?bsp_sitemap=$matches[1]&bsp_page=1', 'top');
    add_rewrite_rule('^([a-z0-9_-]+)-sitemap-([0-9]+)\.xml$', 'index.php?bsp_sitemap=$matches[1]&bsp_page=$matches[2]', 'top');
    add_rewrite_tag('%bsp_sitemap%', '([^&]+)');
    add_rewrite_tag('%bsp_page%', '([0-9]+)');
});

add_action('template_redirect', function() {
    $sitemap = get_query_var('bsp_sitemap');
    $page = intval(get_query_var('bsp_page'));
    if (!$page || $page < 1) $page = 1;
    if (!$sitemap) return;

    $cached_sitemap = bsp_get_sitemap_cache($sitemap, $page);
    if ($cached_sitemap !== false) {
        header('Content-Type: application/xml; charset=utf-8');
        header('Link: <' . untrailingslashit(home_url()) . '/sitemap_index.xml>; rel="sitemap"');
        echo $cached_sitemap;
        exit;
    }

    ob_start();
    header('Content-Type: application/xml; charset=utf-8');
    header('Link: <' . untrailingslashit(home_url()) . '/sitemap_index.xml>; rel="sitemap"');

    $enabled = get_option('bsp_sitemap_enabled', []);
    $xsl_url = esc_url(rtrim(plugin_dir_url(__DIR__), '/') . '/includes/sitemap.xsl');

    if ($sitemap === 'index') {
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<?xml-stylesheet type="text/xsl" href="' . $xsl_url . '"?>' . "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        $sitemap_entries = [];

        foreach ($enabled as $key) {
            if (strpos($key, 'posttype_') === 0) {
                $post_type = substr($key, 9);
                $count = wp_count_posts($post_type)->publish;
                $pages = max(1, ceil($count / 1000));
                for ($i = 1; $i <= $pages; $i++) {
                    $suffix = $pages > 1 ? "-$i" : "";
                    $loc = untrailingslashit(home_url()) . "/$post_type-sitemap$suffix.xml";
                    $sitemap_entries[] = [
                        'loc' => $loc,
                        'lastmod' => date('c')
                    ];
                }
            }
            if (strpos($key, 'taxonomy_') === 0) {
                $taxonomy = substr($key, 9);
                $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => true, 'fields' => 'ids']);
                $pages = max(1, ceil(count($terms) / 1000));
                for ($i = 1; $i <= $pages; $i++) {
                    $suffix = $pages > 1 ? "-$i" : "";
                    $loc = untrailingslashit(home_url()) . "/$taxonomy-sitemap$suffix.xml";
                    $sitemap_entries[] = [
                        'loc' => $loc,
                        'lastmod' => date('c')
                    ];
                }
            }
        }

        usort($sitemap_entries, function($a, $b) {
            return strcmp($a['loc'], $b['loc']);
        });

        foreach ($sitemap_entries as $entry) {
            echo '  <sitemap>' . "\n";
            echo '    <loc>' . esc_url($entry['loc']) . '</loc>' . "\n";
            echo '    <lastmod>' . $entry['lastmod'] . '</lastmod>' . "\n";
            echo '  </sitemap>' . "\n";
        }

        echo '</sitemapindex>' . "\n";
        $content = ob_get_clean();
        bsp_set_sitemap_cache($content, $sitemap, $page);
        echo $content;
        exit;
    }

    $is_post_type = post_type_exists($sitemap);
    $is_taxonomy = taxonomy_exists($sitemap);

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<?xml-stylesheet type="text/xsl" href="' . $xsl_url . '"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

    $fallback_image = '';

    // First, check the plugin’s custom fallback image
    $global_fallback_image = get_option('bsp_fallback_image_url', '');
    if ($global_fallback_image) {
        $fallback_image = $global_fallback_image;
    } else {
        // Only if no custom fallback is set, use the site icon as a last resort
        $site_icon_id = get_option('site_icon');
        if ($site_icon_id) {
            $fallback_image = wp_get_attachment_url($site_icon_id);
        }
    }

    if ($is_post_type) {
        $args = [
            'post_type'      => $sitemap,
            'post_status'    => 'publish',
            'posts_per_page' => 1000,
            'paged'          => $page,
            'orderby'        => 'none',
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    [
                        'key'     => '_yoast_wpseo_canonical',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key'     => '_yoast_wpseo_canonical',
                        'value'   => '',
                        'compare' => '=',
                    ],
                ],
                [
                    'relation' => 'OR',
                    [
                        'key'     => '_yoast_wpseo_meta-robots-noindex',
                        'compare' => 'NOT EXISTS',
                    ],
                    [
                        'key'     => '_yoast_wpseo_meta-robots-noindex',
                        'value'   => '',
                        'compare' => '=',
                    ],
                ],
            ],
        ];
        $query = new WP_Query($args);

        while ($query->have_posts()) {
            $query->the_post();
            $permalink = get_permalink();
            $lastmod = get_the_modified_date('c');
            $content = get_post_field('post_content', get_the_ID());
            preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);
            $images = $matches[1] ?? [];
            $featured_img = get_the_post_thumbnail_url(get_the_ID(), 'full');
            if ($featured_img && !in_array($featured_img, $images)) {
                array_unshift($images, $featured_img);
            }
            if (empty($images) && $fallback_image) {
                $images[] = $fallback_image;
            }
            echo '  <url>' . "\n";
            echo '    <loc>' . esc_url($permalink) . '</loc>' . "\n";
            echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
            foreach ($images as $img_url) {
                if (strpos($img_url, '/') === 0) {
                    $img_url = untrailingslashit(home_url()) . $img_url;
                }
                echo '    <image:image>' . "\n";
                echo '      <image:loc>' . esc_url($img_url) . '</image:loc>' . "\n";
                echo '    </image:image>' . "\n";
            }
            echo '  </url>' . "\n";
        }
        wp_reset_postdata();
    }

    if ($is_taxonomy) {
        $terms = get_terms([
            'taxonomy'   => $sitemap,
            'hide_empty' => true,
            'number'     => 1000,
            'offset'     => ($page - 1) * 1000,
        ]);

        foreach ($terms as $term) {
            $term_link = get_term_link($term);

            // Get lastmod from latest modified post in the term
            $latest_post = get_posts([
                'post_type'      => 'any',
                'posts_per_page' => 1,
                'tax_query'      => [[
                    'taxonomy' => $sitemap,
                    'field'    => 'term_id',
                    'terms'    => $term->term_id,
                ]],
                'orderby' => 'modified',
                'order'   => 'DESC',
                'fields'  => 'ids',
            ]);
            if (!empty($latest_post)) {
                $lastmod = get_the_modified_date('c', $latest_post[0]);
            } else {
                $lastmod = date('c');
            }

            echo '  <url>' . "\n";
            echo '    <loc>' . esc_url($term_link) . '</loc>' . "\n";
            echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";

            if ($sitemap === 'product_cat' && post_type_exists('product')) {
                $args = [
                    'post_type'      => 'product',
                    'posts_per_page' => 10,
                    'tax_query'      => [[
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $term->term_id,
                    ]],
                    'fields'        => 'ids',
                    'post_status'   => 'publish',
                ];
                $products = get_posts($args);
                foreach ($products as $product_id) {
                    $img_url = get_the_post_thumbnail_url($product_id, 'full');
                    if ($img_url) {
                        echo '    <image:image>' . "\n";
                        echo '      <image:loc>' . esc_url($img_url) . '</image:loc>' . "\n";
                        echo '    </image:image>' . "\n";
                    }
                }
            } elseif ($fallback_image) {
                echo '    <image:image>' . "\n";
                echo '      <image:loc>' . esc_url($fallback_image) . '</image:loc>' . "\n";
                echo '    </image:image>' . "\n";
            }

            echo '  </url>' . "\n";
        }
    }

    echo '</urlset>' . "\n";
    $content = ob_get_clean();
    bsp_set_sitemap_cache($content, $sitemap, $page);
    echo $content;
    exit;
});
