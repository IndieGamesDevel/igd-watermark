<?php
/**
 * Plugin Name: IGD Watermark
 * Plugin URI: https://indiegamesdevel.com/
 * Description: Adds a button to watermark a copy of uploaded image in WordPress media gallery.
 * Version: 1.1.12
 * Author: Carlo Cancelloni
 * Author URI: https://indiegamesdevel.com/
 * License: GPL2
 */

function igd_watermark_admin_enqueue_scripts($hook) {
	if ('upload.php' !== $hook) {
		return;
	}

	// Enqueue del file JavaScript
	wp_register_script('igd-watermark', plugin_dir_url(__FILE__) . 'js/igd-watermark.js', array('jquery'), '1.3.5', true);
	wp_enqueue_script('igd-watermark');

	// Passa i dati al file JavaScript
	wp_localize_script('igd-watermark', 'igd_watermark', array('ajax_url' => admin_url('admin-ajax.php')));
}

add_action('admin_enqueue_scripts', 'igd_watermark_admin_enqueue_scripts');

function igd_watermark_action_callback() {
	$attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;

	if ($attachment_id === 0) {
		wp_send_json_error('Invalid attachment ID.');
	}

	$watermark_url = plugin_dir_url(__FILE__) . '/images/watermark.png';

	$new_attachment_id = apply_watermark_to_image($attachment_id, $watermark_url);

	$response = array(
		'success' => true,
		'message' => 'Watermark successful applied with image ID: ' . $attachment_id,
		'new_attachment_id' => $new_attachment_id,
	);

	echo json_encode($response);
	wp_die();
}

function apply_watermark_to_image($attachment_id, $watermark_url)
{
	$image_url = wp_get_attachment_url($attachment_id);
	$image_path = get_attached_file($attachment_id);

	$image = imagecreatefromstring(file_get_contents($image_url));

	imagealphablending($image, true);
	imagesavealpha($image, true);
	$image_width = imagesx($image);
	$image_height = imagesy($image);

	$watermark_size = 200;
	$margin = 60;
	if ($image_width < 900 || $image_height < 600)
	{
		$watermark_size = 80;
		$margin = 20;
	}
	else if ($image_width < 1200 || $image_height < 900)
	{
		$watermark_size = 150;
		$margin = 30;
	}

	$watermark = resize_watermark(imagecreatefrompng($watermark_url), $watermark_size, $watermark_size);
	imagealphablending($watermark, true);
	imagesavealpha($watermark, true);

	$dest_x = $image_width - $watermark_size - $margin;
	$dest_y = $image_height - $watermark_size - $margin;

	imagecopyresampled($image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_size, $watermark_size, $watermark_size, $watermark_size);

	$new_image_path = pathinfo($image_path, PATHINFO_DIRNAME) . '/' . pathinfo($image_path, PATHINFO_FILENAME) . '-watermarked.' . pathinfo($image_path, PATHINFO_EXTENSION);
	imagejpeg($image, $new_image_path);

	imagedestroy($image);
	imagedestroy($watermark);

	$new_attachment_id = wp_insert_attachment(array(
		'post_mime_type' => get_post_mime_type($attachment_id),
		'post_title' => get_the_title($attachment_id) . ' - Watermarked',
		'post_content' => '',
		'post_status' => 'inherit',
	), $new_image_path);

	$metadata = wp_generate_attachment_metadata($new_attachment_id, $new_image_path);
	wp_update_attachment_metadata($new_attachment_id, $metadata);

	return $new_attachment_id;
}

function resize_watermark($image, $new_width, $new_height) {
	$resized_image = imagecreatetruecolor($new_width, $new_height);
	imagealphablending($resized_image, false);
	imagesavealpha($resized_image, true);
	imagecopyresampled($resized_image, $image, 0, 0, 0, 0, $new_width, $new_height, imagesx($image), imagesy($image));
	imagedestroy($image);
	return $resized_image;
}

function igd_watermark_admin_init()
{
	add_action('wp_ajax_igd_watermark_action', 'igd_watermark_action_callback');
}
add_action( 'admin_init', 'igd_watermark_admin_init' );

function igd_watermark_activate() {}
register_activation_hook( __FILE__, 'igd_watermark_activate' );


function igd_watermark_deactivate() {}
register_deactivation_hook( __FILE__, 'igd_watermark_deactivate' );
