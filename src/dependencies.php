<?php
use Slim\App;
use App\Models\Db;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

return function (App $app) {
  $settings = require __DIR__ . '/../config/settings.php';
  $container = $app->getContainer();
  $container->set('database', function (): Db {
    return new Db();
  });
  $container->set('logger', function () use ($settings): Logger {
    $logger = new Logger('downloadphp');
    $stream = new StreamHandler(__DIR__ . "/../logs/downloadphp.log", $settings['app']['log-level']);
    $logger->pushHandler($stream);
    return $logger;
  });
};