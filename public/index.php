<?php
require_once '../vendor/autoload.php';
require_once '../config/constants.php';

use TmdbProxy\Boot\App;

$app = new App();
$app->init()
    ->loadRoutes()
    ->start();


