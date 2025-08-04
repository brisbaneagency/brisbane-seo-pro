<?php
function bsp_replace_template_tags($string, $context = []) {
    $replacements = [
        '%post_title%' => isset($context['post_title']) ? $context['post_title'] : '',
        '%term_title%' => isset($context['term_title']) ? $context['term_title'] : '',
        '%site_name%'  => get_bloginfo('name'),
        '%site_url%'   => home_url('/'),
    ];
    return strtr($string, $replacements);
}


add_action('wp_head', function() {
    $url = home_url('/');
    $lang = strtolower(get_bloginfo('language'));
    echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url($url) . '" />' . "\n";
    echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($url) . '" />' . "\n";
}, 1);



add_action('wp_head', function() {
    $enabled = get_option('bsp_sitemap_enabled', []);
    $home_url = home_url('/');
    $site_name = get_bloginfo('name');
    $global_fallback_image = get_option('bsp_fallback_image_url', '');

    if (is_singular()) {
        global $post;
        $ptype = 'posttype_' . $post->post_type;
        $permalink = get_permalink($post);
        $page_name = get_the_title($post);

        $rush_title = get_post_meta($post->ID, 'rush_seo_title', true);
        $rush_desc = get_post_meta($post->ID, 'rush_seo_metadesc', true);
        $rush_canonical = get_post_meta($post->ID, 'rush_seo_canonical', true);
        $rush_noindex = get_post_meta($post->ID, 'rush_seo_noindex', true);

        $global_rush_title = get_option('bsp_meta_rush_title_' . $ptype, '');
        $global_rush_desc = get_option('bsp_meta_rush_description_' . $ptype, '');
        $global_rush_canonical = get_option('bsp_meta_rush_canonical_' . $ptype, '');
        $global_rush_noindex = get_option('bsp_meta_rush_noindex_' . $ptype, '');

        $global_fallback_title = get_option('bsp_meta_rush_title_global_unused', '');
        $global_fallback_desc = get_option('bsp_meta_rush_description_global_unused', '');

        $title = $rush_title ?: $global_rush_title ?: $global_fallback_title ?: $page_name . ' | ' . $site_name;
        $description = $rush_desc ?: $global_rush_desc ?: $global_fallback_desc ?: 'Browse ' . $page_name . ' on ' . $site_name . '.';
        $canonical = $rush_canonical ?: $global_rush_canonical ?: $permalink;

        if (!in_array($ptype, $enabled)) {
            $robots = ['noindex','nofollow'];
            $canonical = $home_url;
        } else {
            $robots = ($rush_noindex || $global_rush_noindex) ? ['noindex','nofollow'] : ['index','follow','max-snippet:-1','max-video-preview:-1','max-image-preview:large'];
        }

        $title = bsp_replace_template_tags($title, ['post_title' => $page_name]);
        $description = bsp_replace_template_tags($description, ['post_title' => $page_name]);

        $image_url = get_the_post_thumbnail_url($post->ID, 'full') ?: $global_fallback_image;

        echo '<title>' . esc_html($title) . '</title>' . "\n";
        echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
        echo '<link rel="canonical" href="' . esc_url($canonical) . '" />' . "\n";
        echo '<meta name="robots" content="' . implode(', ', $robots) . '" />' . "\n";

        echo '<meta property="og:locale" content="en_US" />' . "\n";
		$og_type = 'article';
		if ($post->post_type === 'page') {
			$og_type = 'website';
		} elseif ($post->post_type === 'product') {
			$og_type = 'product';
		} elseif (post_type_supports($post->post_type, 'custom-fields')) {
			$og_type = $post->post_type;
		}
		echo '<meta property="og:type" content="' . esc_attr($og_type) . '" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($permalink) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";

        if ($image_url) {
            $meta = wp_get_attachment_metadata(get_post_thumbnail_id($post->ID));
            echo '<meta property="og:image" content="' . esc_url($image_url) . '" />' . "\n";
            if (!empty($meta['width'])) echo '<meta property="og:image:width" content="' . intval($meta['width']) . '" />' . "\n";
            if (!empty($meta['height'])) echo '<meta property="og:image:height" content="' . intval($meta['height']) . '" />' . "\n";
        }

        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
        if ($image_url) echo '<meta name="twitter:image" content="' . esc_url($image_url) . '" />' . "\n";
        return;
    }

    if (is_tax() || is_category() || is_tag()) {
        $term = get_queried_object();
        if ($term && isset($term->taxonomy)) {
            $tax = 'taxonomy_' . $term->taxonomy;
            $term_name = $term->name;
            $term_link = get_term_link($term);

            $rush_title = get_term_meta($term->term_id, 'rush_seo_title', true);
            $rush_desc = get_term_meta($term->term_id, 'rush_seo_metadesc', true);
            $rush_canonical = get_term_meta($term->term_id, 'rush_seo_canonical', true);
            $rush_noindex = get_term_meta($term->term_id, 'rush_seo_noindex', true);

            $global_rush_title = get_option('bsp_meta_rush_title_' . $tax, '');
            $global_rush_desc = get_option('bsp_meta_rush_description_' . $tax, '');
            $global_rush_canonical = get_option('bsp_meta_rush_canonical_' . $tax, '');
            $global_rush_noindex = get_option('bsp_meta_rush_noindex_' . $tax, '');

            $global_fallback_title = get_option('bsp_meta_rush_title_global_unused', '');
            $global_fallback_desc = get_option('bsp_meta_rush_description_global_unused', '');

            $title = $rush_title ?: $global_rush_title ?: $global_fallback_title ?: $term_name . ' | ' . $site_name;
            $description = $rush_desc ?: $global_rush_desc ?: $global_fallback_desc ?: 'Browse ' . $term_name . ' on ' . $site_name . '.';
            $canonical = $rush_canonical ?: $global_rush_canonical ?: $term_link;

            if (is_paged()) {
                $canonical = get_pagenum_link(get_query_var('paged'));
            }

            if (!in_array($tax, $enabled)) {
                $robots = ['noindex','nofollow'];
                $canonical = $home_url;
            } else {
                $robots = ($rush_noindex || $global_rush_noindex) ? ['noindex','nofollow'] : ['index','follow','max-snippet:-1','max-video-preview:-1','max-image-preview:large'];
            }

            $title = bsp_replace_template_tags($title, [
                'term_title' => $term_name,
                'post_title' => $term_name
            ]);
            $description = bsp_replace_template_tags($description, [
                'term_title' => $term_name,
                'post_title' => $term_name
            ]);

            $term_image_url = '';
            if (function_exists('get_term_meta')) {
                $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);
                if ($thumbnail_id) {
                    $term_image_url = wp_get_attachment_url($thumbnail_id);
                }
            }
            if (!$term_image_url && $global_fallback_image) {
                $term_image_url = $global_fallback_image;
            }

            echo '<title>' . esc_html($title) . '</title>' . "\n";
            echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
            echo '<link rel="canonical" href="' . esc_url($canonical) . '" />' . "\n";
            echo '<meta name="robots" content="' . implode(', ', $robots) . '" />' . "\n";

            echo '<meta property="og:locale" content="en_US" />' . "\n";
            echo '<meta property="og:type" content="website" />' . "\n";
            echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
            echo '<meta property="og:description" content="' . esc_attr($description) . '" />' . "\n";
            echo '<meta property="og:url" content="' . esc_url($canonical) . '" />' . "\n";
            echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";

            if ($term_image_url) {
                echo '<meta property="og:image" content="' . esc_url($term_image_url) . '" />' . "\n";
            }

            echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
            echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
            echo '<meta name="twitter:description" content="' . esc_attr($description) . '" />' . "\n";
            if ($term_image_url) echo '<meta name="twitter:image" content="' . esc_url($term_image_url) . '" />' . "\n";
            return;
        }
    }

    if (is_post_type_archive()) {
        $post_type = get_post_type();
        $ptype = 'posttype_' . $post_type;
        $archive_title = post_type_archive_title('', false);

        $global_rush_title = get_option('bsp_meta_rush_title_' . $ptype, '');
        $global_rush_desc = get_option('bsp_meta_rush_description_' . $ptype, '');
        $global_rush_canonical = get_option('bsp_meta_rush_canonical_' . $ptype, '');
        $global_rush_noindex = get_option('bsp_meta_rush_noindex_' . $ptype, '');

        $global_fallback_title = get_option('bsp_meta_rush_title_global_unused', '');
        $global_fallback_desc = get_option('bsp_meta_rush_description_global_unused', '');

        $title = $global_rush_title ?: $global_fallback_title ?: $archive_title . ' | ' . $site_name;
        $description = $global_rush_desc ?: $global_fallback_desc ?: 'Browse ' . $archive_title . ' on ' . $site_name . '.';
        $canonical = $global_rush_canonical ?: get_post_type_archive_link($post_type);

        if (is_paged()) {
            $canonical = get_pagenum_link(get_query_var('paged'));
        }

        if (!in_array($ptype, $enabled)) {
            $robots = ['noindex','nofollow'];
            $canonical = $home_url;
        } else {
            $robots = ($global_rush_noindex) ? ['noindex','nofollow'] : ['index','follow','max-snippet:-1','max-video-preview:-1','max-image-preview:large'];
        }

        $title = bsp_replace_template_tags($title, ['post_title' => $archive_title]);
        $description = bsp_replace_template_tags($description, ['post_title' => $archive_title]);

        echo '<title>' . esc_html($title) . '</title>' . "\n";
        echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
        echo '<link rel="canonical" href="' . esc_url($canonical) . '" />' . "\n";
        echo '<meta name="robots" content="' . implode(', ', $robots) . '" />' . "\n";

        echo '<meta property="og:locale" content="en_US" />' . "\n";
        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta property="og:description' . esc_attr($description) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url($canonical) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '" />' . "\n";

        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '" />' . "\n";
        echo '<meta name="twitter:description' . esc_attr($description) . '" />' . "\n";
        return;
    }
}, 1);
?>
