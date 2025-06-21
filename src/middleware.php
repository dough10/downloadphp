<?php
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;
use App\Helpers;

/**
 * Application middleware configuration
 * Sets up authentication, rate limiting, and logging
 * 
 * @param App $app Slim application instance
 */
return function (App $app) {
  $settings = require __DIR__ . '/../config/settings.php';
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
  $app->add(function (Request $request, RequestHandler $handler) use ($settings, $logger): Response {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }

    $queryParams = $request->getQueryParams();
    $token = $queryParams['token'] ?? '';

    try {
      $username = Helpers\decodeToken($token);
    } catch (\Exception $e) {
      $logger->warning('Authentication failed: ' . $e->getMessage());
      $response = new SlimResponse();
      return $response
        ->withHeader('Location', Helpers\auth_redirect_address($request))
        ->withStatus(302);
    }

    $_SESSION['username'] = $username;
    
    try {
      $userPath = $settings['app']['file-path'] . DIRECTORY_SEPARATOR . $username;
      $realPath = realpath(dirname($userPath));
      
      if ($realPath === false) {
        throw new \RuntimeException('Invalid base path');
      }

      $userDir = $realPath . DIRECTORY_SEPARATOR . basename($userPath);
      
      if (!file_exists($userDir)) {
        if (!mkdir($userDir, 0775, true)) {
          throw new \RuntimeException('Failed to create user directory');
        }
        $logger->info('Created directory for user: ' . $username);
      }
    } catch (\Exception $e) {
      $logger->error('Directory creation failed: ' . $e->getMessage());
      return Helpers\jsonResponse(
        new SlimResponse(),
        ['error' => 'Server configuration error'],
        500
      );
    }

    $logger->info('User authenticated: ' . $username);
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
      $response = new SlimResponse();
      $response->getBody()->write(json_encode(['error' => 'Rate limit exceeded. Please try again later.']));
      return $response->withStatus(429)->withHeader('Content-Type', 'application/json');
    }

    $_SESSION['request_count']++;
    return $handler->handle($request);
  });

  /**
   * CSRF Protection Middleware
   */
  $app->add(function (Request $request, RequestHandler $handler): Response {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }

    $method = strtoupper($request->getMethod());
    $isSafeMethod = in_array($method, ['GET', 'HEAD', 'OPTIONS']);

    if ($isSafeMethod) {
      if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }
      $response = $handler->handle($request);
      $path = $request->getUri()->getPath();
      if ($path === '/logout') {
        return $response;
      }
      return $response->withHeader('X-CSRF-Token', $_SESSION['csrf_token']);
    }

    $parsedBody = $request->getParsedBody() ?? [];
    $csrfToken = $request->getHeaderLine('X-CSRF-Token') ?? $parsedBody['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
      $response = new SlimResponse();
      $response->getBody()->write(json_encode(['error' => 'Invalid CSRF token']));
      return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    }

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