<?php
require_once '../vendor/autoload.php';

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Cache\Bridge\SimpleCache\SimpleCacheBridge;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

Flight::set('apiKey', $_ENV['TMDB_API_KEY'] ?? '');
Flight::set('ttlMinutes', $_ENV['CACHE_TTL_MINUTES'] ?? 1);
Flight::set('debug', isset($_ENV['DEBUG']) ? filter_var($_ENV['DEBUG'], FILTER_VALIDATE_BOOLEAN) : true);
Flight::set('flight.log_errors', true);

$filesystemAdapter = new Local(__DIR__ . '/../cache/');
$filesystem = new Filesystem($filesystemAdapter);
$pool = new FilesystemCachePool($filesystem);
$cache = new SimpleCacheBridge($pool);
$client = HttpClient::create();

Flight::map('error', function (Exception $ex) {
    header('Content-type: application/json');
    $statusCode = $ex->getCode() > 99 ? $ex->getCode() : 400;
    $payload = [
        'code' => $ex->getCode(),
        'success' => false,
    ];

    if (Flight::get('debug')) {
        $payload = array_merge($payload, [
            'type' => get_class($ex),
            'message' => $ex->getMessage(),
            'file' => $ex->getFile(),
            'line' => $ex->getLine(),
            'trace' => $ex->getTrace(),
        ]);
    }
    Flight::halt($statusCode, json_encode($payload));
});

Flight::route('*', function () use ($cache, $client) {
    $request = Flight::request();

    $method = $request->method;
    $path = $request->url;

    $cacheKey = preg_replace('/[^a-zA-Z0-9_.!]+/', '..', strtolower($method . $path));
    if (!Flight::get('debug')){
        Flight::etag(md5($cacheKey));
    }

    if ($cache->has($cacheKey)) {
        $result = $cache->get($cacheKey);
        $result['cached'] = true;
    } else {
        $url = 'https://api.themoviedb.org' . parse_url($path, PHP_URL_PATH);
        $query = $request->query->jsonSerialize();
        $query['api_key'] = Flight::get('apiKey');
        $response = $client->request($request->method, $url, compact('query'));

        $result = $response->toArray();
        if ($response->getStatusCode() === 200) {
            $ttl = new DateInterval('P' . Flight::get('ttlMinutes') . 'M');
            $cache->set($cacheKey, $result, $ttl);
        }
        $result['cached'] = false;
    }

    Flight::json([
        'code' => 200,
        'success' => true,
        'data' => $result
    ]);
});

Flight::start();