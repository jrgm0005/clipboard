<?php

include_once("Helper.php");
$route  = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$route = substr($route, 1);
$route = explode("?", $route);
$route = explode("/", $route[0]);

// Remove the project url in localhost.
if ($route[0] == 'clipboard' || $route[0] == 'clip') {
    unset($route[0]);
}

$route = array_diff($route, array('API_Restful', 'api'));
$route = array_values($route);

$arr_json = null;

Helper::saveInLog("POST Request", $_POST);
$post = json_decode(file_get_contents('php://input'), true);

Helper::saveInLog("Route", $route);

if (count($route) <= 2) {
    switch ($route[0]) {
        case 'boards':
            include_once('Board.php');
            $board = new Board();
            $arr_json = $board->verifyMethod($method, $route);
            break;

        default:
            $arr_json = array('status' => 404);
            break;
    }
} else {
    $arr_json = array('status' => 404);
}

echo json_encode($arr_json);
