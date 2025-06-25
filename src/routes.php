<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;
use App\Helpers;

/**
 * Configure and return application routes
 * Sets up all HTTP endpoints and their handlers
 * 
 * @param App $app Slim application instance
 */
return function (App $app) {
  $settings = require __DIR__ . '/../config/settings.php';
  $container = $app->getContainer();
  $database = $container->get('database');
  $logger = $container->get('logger');

  /**
   * Stream file download
   * GET /files/{file}
   * 
   * @param Request $request HTTP request
   * @param Response $response HTTP response
   * @param array $args Route parameters containing file name
   * @throws Exception On file access/read errors
   * @return Response File stream or error response
   */
  $app->post('/files/{file}', function (Request $request, Response $response, $args) use ($settings, $logger) {
    $user = $request->getAttribute('user-info');
    $safeUsername = str_replace(['@', '.'], ['_at_', '_dot_'], $user);
    $userPath = $settings['app']['file-path'] . '/' . $safeUsername;
    $file = $userPath . '/' . basename($args['file']);

    $realPath = realpath($file);
    if ($realPath === false || strpos($realPath, realpath($userPath)) !== 0) {
      $logger->warning('Forbidden access: ' . $file);
      return Helpers\jsonResponse($response, ['error' => 'Forbidden access'], 403);
    }

    if (!file_exists($file)) {
      $logger->warning('File not found: ' . $file);
      return Helpers\jsonResponse($response, ['error' => 'File not found'], 404);
    }

    $mimeType = mime_content_type($file) ?: 'application/octet-stream';
    $fileSize = filesize($file);

    $response = $response
      ->withHeader('Content-Type', $mimeType)
      ->withHeader('Content-Length', $fileSize)
      ->withHeader('Cache-Control', 'no-store')
      ->withHeader('Content-Disposition', 'attachment; filename="' . basename($file) . '"');

    $logger->info($request->getUri()->getPath() . ', ' . Helpers\formatFileSize($fileSize) . ', ' . $mimeType);

    $response->getBody()->write(file_get_contents($file));
    return $response->withStatus(200);
  });

  /**
   * Request new file download
   * POST /request-file/{file}
   * 
   * @param Request $request HTTP request
   * @param Response $response HTTP response
   * @param array $args Route parameters containing file name
   * @return Response JSON with download ID and list
   */  
  $app->post('/request-file/{file}', function (Request $request, Response $response, $args) use ($settings, $database, $logger) {
    $user = $request->getAttribute('user-info');
    $safeUsername = str_replace(['@', '.'], ['_at_', '_dot_'], $user);
    $userPath = $settings['app']['file-path'] . '/' . $safeUsername;
    $file = $userPath . '/' . basename($args['file']);
    if (realpath($file) === false || strpos(realpath($file), realpath($userPath)) !== 0) {
      $logger->warning('Forbidden access: ' . $file);
      return Helpers\jsonResponse($response, ['error' => 'Forbidden access', 'file' => $file], 403);
    }
    if (!file_exists($file)) {
      $logger->warning('File not found: ' . $file);
      return Helpers\jsonResponse($response, ['error' => 'File not found', 'file' => $file], 404);
    }
    try {
      $ndx = $database->insertDownloadEntry($file, $user);
      $logger->info($request->getUri()->getPath() . ', id: ' . $ndx);
      $retData = ['ndx' => $ndx, 'downloads' => $database->getDownloads($user)];
      return Helpers\jsonResponse($response, $retData, 200);
    } catch (Exception $e) {
      $logger->error($e->getMessage());
      return Helpers\jsonResponse($response, ['error' => $e->getMessage()], 500);
    }
  });

  /**
   * Update download status
   * POST /file-status/{ndx}/{status}
   * 
   * @param Request $request HTTP request
   * @param Response $response HTTP response
   * @param array $args Route parameters [ndx: download ID, status: new status]
   * @return Response JSON with updated download list
   */  
  $app->post('/file-status/{ndx}/{status}', function (Request $request, Response $response, $args) use ($database, $logger) {
    try {
      $user = $request->getAttribute('user-info');
      $database->downloadStatusChanged($args['ndx'], $args['status']);
      return Helpers\jsonResponse($response, $database->getDownloads($user), 200);
    } catch (Exception $e) {
      $logger->error($e->getMessage());
      return Helpers\jsonResponse($response, ['error' => $e->getMessage()], 500);
    }
  });

  /**
   * Clear download history
   * POST /reset
   * 
   * @param Request $request HTTP request
   * @param Response $response HTTP response
   * @param array $args Route parameters
   * @return Response JSON with empty download list
   */  
  $app->post('/reset', function (Request $request, Response $response, $args) use ($database, $logger) {
    try {
      $user = $request->getAttribute('user-info');
      $database->clearDownloads($user);
      return Helpers\jsonResponse($response, $database->getDownloads($user), 200);
    } catch (Exception $e) {
      $logger->error($e->getMessage());
      return Helpers\jsonResponse($response, ['error' => $e->getMessage()], 500);
    }
  });

  /**
   * Get session JavaScript
   * GET /session.js
   * 
   * @param Request $request HTTP request
   * @param Response $response HTTP response
   * @return Response JavaScript code for session management
   */  
  $app->get('/session.js', function (Request $request, Response $response) use ($database) {
    $user = $request->getAttribute('user-info');
    $response = $response->withHeader('Content-Type', 'application/javascript');
    $response->getBody()->write(Helpers\sessionjs($user, $database->getDownloads($user)));
    return $response;
  });

  /**
   * Render main application page
   * GET /
   * 
   * @param Request $request HTTP request
   * @param Response $response HTTP response
   * @param array $args Route parameters
   * @return Response HTML page or error JSON
   */  
  $app->get('/', function (Request $request, Response $response, $args) use ($settings, $database, $logger) {
    $user = $request->getAttribute('user-info');
    $safeUsername = str_replace(['@', '.'], ['_at_', '_dot_'], $user->email);
    $userPath = $settings['app']['file-path'] . '/' . $safeUsername;
    try {
      $renderer = new PhpRenderer(__DIR__ . '/../templates');
      $viewData = [
        'username' => $user->email,
        'allowedExtensions' => $settings['app']['allowed-extensions'],
        'files' => Helpers\generateFileList($userPath, $settings['app']['allowed-extensions']),
        'downloadList' => $database->getDownloads($user->email),
        'csrf' => $request->getAttribute('csrf_token')
      ];
      return $renderer->render($response, 'downloads.phtml', $viewData)->withStatus(200);
    } catch (Exception $e) {
      $logger->error('Error rendering page: ' . $e->getMessage());
      return Helpers\jsonResponse($response, ['error' => $e->getMessage()], 500);
    }
  });

  /**
   * Logout endpoint
   * GET /logout
   * Destroys the current user session and logs out from the auth server
   * 
   * @param Request $request HTTP request
   * @param Response $response HTTP response
   * @return Response JSON logout confirmation
   */
  $app->get('/logout', function (Request $request, Response $response) use ($settings) {
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_unset();
      session_destroy();
      if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
          $params["path"], $params["domain"],
          $params["secure"], $params["httponly"]
        );
      }
    }

    $authLogoutUrl = rtrim($settings['app']['auth-server'], '/') . '/logout';
    return $response
      ->withHeader('Location', $authLogoutUrl)
      ->withStatus(302);
  });

  /**
   * Handle 404 Not Found
   * ANY /{routes:.+}
   * Catches all undefined routes
   * 
   * @param Request $request HTTP request
   * @param Response $response HTTP response
   * @return Response JSON 404 error
   */  
  $app->any('/{routes:.+}', function (Request $request, Response $response) use ($logger) {
    $logger->info('404 ' . $request->getUri()->getPath());
    return Helpers\jsonResponse($response, ['error' => 'File not found'], 404);
  });
};