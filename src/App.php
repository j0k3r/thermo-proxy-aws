<?php

require __DIR__ . '/../vendor/autoload.php';

use Bref\Logger\StderrLogger;
use DI\Container;
use Dynamap\Dynamap;
use Psr\Container\ContainerInterface;
use Psr\Log\LogLevel;
use Slim\Factory\AppFactory;
use Thermo\Controller\DetailController;
use Thermo\Controller\DeviceController;
use Thermo\Controller\EventController;
use Thermo\Controller\ListController;
use Thermo\Model\Device;

// define container
$container = new Container();

// define dynamo
$dynamoOptions = [
    'version' => 'latest',
    'region' => 'eu-west-1',
];
// if the env variable isn't define, it means we are not in AWS env, so use local DynamoDB
if (false === getenv('AWS_LAMBDA_RUNTIME_API')) {
    $dynamoOptions['endpoint'] = 'http://localhost:8000/';
    $dynamoOptions['credentials'] = [
        'key' => 'FAKE_KEY',
        'secret' => 'FAKE_SECRET',
    ];
}
$dynamap = Dynamap::fromOptions($dynamoOptions, [
    Device::class => [
        'table' => 'thermo',
        'keys' => [
            'mac',
        ],
    ],
]);
$container->set('dynamap', $dynamap);

$container->set('log', (new StderrLogger(LogLevel::NOTICE)));

// define app
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

// define controller as a service
$container->set('ListController', function (ContainerInterface $c) {
    return new ListController(
        $c->get('dynamap'),
        $c->get('log')
    );
});
$container->set('DetailController', function (ContainerInterface $c) {
    return new DetailController(
        $c->get('dynamap'),
        $c->get('log')
    );
});
$container->set('EventController', function (ContainerInterface $c) {
    return new EventController(
        $c->get('dynamap'),
        $c->get('log')
    );
});
$container->set('DeviceController', function (ContainerInterface $c) {
    return new DeviceController(
        $c->get('dynamap'),
        $c->get('log')
    );
});

$app->get('/thermo/init', 'DeviceController:init');
$app->get('/thermo/list', 'ListController:list');
$app->get('/thermo/{mac}/detail', 'DetailController:detail');
$app->post('/peanut/api/v1/peanuts/{mac}/events/', 'EventController:register');

$app->run();
