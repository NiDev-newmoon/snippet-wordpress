<?php
require_once 'wp-load.php';

$_POST = array(
    'secret_key' => 'VtHXXwI3TXLFWlOINTBvFmAy5Xyt3eta',
    'title' => 'Coloring book test',
    'asin' => 'B00TEST123',
    'description' => 'This is a test product description. It\'s an amazing book that everyone should read!',
    'short_description' => 'A must-read book for all ages.',
    'price' => '19.99',
    'category' => 'Coloring',
    'tags' => 'bestseller,romance,adventure',
    'featured_image' => 'http://azbookland.local/wp-content/uploads/2024/08/AdventStorybook_ChristmasColoringPage_page-0001.jpg',
    'gallery_images' => 'http://azbookland.local/wp-content/uploads/2024/04/4-scaled-2.jpg|http://azbookland.local/wp-content/uploads/2024/04/1-scaled-2.jpg|http://azbookland.local/wp-content/uploads/2024/04/BD-85x110-copy_0009_16-scaled-1.jpg',
    'book_insides_urls' => 'http://azbookland.local/wp-content/uploads/2024/04/4-scaled-2.jpg|http://azbookland.local/wp-content/uploads/2024/04/1-scaled-2.jpg|http://azbookland.local/wp-content/uploads/2024/04/BD-85x110-copy_0009_16-scaled-1.jpg',
    'product_url' => 'https://www.amazon.com/dp/B00TEST123',
    'seo_title' => 'Amazing Book - Must-Read Fiction of the Year',
    'seo_description' => 'Discover the book that\'s captivating readers worldwide. A thrilling adventure that will keep you on the edge of your seat.',
    'seo_permalink' => 'amazing-book-must-read-fiction',
    'seo_focus_keyword' => 'coloring book'
);


if ($_POST['secret_key'] == "VtHXXwI3TXLFWlOINTBvFmAy5Xyt3eta") {
    if (isset($_POST['title'])) {
        // custom permalink
        $title = explode(':', $_POST['title']);
        $slug = sanitize_title($title[0]) . '-' . $_POST['asin'];

        $short_description = "Print Length: " . $_POST['print_length'] . "\n" .
            "Dimensions: " . $_POST['dimensions'];

        // Kiểm tra xem sản phẩm đã tồn tại chưa
        $existing_product = get_page_by_path($slug, OBJECT, 'product');
        
        if ($existing_product) {
            // Sản phẩm đã tồn tại, cập nhật nó
            $product_id = $existing_product->ID;
            $post_data = array(
                'ID' => $product_id,
                'post_title' => wp_specialchars_decode(mb_convert_encoding($_POST['title'], "UTF-8")),
                'post_content' => wp_specialchars_decode(mb_convert_encoding($_POST['description'], "UTF-8")),
                'post_excerpt' => wp_specialchars_decode(mb_convert_encoding($short_description, "UTF-8")),
                'post_name' => $slug,
            );
            wp_update_post($post_data);
        } else {
            // Sản phẩm chưa tồn tại, tạo mới
            $product_id = wp_insert_post(
                array(
                    'post_title' => wp_specialchars_decode(mb_convert_encoding($_POST['title'], "UTF-8")),
                    'post_content' => wp_specialchars_decode(mb_convert_encoding($_POST['description'], "UTF-8")),
                    'post_excerpt' => wp_specialchars_decode(mb_convert_encoding($short_description, "UTF-8")),
                    'post_name' => $slug,
                    'post_type' => 'product',
                    'post_status' => 'publish',
                )
            );
        }
    } else {
        $response = array(
            "status" => "fail",
            "data" => array(
                "woo_url" => "",
                "product_id" => null
            )
        );
        echo json_encode($response);
        exit();
    }


    // Category
    if (isset($_POST['category'])) {
        $categories = explode(',', $_POST['category']);
        wp_set_object_terms($product_id, $categories, 'product_cat');
    }

    // tags
    if (isset($_POST['tags'])) {
        $tags = explode(',', $_POST['tags']);
        wp_set_post_terms($product_id, $tags, 'product_tag');
    }

    if (isset($_POST['featured_image'])) {
        $_knawatfibu_url = array(
            'img_url' => $_POST['featured_image'],
        );
        update_post_meta($product_id, '_knawatfibu_url', $_knawatfibu_url);
    }

    // Book Insides URLs
    if (isset($_POST['book_insides_urls'])) {
        $book_inside_urls = $_POST['book_insides_urls'];
        if (is_string($book_inside_urls)) {
            $book_inside_urls = array_map('trim', explode('|', $_POST['book_insides_urls']));
        }
        update_post_meta($product_id, 'book_insides_urls', $book_inside_urls);
        
    }
   
    if (isset($_POST['gallery_images'])) {
        $gallery_images = $_POST['gallery_images'];
        
        // Nếu là chuỗi, chuyển đổi thành mảng
        if (is_string($gallery_images)) {
            $gallery_images = array_map('trim', explode('|', $gallery_images));
        }
        
        // Đảm bảo $gallery_images là một mảng và loại bỏ các giá trị trống
        $gallery_images = array_filter((array)$gallery_images);
        
        $formatted_gallery = array();
        foreach ($gallery_images as $image_url) {
            $formatted_gallery[] = array('url' => $image_url);
        }
        
        update_post_meta($product_id, '_knawatfibu_wcgallary', $formatted_gallery);
        
        // Log để debug
        error_log('Gallery Images saved: ' . print_r($formatted_gallery, true));
    }
   

    // External/Affiliate product
    wp_set_object_terms($product_id, 'external', 'product_type');

    // price
    if (isset($_POST['price'])) {
        update_post_meta($product_id, '_price', $_POST['price']);
        update_post_meta($product_id, '_regular_price', $_POST['price']);
    }

    // link btn_amz
    if (isset($_POST['asin'])) {
        $link_amz = "https://www.amazon.com/dp/" . $_POST['asin'];
        update_post_meta($product_id, '_product_url', sanitize_text_field($link_amz));
    }
    update_post_meta($product_id, '_button_text', "Buy on Amazon");


    if (isset($_POST['seo_title']) && isset($_POST['seo_description']) && isset($_POST['seo_permalink']) && isset($_POST['seo_focus_keyword'])) {
        update_post_meta($product_id, 'rank_math_title', $_POST['seo_title']);
        update_post_meta($product_id, 'rank_math_description', $_POST['seo_description']);
        update_post_meta($product_id, 'rank_math_permalink', $_POST['seo_permalink']);
        update_post_meta($product_id, 'rank_math_focus_keyword', $_POST['seo_focus_keyword']);
    }
    // response data
    $response = array(
        "status" => "success",
        "data" => array(
            "product_id" => $product_id ,
            "woo_url" => get_permalink($product_id),
            "product_url" => $_POST['product_url'],
        )
    );
    echo json_encode($response, JSON_UNESCAPED_SLASHES);
    exit();
}
?>
