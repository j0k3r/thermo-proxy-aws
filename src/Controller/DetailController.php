<?php

namespace Thermo\Controller;

use Dynamap\Dynamap;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\AbstractLogger;

/**
 * Retrieve information about one peanut.
 */
class DetailController
{
    protected $dynamap;
    protected $log;

    public function __construct(Dynamap $dynamap, AbstractLogger $log)
    {
        $this->dynamap = $dynamap;
        $this->log = $log;
    }

    public function detail(Request $request, Response $response, $args)
    {
        $this->log->notice('getting details for ' . $args['mac']);

        $response->getBody()->write(json_encode(['name' => 'Bob', 'age' => 40]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
