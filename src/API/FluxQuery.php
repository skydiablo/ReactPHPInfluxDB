<?php
declare(strict_types=1);

namespace SkyDiablo\ReactphpInfluxDB\API;

use Clue\React\Csv\AssocDecoder;
use Psr\Http\Message\ResponseInterface;
use React\Http\Message\ResponseException;
use React\Promise\Deferred;
use React\Stream\ReadableStreamInterface;
use React\Stream\ThroughStream;
use SkyDiablo\ReactphpInfluxDB\Client;
use function React\Promise\resolve;

class FluxQuery
{

    const BASE_ENDPOINT = '/api/v2/query';

    /**
     * @param Client $client
     */
    public function __construct(protected Client $client)
    {
    }

    public function defaultBucket(): string
    {
        return $this->client->getBucket();
    }

    protected function defaultHeader(): array
    {
        return [
            'Accept' => 'application/csv',
            'Content-type' => 'application/vnd.flux'
        ];
    }

    public function __invoke(string $flux): \React\Promise\PromiseInterface
    {
        return $this->client->post($flux, self::BASE_ENDPOINT, [], $this->defaultHeader())->then(function (ResponseInterface $response) {
            return $this->parseResponse($response);
        }, function (\Throwable $e) {
            if ($e instanceof ResponseException) {
                $deferred = new Deferred();
                $buffer = '';
                $e->getResponse()->getBody()->on('data', function ($data) use (&$buffer) {
                    $buffer .= $data;
                })->on('end', function () use ($deferred, &$buffer, $e) {
                    $decoded = json_decode($buffer, true);
                    $deferred->reject(new \RuntimeException($decoded['message'] ?? $buffer, $e->getResponse()->getStatusCode(), $e));
                });
                return $deferred->promise();
            } else {
                throw $e;
            }
        });
    }

    protected function parseResponse(ResponseInterface $response): \React\Promise\PromiseInterface|\React\Promise\Promise
    {
        $deferred = new Deferred();
        $buffer = [];

        /** @var ReadableStreamInterface $body */
        $body = $response->getBody();

        $body->on('end', function () use (&$buffer, &$csv) {
            if(!$buffer) {
                // InfluxDB returns empty response if no data is found,
                // so we need to set a non-empty dummy header to prevent
                // exception in AssocDecoder class
                $csv->handleData(['dummy' => true]);
            }
        });

        //prepare and cleanup response from InfluxDB
        $body = $body->pipe(new ThroughStream(function (string $raw) {
            $raw = preg_replace('/^#.*$/m', '', $raw); //remove comment lines
            $raw = str_replace("\r\n\r\n", "\r\n", $raw); //remove double line breaks
            $raw = $raw === "\r\n" ? '' : $raw; //remove empty line
            return $raw;
        }));

        /** @var ReadableStreamInterface $body */
        $csv = new AssocDecoder($body);
        $csv->on('data', function (array $data) use (&$buffer) {
            $buffer[] = $data;
        });
        $csv->on('end', function () use ($deferred, &$buffer) { //TODO: maybe switch to "close" event?
            $deferred->resolve($buffer);
        });
        $csv->on('error', function (\Exception $error) use ($deferred) {
            $deferred->reject($error);
        });

        return $deferred->promise();
    }


}