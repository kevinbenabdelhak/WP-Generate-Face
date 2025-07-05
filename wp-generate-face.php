<?php
/*
Plugin Name: WP Generate Face
Plugin URI: https://kevin-benabdelhak.fr/plugins/wp-generate-face/
Description: WP Generate Face est un plugin qui permet de générer des visages aléatoires depuis cettepersonnenexistepas.fr et l'enregistrer dans les médias
Version: 1.1
Author: Kevin Benabdelhak
Author URI: https://kevin-benabdelhak.fr/
Contributors: kevinbenabdelhak
*/

if (!defined('ABSPATH')) exit;



if ( !class_exists( 'YahnisElsts\\PluginUpdateChecker\\v5\\PucFactory' ) ) {
    require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';
}
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$monUpdateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/kevinbenabdelhak/WP-Generate-Face/', 
    __FILE__,
    'wp-generate-face' 
);
$monUpdateChecker->setBranch('main');











add_action('media_upload_upload', 'wgf_enqueue_media_button');
add_action('admin_enqueue_scripts', 'wgf_enqueue_js');

function wgf_enqueue_js($hook) {
    if ($hook === 'upload.php') {
        wp_enqueue_script(
            'wgf-script',
            plugins_url('wgf-script.js', __FILE__ ),
            array('jquery'),
            null,
            true
        );
        wp_localize_script(
            'wgf-script',
            'wgf_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('wgf_generate_face_nonce'),
            )
        );
    }
}

add_action('pre-upload-ui', 'wgf_generate_face_button');
function wgf_generate_face_button() {
    echo '<input id="wgf-image-count" type="number" min="1" value="1" style="width: 60px; margin-right: 10px;margin-top:5px;" />';
    echo '<button id="wgf-generate-face" type="button" class="button" style="margin:5px 0 15px">'.__('Générer un visage', 'wp-generate-face').'</button>';
    echo '<div id="wgf-face-message" style="margin-bottom:10px"></div>';
}

// Traitement AJAX côté serveur : génère 1 image à la fois
add_action('wp_ajax_wgf_generate_face', 'wgf_generate_face_callback');
function wgf_generate_face_callback() {
    check_ajax_referer('wgf_generate_face_nonce', 'nonce');

    // générer une nouvelle image via l'API
    $api_url = 'https://cettepersonnenexistepas.fr/api/image/v1/download';
    $response = wp_remote_post($api_url, array('timeout' => 20));
    if (is_wp_error($response)) {
        wp_send_json_error('Erreur lors de la connexion à l\'API');
    }

    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body);
    if (empty($json->url_img)) {
        wp_send_json_error('Erreur API : pas d\'image générée');
    }

    $image_url = esc_url_raw($json->url_img);
    $image_data = wp_remote_get($image_url);

    if (is_wp_error($image_data)) {
        wp_send_json_error('Erreur lors du téléchargement de l\'image');
    }

    $image_content = wp_remote_retrieve_body($image_data);
    $filename = basename(parse_url($image_url, PHP_URL_PATH));

    $upload_file = wp_upload_bits($filename, null, $image_content);
    if ($upload_file['error']) {
        wp_send_json_error('Erreur à l\'upload : '. $upload_file['error']);
    }

    $wp_filetype = wp_check_filetype($filename, null);
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name($filename),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $upload_file['file']);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $upload_file['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);

    $image_src = wp_get_attachment_url($attach_id);


    wp_send_json_success(array(
        'message'   => 'Visage généré avec succès !',
        'attach_id' => $attach_id,
        'image_url' => $image_src,
    ));
}