<?php
use Slim\App;
use App\Models\Db;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

return function (App $app) {
  $settings = require __DIR__ . '/../config/settings.php';
  $container = $app->getContainer();

  $container->set('database', new Db());

  $container->set('logger', function () use ($settings): Logger {
    $logger = new Logger('downloadphp');
    $stream = new StreamHandler($settings['log']['log-location'], $settings['log']['log-level']);
    $logger->pushHandler($stream);
    $logger->getHandlers()[0]->setFormatter(new LineFormatter(
      "%datetime% %level_name%: %message% %context% %extra%\n",
      null, 
      true,
      true  
    ));
    return $logger;
  });
};