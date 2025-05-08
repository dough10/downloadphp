<?php
use DI\Container;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

session_start();

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