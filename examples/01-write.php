<?php
declare(strict_types=1);

use SkyDiablo\ReactphpInfluxDB\API\PointWriter;
use SkyDiablo\ReactphpInfluxDB\Client;
use SkyDiablo\ReactphpInfluxDB\Measurement\Point;

require __DIR__ . '/../vendor/autoload.php';

$client = new Client(
    'http://localhost:8086',
    'SkyDiablo',
    'TestBucket',
    'XXXXXXXXXXXX'
);
$writer = new PointWriter($client);

$points = [
    new Point('mem', ['host' => 'aws_europe', 'type' => 'batch'], ['value' => 75.3], 1),
    new Point('mem', ['host' => 'aws_europe', 'type' => 'batch'], ['value' => 75.3], 10),
    new Point('mem', ['host' => 'aws_europe', 'type' => 'batch'], ['value' => 75.3], 20),
    new Point('mem', ['host' => 'aws_europe', 'type' => 'batch'], ['value' => 75.3], 30),
];

$writer($points)->then(fn($results) => var_dump($results));