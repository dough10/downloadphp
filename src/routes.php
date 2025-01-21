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

  $app->get('/files/{file}', function (Request $request, Response $response, $args) use ($settings) {
    $file = $settings['app']['file-path'] . '/' . $args['file'];
    if (!file_exists($file)) {
      $body = json_encode(array('error'=> 'File not found'));
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
        ->withHeader('Content-Length', $fileSize);
    $response->getBody()->write(file_get_contents($file));
    return $response;
  });
  
  $app->post('/request-file/{file}', function (Request $request, Response $response, $args) use ($settings, $database) {
    $file = $settings['app']['file-path'] . '/' . $args['file'];
    if (!file_exists($file)) {
      $body = json_encode(array('error'=> 'File not found'));
      $response->getBody()->write($body);
      return $response->withStatus(404, 'File not found');
    }
    // error_log("Download request: " . $args['file'] . " by user: " . '**stand in for username**' . "@" . $_SERVER['REMOTE_ADDR'] . " User-Agent: " . $_SERVER['HTTP_USER_AGENT']);
    $ndx = $database->insertDownloadEntry($file);
    $retData = ['ndx' => $ndx, 'downloads' => $database->getDownloads()];
    $response->getBody()->write(json_encode($retData));
    return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
  });
  
  $app->post('/file-status/{ndx}/{status}', function (Request $request, Response $response, $args) use ($database) {
    try {
      $database->downloadStatusChanged($args['ndx'], $args['status']);
      $response->getBody()->write(json_encode($database->getDownloads()));
      return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    } catch(Exception $e) {
      $response->getBody()->write(json_encode(array('error'=> $e->getMessage())));
      $response->withStatus(400, 'Bad Request');
      return $response;
    }
  });
  
  $app->post('/reset', function (Request $request, Response $response, $args) use ($database) {
    try {
      $database->clearDownloads();
      $response->getBody()->write(json_encode($database->getDownloads()));
      return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
    } catch(Exception $e) {
      $response->getBody()->write(json_encode(array('error'=> $e->getMessage())));
      return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
    }
  });
  
  $app->get('/', function (Request $request, Response $response, $args) use ($settings, $database) {
    $renderer = new PhpRenderer(__DIR__ . '/../resources/templates');
    $viewData = [
      'host' => $_SERVER['HTTP_HOST'],
      'username' => $_SESSION['username'],
      'folder' => $settings['app']['file-path'],
      'allowedExtensions' => $settings['app']['allowed-extensions'],
      'files' => Helpers\generateFileList($settings['app']['file-path'], $settings['app']['allowed-extensions']),
      'downloadList' => $database->getDownloads()
    ];
    return $renderer->render($response, 'downloads.phtml', $viewData);
  });

  $app->get('*', function (Request $request, Response $response, $args) {
    return $response->withStatus(404, 'File not found');
  });
};