<?php
use Slim\App;
use App\Models\Db;

return function (App $app) {
  $container = $app->getContainer();
  $container->set('database', function (): Db {
    return new Db();
  });
};