<?php
declare(strict_types=1);

namespace SkyDiablo\ReactphpInfluxDB\API;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use React\Http\Message\ResponseException;
use React\Promise\Deferred;
use SkyDiablo\ReactphpInfluxDB\Client;
use SkyDiablo\ReactphpInfluxDB\Exceptions\WriteException;
use SkyDiablo\ReactphpInfluxDB\Measurement\Point;
use SkyDiablo\ReactphpInfluxDB\Measurement\TimePrecision;
use SkyDiablo\ReactphpInfluxDB\Protocol\PointToLine;
use function React\Promise\all;

class PointWriter
{
    const BASE_ENDPOINT = '/api/v2/write';

    protected PointToLine $pointToLine;

    /**
     * @param Client $client
     */
    public function __construct(protected Client $client)
    {
        $this->pointToLine = new PointToLine();
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
            $precision = TimePrecision::from($precisionName); // to validate only valid values
            $data = implode("\n", array_map([$this->pointToLine, 'toLine'], $tpGroup));
            $results[] = $this->client->post($data, self::BASE_ENDPOINT, ["precision" => $precision->value])
                ->then(function (ResponseInterface $response) use ($tpGroup) {
                    if (StatusCodeInterface::STATUS_NO_CONTENT === $response->getStatusCode()) {
                        return $tpGroup;
                    }
                    throw new WriteException($tpGroup);
                })->catch(function (\Throwable $throwable) use ($tpGroup) {
                    if ($throwable instanceof ResponseException) {
                        $deferred = new Deferred();
                        $buffer = '';
                        $throwable->getResponse()->getBody()->on('data', function ($data) use (&$buffer) {
                            $buffer .= $data;
                        })->on('end', function () use ($deferred, &$buffer, $throwable, $tpGroup) {
                            $decoded = json_decode($buffer, true);
                            $deferred->reject(new WriteException($tpGroup,
                                new \RuntimeException($decoded['message'] ?? $buffer, $throwable->getResponse()->getStatusCode(), $throwable)
                            ));
                        });
                        return $deferred->promise();
                    }
                    throw new WriteException($tpGroup, $throwable);
                });
        }
        return all($results)->then(function (array $lists) {
            return array_merge(...$lists);
        });
    }

}