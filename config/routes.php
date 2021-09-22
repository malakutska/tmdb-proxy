<?php

use TmdbProxy\RequestHandlers;

Flight::route('*', new RequestHandlers\ProxyRequestHandler());