<?php

require __DIR__ . '/../vendor/autoload.php';

use Aws\DynamoDb\DynamoDbClient;
use Aws\Sdk;
use Bref\Logger\StderrLogger;
use DI\Container;
use Dotenv\Dotenv;
use Dynamap\Dynamap;
use InfluxDB\Client;
use InfluxDB\Database;
use Psr\Container\ContainerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Slim\Factory\AppFactory;
use Thermo\Controller\DetailController;
use Thermo\Controller\DeviceController;
use Thermo\Controller\EventController;
use Thermo\Controller\ListController;
use Thermo\Model\Device;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
$dotenv->required(['INFLUXDB_HOST', 'INFLUXDB_PORT', 'INFLUXDB_DBNAME']);

// define container
$container = new Container();

// define dynamo
$dynamoOptions = [
    'version' => 'latest',
    'region' => 'eu-west-1',
];
$container->set('dynamodb', null);

// if the env variable isn't define, it means we are not in AWS env, so use local DynamoDB
if (empty($_ENV['AWS_LAMBDA_RUNTIME_API'])) {
    $dynamoOptions['endpoint'] = 'http://localhost:8000/';
    $dynamoOptions['credentials'] = [
        'key' => 'FAKE_KEY',
        'secret' => 'FAKE_SECRET',
    ];

    // create single SDK connection to DyanmoDB (to create the table locally)
    $sdk = new Sdk($dynamoOptions);
    $container->set('dynamodb', $sdk->createDynamoDb());
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

// define influx
$dsn = sprintf(
    'influxdb://%s:%s/%s',
    $_ENV['INFLUXDB_HOST'],
    $_ENV['INFLUXDB_PORT'],
    $_ENV['INFLUXDB_DBNAME']
);
if (!empty($_ENV['INFLUXDB_USER']) && !empty($_ENV['INFLUXDB_PASS'])) {
    $dsn = sprintf(
        'influxdb://%s:%s@%s:%s/%s',
        $_ENV['INFLUXDB_USER'],
        $_ENV['INFLUXDB_PASS'],
        $_ENV['INFLUXDB_HOST'],
        $_ENV['INFLUXDB_PORT'],
        $_ENV['INFLUXDB_DBNAME']
    );
}

$influx = Client::fromDSN($dsn);
$container->set('influx', $influx);

$container->set('log', new StderrLogger(LogLevel::NOTICE));

// define app
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

// define controller as a service
$container->set('ListController', function (ContainerInterface $c) {
    /** @var Dynamap */
    $dynamap = $c->get('dynamap');
    /** @var AbstractLogger */
    $log = $c->get('log');

    return new ListController(
        $dynamap,
        $log
    );
});
$container->set('DetailController', function (ContainerInterface $c) {
    /** @var Dynamap */
    $dynamap = $c->get('dynamap');
    /** @var Database */
    $influx = $c->get('influx');
    /** @var AbstractLogger */
    $log = $c->get('log');

    return new DetailController(
        $dynamap,
        $influx,
        $log
    );
});
$container->set('EventController', function (ContainerInterface $c) {
    /** @var Dynamap */
    $dynamap = $c->get('dynamap');
    /** @var Database */
    $influx = $c->get('influx');
    /** @var AbstractLogger */
    $log = $c->get('log');

    return new EventController(
        $dynamap,
        $influx,
        $log
    );
});
$container->set('DeviceController', function (ContainerInterface $c) {
    /** @var Dynamap */
    $dynamap = $c->get('dynamap');
    /** @var DynamoDbClient */
    $dynamodb = $c->get('dynamodb');
    /** @var Database */
    $influx = $c->get('influx');
    /** @var AbstractLogger */
    $log = $c->get('log');

    return new DeviceController(
        $dynamap,
        $dynamodb,
        $influx,
        $log
    );
});

$app->get('/thermo/init', 'DeviceController:init');
$app->get('/thermo/list', 'ListController:list');
$app->get('/thermo/{mac}/detail', 'DetailController:detail');
$app->post('/peanut/api/v1/peanuts/{mac}/events/', 'EventController:register');

$app->run();
