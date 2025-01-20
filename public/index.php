<?php
use Slim\Views\PhpRenderer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;

require_once '../src/Helpers/Utils.php';

$fileDir = '/downloads';

use App\Models\Db;
use App\Helpers;

require __DIR__ . '/../vendor/autoload.php';

$dbFile = 'downloads.db';

$database = new Db($dbFile);

$app = AppFactory::create();

$app->add(function (Request $request, RequestHandler $handler) use ($app) {
  $header = $request->getHeaderLine('Authorization');
  // if (empty($header)) {
  //   $payload = json_encode(['error' => 'Unauthorized'], JSON_PRETTY_PRINT);
  //   $response = $app->getResponseFactory()->createResponse();
  //   $response->getBody()->write($payload);
  //   return $response->withStatus(401);
  // }
  $_SESSION['username'] = Helpers\decodeAuthHeader($header);
  return $handler->handle($request);
});

/**
 * will mark a pending download with a completed status
 * 
 * @param string $ndx
 * @param string $status
 * 
 * @return void
 */
function downloadComplete($ndx, $status) {
  global $database;
  return match($status) {
    'true' => $database->updateDownloadStatus($ndx, 'complete'),
    'canceled' => $database->updateDownloadStatus($ndx, 'canceled'),
    'failed' => $database->updateDownloadStatus($ndx, 'failed'),
    default => throw new Exception('Invalid completed status.'),
  };
}

$app->get('/css/{file}', function (Request $request, Response $response, $args) {
  $file = __DIR__ . '/../public/css/' . $args['file'];
  if (!file_exists($file)) {
    return $response->withStatus(404, 'File Not Found');
  }
  $response->getBody()->write(file_get_contents($file));
  return $response->withHeader('Content-Type', 'text/css');
});

$app->get('/js/{file}', function (Request $request, Response $response, $args) {
  $file = __DIR__ . '/../public/js/' . $args['file'];
  if (!file_exists($file)) {
    return $response->withStatus(404, 'File Not Found');
  }
  $response->getBody()->write(file_get_contents($file));
  return $response->withHeader('Content-Type', 'application/javascript');
});

$app->get('/files/{file}', function (Request $request, Response $response, $args) {
  global $fileDir;
  $file = $fileDir . '/' . $args['file'];
  if (!file_exists($file)) {
    return $response->withStatus(404, 'File Not Found');
  }
  $mimeType = mime_content_type($file);
  if (!$mimeType) {
      $mimeType = 'application/octet-stream';
  }
  $response = $response->withHeader('Content-Type', $mimeType);
  $response->getBody()->write(file_get_contents($file));
  return $response;
});

$app->post('/request-file/{file}', function (Request $request, Response $response, $args) {
  global $fileDir;
  $file = $fileDir . '/' . $args['file'];
  if (!file_exists($file)) {
    $body = json_encode(array('error'=> 'File not found'));
    $response->getBody()->write($body);
    return $response->withStatus(404, 'File not found');
  }
  global $database;
  error_log("Download request: " . $_POST['file'] . " by user: " . '**stand in for username**' . "@" . $_SERVER['REMOTE_ADDR'] . " User-Agent: " . $_SERVER['HTTP_USER_AGENT']);
  $ndx = $database->insertDownloadEntry($file);
  $retData = ['ndx' => $ndx, 'downloads' => $database->getDownloads()];
  $response->getBody()->write(json_encode($retData));
  return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
});

$app->post('/file-status/{ndx}/{status}', function (Request $request, Response $response, $args) {
  global $database;
  try {
    downloadComplete($args['ndx'], $args['status']);
    $response->getBody()->write(json_encode($database->getDownloads()));
    return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
  } catch(Exception $e) {
    $response->getBody()->write(json_encode(array('error'=> $e->getMessage())));
    $response->withStatus(400, 'Bad Request');
    return $response;
  }
});

$app->post('/reset', function (Request $request, Response $response, $args) {
  global $database;
  try {
    $database->clearDownloads();
    $response->getBody()->write(json_encode($database->getDownloads()));
    return $response->withStatus(200)->withHeader('Content-Type', 'application/json');
  } catch(Exception $e) {
    $response->getBody()->write(json_encode(array('error'=> $e->getMessage())));
    return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
  }
});

$app->get('/', function (Request $request, Response $response, $args) {
  global $database;
  global $fileDir;
  $renderer = new PhpRenderer(__DIR__ . '/../templates');
  $viewData = [
    'host' => $_SERVER['HTTP_HOST'],
    'username' => $_SESSION['username'],
    'files' => Helpers\generateFileList($fileDir, Helpers\allowedExtensions),
    'downloadList' => $database->getDownloads()
  ];
  return $renderer->render($response, 'downloads.php', $viewData);
});

$app->run();