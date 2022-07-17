<?php
use SDK\AutoLoader;
use SDK\sdkprojet;

require 'class/AutoLoader.php';

AutoLoader::init();
$CONNECT = new sdkprojet();

$route = $_SERVER["REQUEST_URI"];
switch (strtok($route, "?")) {
    case '/login':
        $CONNECT->login();
        break;
    case '/oauth_success':
        $CONNECT->callback();
        break;
    case '/fb_oauth_success':
        $CONNECT->app_callback("fb");
        break;
    case '/success_url_redirection':
        $CONNECT->app_callback("discord");
        break;
    case '/twitter_oauth_success':
        $CONNECT->app_callback("twitch");
        break;
    default:
        http_response_code(404);
}
