<?php
use DI\Container;
use Slim\Factory\AppFactory;

require_once '../src/Helpers/Utils.php';
require __DIR__ . '/../vendor/autoload.php';
$settings = require __DIR__ . '/../config/settings.php';

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$dependencies = require __DIR__ . '/../src/dependencies.php';
$dependencies($app);

$middleware = require __DIR__ . '/../src/middleware.php';
$middleware($app);

$routes = require __DIR__ .'/../src/routes.php';
$routes($app);

$app->run();