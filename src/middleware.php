<?php
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use App\Helpers;

return function (App $app) {
  $container = $app->getContainer();
  $logger = $container->get('logger');
  
  $app->add(function (Request $request, RequestHandler $next) use ($logger) {
    $settings = require __DIR__ . '/../config/settings.php';
    if (!isset($_SESSION['request_count'])) {
      $_SESSION['request_count'] = 0;
      $_SESSION['first_request_time'] = time();
    }
    if (time() - $_SESSION['first_request_time'] > $settings['limit']['limit-window']) {
      $_SESSION['request_count'] = 0;
      $_SESSION['first_request_time'] = time();
    }
    if ($_SESSION['request_count'] >= $settings['limit']['max-requests']) {
      $logger->notice(Helpers\getUserIP() . ' ' . $_SESSION['username'] . ' has hit the rate limit');
      $response = new \Slim\Psr7\Response();
      $response->getBody()->write(json_encode(['error' => 'Rate limit exceeded. Please try again later.']));
      return $response->withStatus(429)->withHeader('Content-Type', 'application/json');
    }
    $_SESSION['request_count']++;
    return $next->handle($request);
  });

  $app->add(function (Request $request, RequestHandler $handler) {
    $header = $request->getHeaderLine('Authorization');
    $_SESSION['username'] = Helpers\decodeAuthHeader($header);
    return $handler->handle($request);
  });
};