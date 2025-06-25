<?php
use Slim\App;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as SlimResponse;
use Slim\Views\PhpRenderer;
use App\Helpers;

function authenticate($token, $refresh, $logger) {
  try {
    // $logger->debug('Raw token: ' . $token);
    return Helpers\decodeToken($token, $logger);
  } catch(\Exception $e) {
    $logger->debug('Decode token failed: ' . $e->getMessage());
    try {
      $token = Helpers\attemptTokenRefresh($refresh);
      return Helpers\decodeToken($token, $logger);
    } catch(\Exception $e) {
      $logger->debug('Refresh token failed: ' . $e->getMessage());
      throw new Exception($e->getMessage());
    }
  }
}

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
    $cookies = $request->getCookieParams();
    $token = $cookies['access_token'] ?? '';
    $refresh = $cookies['refresh_token'] ?? '';
    $token = trim($token, "\"'");
    $token = preg_replace('/^Bearer\s+/i', '', $token);
    
    if (!$token) {
      $response = new SlimResponse();
      return $response
        ->withHeader('Location', Helpers\auth_redirect_address($request))
        ->withStatus(302);
    }

    try {
      $userInfo = authenticate($token, $refresh, $logger);
    } catch (\Exception $e) {
      $message = 'Authentication failed: ' . $e->getMessage();
      $logger->warning($message);
      $renderer = new PhpRenderer(__DIR__ . '/../templates');
      $viewData = [
        'error' => $message
      ];
      $response = new SlimResponse();
      return $renderer->render($response, 'error.phtml', $viewData)->withStatus(403);
    }
    
    
    $request = $request->withAttribute('user-info', $userInfo);
    
    
    try {
      if (!filter_var($userInfo->email, FILTER_VALIDATE_EMAIL)) {
        throw new \RuntimeException('Invalid username format');
      }
      
      $safeUsername = str_replace(['@', '.'], ['_at_', '_dot_'], $userInfo->email);
      $userPath = $settings['app']['file-path'] . DIRECTORY_SEPARATOR . $safeUsername;
      $realPath = realpath(dirname($userPath));
      
      if ($realPath === false) {
        throw new \RuntimeException('Invalid base path');
      }
      
      $userDir = $realPath . DIRECTORY_SEPARATOR . basename($userPath);
      
      if (!file_exists($userDir)) {
        if (!mkdir($userDir, 0775, true)) {
          throw new \RuntimeException('Failed to create user directory');
        }
        $logger->info('Created directory for user: ' . $userInfo->email);
      }
    } catch (\Exception $e) {
      $logger->error('Directory creation failed: ' . $e->getMessage());
      return Helpers\jsonResponse(
        new SlimResponse(),
        ['error' => 'Server configuration error'],
        500
      );
    }
    
    $logger->info(Helpers\getUserIP() . ' (' . $userInfo->email . ') ' . $request->getUri()->getPath());
    return $handler->handle($request);
  });

  /**
   * CSRF Protection Middleware
   */
  $app->add(function (Request $request, RequestHandler $handler): Response {
    $method = strtoupper($request->getMethod());
    $isSafeMethod = in_array($method, ['GET', 'HEAD', 'OPTIONS']);

    if ($isSafeMethod) {
      $csrfToken = bin2hex(random_bytes(32));
      setcookie('csrf_token', $csrfToken, [
        'path' => '/',
        'secure' => true,
        'samesite' => 'Strict'
      ]);
      $response = $handler->handle($request);
      $path = $request->getUri()->getPath();
      if ($path === '/logout') {
        return $response;
      }
      return $response->withHeader('X-CSRF-Token', $csrfToken);
    }

    $csrfTokenHeader = $request->getHeaderLine('X-CSRF-Token');
    $csrfTokenCookie = $_COOKIE['csrf_token'] ?? '';

    if (!hash_equals($csrfTokenCookie, $csrfTokenHeader)) {
      $response = new SlimResponse();
      $response->getBody()->write(json_encode(['error' => 'Invalid CSRF token']));
      return $response->withStatus(403)->withHeader('Content-Type', 'application/json');
    }

    return $handler->handle($request);
});
};