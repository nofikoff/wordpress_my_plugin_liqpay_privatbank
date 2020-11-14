<?php
if($_SERVER && isset($_SERVER['DOCUMENT_ROOT'])) {
    $root_dir = $_SERVER['DOCUMENT_ROOT'];
} else {
    $_dir = dirname(__FILE__);
    list($root_dir) = explode('/wp-content', $_dir);
}

if(!file_exists($root_dir.'/wp-load.php')) {
    exit;
}

require_once $root_dir.'/wp-load.php';

require_once '../include/SDK/LiqPay.php';

$param = array(
    'version' => 3,
    'action' => 'paydonate',
    'amount' => null,
    'currency' => 'UAH',
    'description' => null,
    'order_id' => null,
    'result_url' => null
    
);

foreach($param as $param_key => &$value) {
    if(!empty($_POST[$param_key])) {
        $value = $_POST[$param_key];
    }
}

$lp = new LiqPay(ELiq_Pay::get('public_key'), ELiq_Pay::get('private_key'));
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Submit donate</title>
        <style>
            body {
                margin: 0;
            }
            form {
                width: 100%;
                height: 100%;
                margin: 0;
                position: absolute;
            }
            form input[type=image] {
                position: absolute;
                left: 50%;
                top: 50%;
                transform: translate(-50%, -50%);
            }
        </style>
    </head>
    <body>
        <?php echo $lp->cnb_form($param); ?>
    </body>
</html>
