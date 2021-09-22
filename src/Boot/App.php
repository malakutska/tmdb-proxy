<?php
declare(strict_types=1);

namespace TmdbProxy\Boot;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Cache\Bridge\SimpleCache\SimpleCacheBridge;
use Dotenv\Dotenv;
use Flight;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Symfony\Component\HttpClient\HttpClient;
use TmdbProxy\Helpers\Config;

class App
{
    public function init(): App
    {
        $this->loadEnv();
        $this->setVariables();
        $this->createCacheEngine();
        $this->createHttpClient();
        $this->createDbConnection();
        $this->setErrorHandler();

        return $this;
    }

    protected function loadEnv()
    {
        $dotenv = Dotenv::createImmutable(ROOT_DIR);
        $dotenv->safeLoad();
    }

    protected function setVariables()
    {
        $settings = require_once CONFIG_DIR . 'settings.php';
        Config::init($settings);
        Flight::set('flight.log_errors', true);
    }

    protected function createCacheEngine()
    {
        $filesystemAdapter = new Local(Config::get('cache.dir'));
        $filesystem = new Filesystem($filesystemAdapter);
        $pool = new FilesystemCachePool($filesystem);
        Flight::set('CACHE_ENGINE', new SimpleCacheBridge($pool));
    }

    protected function createHttpClient()
    {
        Flight::set('HTTP_CLIENT', HttpClient::create());
    }

    protected function createDbConnection()
    {

    }

    protected function setErrorHandler()
    {
        Flight::map('error', new ErrorHandler());
    }

    public function loadRoutes(): App
    {
        require_once CONFIG_DIR . 'routes.php';

        return $this;
    }

    public function start()
    {
        Flight::start();
    }
}