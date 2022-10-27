<?php
declare(strict_types=1);

namespace SkyDiablo\ReactphpInfluxDB\API;

use Fig\Http\Message\StatusCodeInterface;
use RingCentral\Psr7\Response;
use SkyDiablo\ReactphpInfluxDB\Client;
use SkyDiablo\ReactphpInfluxDB\Measurement\Point;
use SkyDiablo\ReactphpInfluxDB\Measurement\WritePrecision;
use function React\Promise\any;

class PointWriter
{
    const BASE_ENDPOINT = '/api/v2/write';

    /**
     * @param Client $client
     */
    public function __construct(protected Client $client)
    {
    }

    /**
     * @param array<Point> $points
     * @return \React\Promise\PromiseInterface<bool>
     */
    public function __invoke(array $points): \React\Promise\PromiseInterface
    {
        $results = [];
        $tpGroups = [];
        foreach ($points as $point) {
            $tpGroups[$point->getPrecision()->value][] = $point;
        }
        foreach ($tpGroups as $key => $tpGroup) {
            $precision = WritePrecision::from($key); // to validate only valid values
            $data = implode("\n", array_map(fn(Point $point) => $point->toLineProtocol(), $tpGroup));
            $results[] = $this->client->post($data, self::BASE_ENDPOINT, ["precision" => $precision->value]);
        }
        return any($results)->then(function (Response $response) {
            return StatusCodeInterface::STATUS_NO_CONTENT === $response->getStatusCode();
        });
    }

}