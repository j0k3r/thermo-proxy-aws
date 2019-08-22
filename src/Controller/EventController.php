<?php

namespace Thermo\Controller;

use Dynamap\Dynamap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\AbstractLogger;

/**
 * The Sense Peanut App will post information on this endpoint.
 */
class EventController
{
    protected $dynamap;
    protected $log;

    public function __construct(Dynamap $dynamap, AbstractLogger $log)
    {
        $this->dynamap = $dynamap;
        $this->log = $log;
    }

    public function register(Request $request, Response $response, $args)
    {
        $this->log->notice('inserted XXX events');

        $response->getBody()->write(json_encode(['name' => 'Bob', 'age' => 40]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
