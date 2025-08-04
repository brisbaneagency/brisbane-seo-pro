<?php
add_action('add_meta_boxes', function() {
    $post_types = get_post_types(['public' => true], 'names');
    foreach ($post_types as $type) {
        add_meta_box(
            'brisbane_seo_pro',
            'Brisbane SEO Pro',
            'brisbane_seo_pro_box',
            $type,
            'normal',
            'high'
        );
    }
});

function brisbane_seo_pro_box($post) {
    $title = get_post_meta($post->ID, 'rush_seo_title', true);
    $description = get_post_meta($post->ID, 'rush_seo_metadesc', true);
    $canonical = get_post_meta($post->ID, 'rush_seo_canonical', true);
    $noindex = get_post_meta($post->ID, 'rush_seo_noindex', true);

    $page_title = get_the_title($post->ID);
    $site_name = get_bloginfo('name');

    if (!$title) {
        $title = $page_title . ' | ' . $site_name;
    }
    if (!$description) {
        $description = 'Browse ' . $page_title . ' on ' . $site_name . '.';
    }

    wp_nonce_field('brisbane_seo_pro_save', 'brisbane_seo_pro_nonce');
    ?>
    <p><label>Title:</label><br><input type="text" name="bsp_title" value="<?php echo esc_attr($title); ?>" style="width:100%;"></p>
    <p><label>Description:</label><br><textarea name="bsp_description" rows="3" style="width:100%;"><?php echo esc_textarea($description); ?></textarea></p>
    <p><label>Canonical URL:</label><br><input type="text" name="bsp_canonical" value="<?php echo esc_attr($canonical); ?>" style="width:100%;"></p>
    <p><label><input type="checkbox" name="bsp_noindex" value="1" <?php checked($noindex, '1'); ?>> Noindex/Nofollow</label></p>
    <?php
}

add_action('save_post', function($post_id) {
    if (!isset($_POST['brisbane_seo_pro_nonce'])) return;
    if (!wp_verify_nonce($_POST['brisbane_seo_pro_nonce'], 'brisbane_seo_pro_save')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['bsp_title'])) {
        update_post_meta($post_id, 'rush_seo_title', sanitize_text_field($_POST['bsp_title']));
    } else {
        delete_post_meta($post_id, 'rush_seo_title');
    }

    if (isset($_POST['bsp_description'])) {
        update_post_meta($post_id, 'rush_seo_metadesc', sanitize_textarea_field($_POST['bsp_description']));
    } else {
        delete_post_meta($post_id, 'rush_seo_metadesc');
    }

    if (isset($_POST['bsp_canonical'])) {
        update_post_meta($post_id, 'rush_seo_canonical', esc_url_raw($_POST['bsp_canonical']));
    } else {
        delete_post_meta($post_id, 'rush_seo_canonical');
    }

    if (!empty($_POST['bsp_noindex'])) {
        update_post_meta($post_id, 'rush_seo_noindex', '1');
    } else {
        delete_post_meta($post_id, 'rush_seo_noindex');
    }
});

add_action('init', function() {
    $taxonomies = get_taxonomies(['public' => true], 'names');
    foreach ($taxonomies as $tax) {
        add_action("{$tax}_add_form_fields", 'add_term_seo_fields');
        add_action("{$tax}_edit_form_fields", 'edit_term_seo_fields', 10, 2);
        add_action("created_{$tax}", 'save_term_seo_fields', 10, 3);
        add_action("edited_{$tax}", 'save_term_seo_fields', 10, 3);
    }
});

function add_term_seo_fields($taxonomy) {
    ?>
    <div class="form-field term-seo-group">
        <label for="bsp_term_title">SEO Title</label>
        <input name="bsp_term_title" id="bsp_term_title" type="text" value="" />
    </div>
    <div class="form-field term-seo-group">
        <label for="bsp_term_description">Meta Description</label>
        <textarea name="bsp_term_description" id="bsp_term_description" rows="3"></textarea>
    </div>
    <div class="form-field term-seo-group">
        <label for="bsp_term_canonical">Canonical URL</label>
        <input name="bsp_term_canonical" id="bsp_term_canonical" type="text" value="" />
    </div>
    <div class="form-field term-seo-group">
        <label><input name="bsp_term_noindex" id="bsp_term_noindex" type="checkbox" value="1" /> Noindex/Nofollow</label>
    </div>
    <?php
}

function edit_term_seo_fields($term, $taxonomy) {
    $title = get_term_meta($term->term_id, 'rush_seo_title', true);
    $description = get_term_meta($term->term_id, 'rush_seo_metadesc', true);
    $canonical = get_term_meta($term->term_id, 'rush_seo_canonical', true);
    $noindex = get_term_meta($term->term_id, 'rush_seo_noindex', true);
    ?>
    <tr class="form-field term-seo-group-wrap">
        <th scope="row" valign="top"><label for="bsp_term_title">SEO Title</label></th>
        <td><input name="bsp_term_title" id="bsp_term_title" type="text" value="<?php echo esc_attr($title); ?>" /></td>
    </tr>
    <tr class="form-field term-seo-group-wrap">
        <th scope="row" valign="top"><label for="bsp_term_description">Meta Description</label></th>
        <td><textarea name="bsp_term_description" id="bsp_term_description" rows="3"><?php echo esc_textarea($description); ?></textarea></td>
    </tr>
    <tr class="form-field term-seo-group-wrap">
        <th scope="row" valign="top"><label for="bsp_term_canonical">Canonical URL</label></th>
        <td><input name="bsp_term_canonical" id="bsp_term_canonical" type="text" value="<?php echo esc_attr($canonical); ?>" /></td>
    </tr>
    <tr class="form-field term-seo-group-wrap">
        <th scope="row" valign="top"><label for="bsp_term_noindex">Noindex/Nofollow</label></th>
        <td><input name="bsp_term_noindex" id="bsp_term_noindex" type="checkbox" value="1" <?php checked($noindex, '1'); ?> /></td>
    </tr>
    <?php
}

function save_term_seo_fields($term_id, $tt_id, $taxonomy) {
    if (isset($_POST['bsp_term_title'])) {
        update_term_meta($term_id, 'rush_seo_title', sanitize_text_field($_POST['bsp_term_title']));
    } else {
        delete_term_meta($term_id, 'rush_seo_title');
    }

    if (isset($_POST['bsp_term_description'])) {
        update_term_meta($term_id, 'rush_seo_metadesc', sanitize_textarea_field($_POST['bsp_term_description']));
    } else {
        delete_term_meta($term_id, 'rush_seo_metadesc');
    }

    if (isset($_POST['bsp_term_canonical'])) {
        update_term_meta($term_id, 'rush_seo_canonical', esc_url_raw($_POST['bsp_term_canonical']));
    } else {
        delete_term_meta($term_id, 'rush_seo_canonical');
    }

    if (!empty($_POST['bsp_term_noindex'])) {
        update_term_meta($term_id, 'rush_seo_noindex', '1');
    } else {
        delete_term_meta($term_id, 'rush_seo_noindex');
    }
}
?>
