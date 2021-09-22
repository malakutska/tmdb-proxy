<?php
declare(strict_types=1);

namespace TmdbProxy\Boot;

use Flight;
use Throwable;
use TmdbProxy\Helpers\Config;

class ErrorHandler
{
    public function __invoke(Throwable $ex)
    {
        header('Content-type: application/json');
        $statusCode = $ex->getCode() > 99 && $ex->getCode() < 600 ? $ex->getCode() : 400;
        $payload = [
            'code' => $ex->getCode(),
            'success' => false,
        ];

        if (Config::get('debug')) {
            $payload = array_merge($payload, [
                'type' => get_class($ex),
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
                'trace' => $ex->getTrace(),
            ]);
        }
        Flight::halt($statusCode, json_encode($payload));
    }
}