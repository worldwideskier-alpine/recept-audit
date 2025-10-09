<?php
declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use App\Exceptions\HttpException;
use App\Support\Request;
use App\Support\Response;
use App\Support\Router;
use function App\Support\log_exception;

$request = Request::fromGlobals();
$router = Router::buildDefault();

try {
    $response = $router->dispatch($request);
} catch (HttpException $httpException) {
    if ($httpException->status >= 300 && $httpException->status < 400 && $httpException->redirectTo !== null) {
        $response = Response::redirect($httpException->redirectTo, $httpException->status);
    } else {
        $response = Response::json([
            'ok' => false,
            'error' => $httpException->getMessage(),
        ], $httpException->status);
    }
} catch (\Throwable $throwable) {
    log_exception($throwable, 'uncaught_exception');
    $response = Response::json([
        'ok' => false,
        'error' => 'internal_server_error',
    ], 500);
}

$response->send(!$request->isHead());
