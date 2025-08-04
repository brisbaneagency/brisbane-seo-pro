<?php

add_action('admin_post_bsp_import_csv', function() {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    echo '<pre>';
    echo "START DEBUG\n\n";

    if (!current_user_can('manage_options')) {
        die('Unauthorized user');
    }

    check_admin_referer('bsp_import_csv');

    echo "FILES:\n";
    print_r($_FILES);

    if (!isset($_FILES['bsp_csv_file'])) {
        die('No file uploaded.');
    }

    if ($_FILES['bsp_csv_file']['error'] !== UPLOAD_ERR_OK) {
        die('Upload error code: ' . $_FILES['bsp_csv_file']['error']);
    }

    $file = $_FILES['bsp_csv_file']['tmp_name'];
    if (!file_exists($file)) {
        die('Uploaded file does not exist.');
    }

    echo "\nFile uploaded successfully: {$file}\n\n";

    $handle = fopen($file, 'r');
    if (!$handle) {
        die('Cannot open file.');
    }

    $header = fgetcsv($handle, 0, ',');
    if (!$header) {
        die('Cannot read header row.');
    }

    echo "HEADER:\n";
    print_r($header);

    while (($row = fgetcsv($handle, 0, ',')) !== false) {
        echo "ROW:\n";
        print_r($row);

        if (!$row) {
            echo "Empty row.\n";
            continue;
        }

        $data = array_combine($header, $row);
        if (!$data) {
            echo "array_combine() failed.\n";
            continue;
        }

        print_r($data);

        if (!isset($data['ID'])) {
            echo "Skipped row without ID.\n";
            continue;
        }

        $post_id = (int)$data['ID'];
        if (get_post_status($post_id) === false) {
            echo "Skipped ID {$post_id}: not found.\n";
            continue;
        }

        echo "Updating post ID {$post_id}\n";

        foreach ($data as $meta_key => $value) {
            if (in_array($meta_key, ['ID', 'SKU', 'Title', 'post_type'])) {
                continue;
            }
            update_post_meta($post_id, $meta_key, $value);
            echo "Updated {$meta_key}\n";
        }
    }

    fclose($handle);

    echo "\n✅ Import completed without fatal error.\n";
    exit;
});
