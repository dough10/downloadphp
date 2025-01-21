<?php
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Helpers;

return function (App $app) {
  $app->add(function (Request $request, RequestHandler $handler) {
    $header = $request->getHeaderLine('Authorization');
    $_SESSION['username'] = Helpers\decodeAuthHeader($header);
    return $handler->handle($request);
  });
};