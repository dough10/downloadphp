<?php
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use App\Helpers;

$settings = require __DIR__ . '/../config/settings.php';

/**
 * Application middleware configuration
 * Sets up authentication, rate limiting, and logging
 * 
 * @param App $app Slim application instance
 */
return function (App $app) use ($settings) {
  $container = $app->getContainer();
  $logger = $container->get('logger');
  
  /**
   * Authentication middleware
   * Handles user authentication and directory creation
   * 
   * @param Request $request HTTP request
   * @param RequestHandler $handler Request handler
   * @return Response Response from next middleware
   */  
  $app->add(function (Request $request, RequestHandler $handler) use ($settings): Response {
    $header = $request->getHeaderLine('Authorization');
    if (!empty($header)) {
      $_SESSION['username'] = Helpers\decodeAuthHeader($header);
    } else {
      $_SESSION['username'] = 'default';
    }

    $userPath = $settings['app']['file-path'] . '/' . $_SESSION['username'];

    if (!file_exists($userPath)) {
      mkdir($userPath, 0755, true);
    }

    return $handler->handle($request);
  });

  /**
   * Rate limiting middleware
   * Implements request rate limiting per session
   * 
   * @param Request $request HTTP request
   * @param RequestHandler $handler Request handler
   * @return Response Response or 429 if rate limit exceeded
   */  
  $app->add(function (Request $request, RequestHandler $handler) use ($logger, $settings): Response {   
    if (!isset($_SESSION['request_count'])) {
      $_SESSION['request_count'] = 0;
      $_SESSION['first_request_time'] = time();
    }

    $timeElapsed = time() - $_SESSION['first_request_time'];
    if ($timeElapsed > $settings['limit']['limit-window']) {
      $_SESSION['request_count'] = 0;
      $_SESSION['first_request_time'] = time();
    }

    if ($_SESSION['request_count'] >= $settings['limit']['max-requests']) {
      $logger->notice(Helpers\getUserIP() . ' (' . ($_SESSION['username'] ?? 'guest') . ') hit the rate limit');
      $response = new \Slim\Psr7\Response();
      $response->getBody()->write(json_encode(['error' => 'Rate limit exceeded. Please try again later.']));
      return $response->withStatus(429)->withHeader('Content-Type', 'application/json');
    }

    $_SESSION['request_count']++;
    return $handler->handle($request);
  });

  /**
   * Logging middleware
   * Logs all incoming requests with user and path info
   * 
   * @param Request $request HTTP request
   * @param RequestHandler $handler Request handler
   * @return Response Response from next middleware
   */  
  $app->add(function (Request $request, RequestHandler $handler) use ($logger): Response {
    $username = $_SESSION['username'] ?? 'default';
    $logger->info(Helpers\getUserIP() . ' (' . $username . ') ' . $request->getUri()->getPath());
    return $handler->handle($request);
  });
};