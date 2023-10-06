<?php
declare(strict_types=1);

namespace SkyDiablo\ReactphpInfluxDB\API;

use Fig\Http\Message\StatusCodeInterface;
use RingCentral\Psr7\Response;
use SkyDiablo\ReactphpInfluxDB\Client;
use SkyDiablo\ReactphpInfluxDB\Exceptions\WriteException;
use SkyDiablo\ReactphpInfluxDB\Measurement\Point;
use SkyDiablo\ReactphpInfluxDB\Measurement\WritePrecision;
use function React\Promise\all;

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
     * @param Point[] $points
     * @return \React\Promise\PromiseInterface<Point[]>
     */
    public function __invoke(array $points): \React\Promise\PromiseInterface
    {
        $results = [];
        $tpGroups = [];
        foreach ($points as $point) {
            $tpGroups[$point->getPrecision()->value][] = $point;
        }
        foreach ($tpGroups as $precisionName => $tpGroup) {
            $precision = WritePrecision::from($precisionName); // to validate only valid values
            $data = implode("\n", array_map(fn(Point $point) => $point->toLineProtocol(), $tpGroup));
            $results[] = $this->client->post($data, self::BASE_ENDPOINT, ["precision" => $precision->value])
                ->then(function (Response $response) use ($tpGroup) {
                    if (StatusCodeInterface::STATUS_NO_CONTENT === $response->getStatusCode()) {
                        return $tpGroup;
                    }
                    throw new WriteException($tpGroup); //todo: add response to exception, see FluxQuery
                })->catch(function (\Throwable $throwable) use ($tpGroup) {
                    throw new WriteException($tpGroup, $throwable);
                });
        }
        return all($results)->then(function (array $lists) {
            return array_merge(...$lists);
        });
    }

}