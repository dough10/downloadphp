<?php

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\PhpRenderer;
use App\Helpers;


return function (App $app) {
  $settings = require __DIR__ . '/../config/settings.php';
  $container = $app->getContainer();
  $database = $container->get('database');
  $logger = $container->get('logger');

  $app->get('/files/{file}', function (Request $request, Response $response, $args) use ($settings, $logger) {
    $userPath = $settings['app']['file-path'] . '/' . $_SESSION['username'];
    $file = $userPath . '/' . basename($args['file']);

    if (realpath($file) === false || strpos(realpath($file), realpath($userPath)) !== 0) {
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

  $app->post('/request-file/{file}', function (Request $request, Response $response, $args) use ($settings, $database, $logger) {
    $userPath = $settings['app']['file-path'] . '/' . $_SESSION['username'];
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
      $ndx = $database->insertDownloadEntry($file);
      $logger->info($request->getUri()->getPath() . ', id: ' . $ndx);
      $retData = ['ndx' => $ndx, 'downloads' => $database->getDownloads()];
      return Helpers\jsonResponse($response, $retData, 200);
    } catch (Exception $e) {
      $logger->error($e->getMessage());
      return Helpers\jsonResponse($response, ['error' => $e->getMessage()], 500);
    }
  });

  $app->post('/file-status/{ndx}/{status}', function (Request $request, Response $response, $args) use ($database, $logger) {
    try {
      $database->downloadStatusChanged($args['ndx'], $args['status']);
      return Helpers\jsonResponse($response, $database->getDownloads(), 200);
    } catch (Exception $e) {
      $logger->error($e->getMessage());
      return Helpers\jsonResponse($response, ['error' => $e->getMessage()], 500);
    }
  });

  $app->post('/reset', function (Request $request, Response $response, $args) use ($database, $logger) {
    try {
      $database->clearDownloads();
      return Helpers\jsonResponse($response, $database->getDownloads(), 200);
    } catch (Exception $e) {
      $logger->error($e->getMessage());
      return Helpers\jsonResponse($response, ['error' => $e->getMessage()], 500);
    }
  });

  $app->get('/', function (Request $request, Response $response, $args) use ($settings, $database, $logger) {
    $userPath = $settings['app']['file-path'] . '/' . $_SESSION['username'];
    try {
      $renderer = new PhpRenderer(__DIR__ . '/../templates');
      $viewData = [
        'host' => $_SERVER['HTTP_HOST'],
        'username' => $_SESSION['username'],
        'allowedExtensions' => $settings['app']['allowed-extensions'],
        'files' => Helpers\generateFileList($userPath, $settings['app']['allowed-extensions']),
        'downloadList' => $database->getDownloads()
      ];
      return $renderer->render($response, 'downloads.phtml', $viewData)->withStatus(200);
    } catch (Exception $e) {
      $logger->error('Error rendering page: ' . $e->getMessage());
      return Helpers\jsonResponse($response, ['error' => $e->getMessage()], 500);
    }
  });

  $app->any('/{routes:.+}', function (Request $request, Response $response) use ($logger) {
    $logger->info('404 ' . $request->getUri()->getPath());
    return Helpers\jsonResponse($response, ['error' => 'File not found'], 404);
  });
};