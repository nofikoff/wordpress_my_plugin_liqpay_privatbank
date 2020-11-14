<?php
if($_SERVER && isset($_SERVER['DOCUMENT_ROOT'])) {
    $root_dir = $_SERVER['DOCUMENT_ROOT'];
} else {
    $_dir = dirname(__FILE__);
    list($root_dir) = explode('/wp-content', $_dir);
}

require_once $root_dir.'/wp-load.php';

if(!$_POST && (!isset($_POST['data']) || !is_string($_POST['data']) ) && (!isset($_POST['signature']) || !is_string($_POST['signature']))) {
    wp_safe_redirect('/');
}

$p_key = ELiq_Pay::get('private_key');
$signature = base64_encode( sha1($p_key.$_POST['data'].$p_key, 1 ));
if($signature !== $_POST['signature']) {
    wp_safe_redirect('/');
}

$data = base64_decode($_POST['data']);

$do_process = apply_filters('eliqpay_callback_data', true, $data['info'], $data);

if($do_process) {
    #ELiq_Pay_Request::saveAnswer($data['info'], $data);
}

exit;