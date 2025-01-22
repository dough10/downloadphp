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
    $file = $settings['app']['file-path'] . '/' . basename($args['file']);

    if (realpath($file) === false || strpos(realpath($file), realpath($settings['app']['file-path'])) !== 0) {
      $logger->warning('Forbidden access: ' . $file);
      $body = json_encode(['error' => 'Forbidden access']);
      $response->getBody()->write($body);
      return $response->withStatus(403, 'Forbidden');
    }

    if (!file_exists($file)) {
      $logger->warning('File not found: ' . $file);
      $body = json_encode(['error' => 'File not found']);
      $response->getBody()->write($body);
      return $response->withStatus(404, 'File Not Found');
    }
  
    $mimeType = mime_content_type($file);
    if (!$mimeType) {
      $mimeType = 'application/octet-stream';
    }
    $fileSize = filesize($file);
  
    $response = $response
      ->withHeader('Content-Type', $mimeType)
      ->withHeader('Content-Length', $fileSize)
      ->withHeader('Cache-Control', 'no-store')
      ->withHeader('Content-Disposition', 'attachment; filename="' . basename($file) . '"');
  
    $handle = fopen($file, 'rb');
    if (!$handle) {
      $logger->error('Unable to open file: ' . $file);
      $body = json_encode(['error' => 'Unable to open file']);
      $response->getBody()->write($body);
      return $response->withStatus(500, 'Internal Server Error');
    }

    $logger->info(Helpers\getUserIP() . ' (' . $_SESSION['username'] . ') ' . $request->getUri()->getPath() . ', ' . Helpers\formatFileSize($fileSize) . ', ' . $mimeType);
  
    $chunkSize = 1024 * 1024;
    $throttleDelay = 0.1;
  
    while (!feof($handle)) {
      $chunk = fread($handle, $chunkSize);
      $response->getBody()->write($chunk);
      flush();
      usleep($throttleDelay * 1000000);
    }
    fclose($handle);
  
    return $response;
  });
  
  $app->post('/request-file/{file}', function (Request $request, Response $response, $args) use ($settings, $database, $logger) {
    $file = $settings['app']['file-path'] . '/' . $args['file'];
    if (realpath($file) === false || strpos(realpath($file), realpath($settings['app']['file-path'])) !== 0) {
      $logger->warning('Forbidden access: ' . $file);
      $body = json_encode(['error' => 'Forbidden access', 'file' => $file]);
      $response->getBody()->write($body);
      return $response->withStatus(403, 'Forbidden');
    }
    if (!file_exists($file)) {
      $logger->warning('File not found: ' . $file);
      $body = json_encode(array('error'=> 'File not found','file' => $file));
      $response->getBody()->write($body);
      return $response->withStatus(404, 'File not found');
    }
    try {
      $ndx = $database->insertDownloadEntry($file);
      $logger->info(Helpers\getUserIP() . ' (' . $_SESSION['username'] . ') ' . $request->getUri()->getPath() . ', id: ' . $ndx);
      $retData = ['ndx' => $ndx, 'downloads' => $database->getDownloads()];
      $response->getBody()->write(json_encode($retData));
      return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    } catch (Exception $e) {
      $logger->error($e->getMessage());
      $response->getBody()->write(json_encode(array('error'=> $e->getMessage())));
      $response->withStatus(500, 'Internal Server Error')->withHeader('Content-Type', 'application/json');
      return $response;
    }
  });
  
  $app->post('/file-status/{ndx}/{status}', function (Request $request, Response $response, $args) use ($database, $logger) {
    try {
      $logger->info(Helpers\getUserIP(). ' (' . $_SESSION['username'] . ') ' . $request->getUri()->getPath());
      $database->downloadStatusChanged($args['ndx'], $args['status']);
      $response->getBody()->write(json_encode($database->getDownloads()));
      return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    } catch(Exception $e) {
      $logger->error($e->getMessage());
      $response->getBody()->write(json_encode(array('error'=> $e->getMessage())));
      $response->withStatus(500, 'Internal Server Error')->withHeader('Content-Type', 'application/json');
      return $response;
    }
  });
  
  $app->post('/reset', function (Request $request, Response $response, $args) use ($database, $logger) {
    try {
      $logger->info(Helpers\getUserIP() . ' (' . $_SESSION['username'] . ') ' . $request->getUri()->getPath());
      $database->clearDownloads();
      $response->getBody()->write(json_encode($database->getDownloads()));
      return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    } catch(Exception $e) {
      $logger->error($e->getMessage());
      $response->getBody()->write(json_encode(array('error'=> $e->getMessage())));
      return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
  });
  
  $app->get('/', function (Request $request, Response $response, $args) use ($settings, $database, $logger) {
    try {
      $renderer = new PhpRenderer(__DIR__ . '/../templates');
      $viewData = [
        'host' => $_SERVER['HTTP_HOST'],
        'username' => $_SESSION['username'],
        'allowedExtensions' => $settings['app']['allowed-extensions'],
        'files' => Helpers\generateFileList($settings['app']['file-path'], $settings['app']['allowed-extensions']),
        'downloadList' => $database->getDownloads()
      ];
      $logger->info(Helpers\getUserIP() . ' (' . $_SESSION['username'] . ') ' . $request->getUri()->getPath());
      return $renderer->render($response, 'downloads.phtml', $viewData);
    } catch(Exception $e) {
      $logger->error('Error rendering page: ' . $e->getMessage());
      $response->getBody()->write(json_encode(array('error'=> $e->getMessage())));
      return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
  });

  $app->any('/{routes:.+}', function (Request $request, Response $response) use ($logger) {
    $logger->info(Helpers\getUserIP() . ' (' . $_SESSION['username'] . ') 404 ' . $request->getUri()->getPath());
    $body = json_encode(array('error'=> 'File not found'));
    $response->getBody()->write($body);
    return $response->withStatus(404, 'File not found');
  });
};