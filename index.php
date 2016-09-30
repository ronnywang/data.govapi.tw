<?php

function error($message) {
    echo json_encode(array('error' => true, 'message' => $message));
    exit;
}

// data.gov.tw/_static/...
if (strpos($_SERVER['REQUEST_URI'], '/_static/')) {
    if (strpos($_SERVER['REQUEST_URI'], '/..')) {
        header('404 Not Found', true, 404);
        echo 'bad url';
        exit;
    }
    $file = __DIR__ . $_SERVER['REQUEST_URI'];
    if (!file_exists($file) or !is_file($file)) {
        header('404 Not Found', true, 404);
        echo '404';
        exit;
    }
    readfile($file);
    exit;
}

// data.gov.tw
if ($_SERVER['REQUEST_URI'] == '/') {
    include('mainpage.php');
    exit;
}

// data.gov.tw/node/12345
if (preg_match('#/node/([0-9]*)#', $_SERVER['REQUEST_URI'], $matches)) {
    $node_id = $matches[1];
    include('node.php');
    exit;
}

header('404 Not Found', true, 404);
echo 404;
exit;
