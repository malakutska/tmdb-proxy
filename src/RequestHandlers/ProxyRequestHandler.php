<?php
declare(strict_types=1);

namespace TmdbProxy\RequestHandlers;

use DateInterval;
use Exception;
use Flight;
use TmdbProxy\Helpers\Config;

class ProxyRequestHandler
{
    /**
     * @throws Exception
     */
    public function __invoke()
    {
        $cache = Flight::get('CACHE_ENGINE');
        $client = Flight::get('HTTP_CLIENT');

        $request = Flight::request();

        $method = $request->method;
        $path = $request->url;

        $cacheKey = preg_replace('/[^a-zA-Z0-9_.!]+/', '..', strtolower($method . $path));
        if (!Flight::get('debug')) {
            Flight::etag(md5($cacheKey));
        }

        if ($cache->has($cacheKey)) {
            $result = $cache->get($cacheKey);
            $result['cached'] = true;
        } else {
            $url = 'https://api.themoviedb.org' . parse_url($path, PHP_URL_PATH);
            $query = $request->query->jsonSerialize();
            $query['api_key'] = Config::get('tmdb.apiKey');
            $response = $client->request($request->method, $url, compact('query'));

            $result = $response->toArray();
            if ($response->getStatusCode() === 200) {
                $ttl = new DateInterval('P' . Config::get('cache.ttlMinutes') . 'M');
                $cache->set($cacheKey, $result, $ttl);
            }
            $result['cached'] = false;
        }

        Flight::json([
            'code' => 200,
            'success' => true,
            'data' => $result
        ]);
    }
}