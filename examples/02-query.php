<?php
declare(strict_types=1);


use SkyDiablo\ReactphpInfluxDB\API\FluxQuery;
use SkyDiablo\ReactphpInfluxDB\Client;

require __DIR__ . '/../vendor/autoload.php';

$client = new Client(
    'http://localhost:8086',
    'SkyDiablo',
    'TestBucket',
    'XXXXXXXXXXXX'
);

$query = new FluxQuery($client);

$flux = "from(bucket: \"{$client->getBucket()}\")
    |> range(start: 0)
    |> filter(fn: (r) => r[\"_measurement\"] == \"mem\")
    |> filter(fn: (r) => r[\"host\"] == \"aws_europe\")
    |> count()";
$query('')->then(fn($data) => var_dump($data));