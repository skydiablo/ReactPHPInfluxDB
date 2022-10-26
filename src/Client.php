<?php
declare(strict_types=1);

namespace SkyDiablo\ReactphpInfluxDB\InfluxDB;

use React\Http\Browser as HttpClient;

class Client
{

    public function __construct(
        protected string     $baseUri,
        protected string     $org,
        protected string     $bucket,
        protected string     $token,
        protected HttpClient $http = new HttpClient())
    {
    }

    public function post(string $data, string $uri, array $queryParams = [], array $headers = []): \React\Promise\PromiseInterface
    {
        return $this->http->requestStreaming('POST', $this->generateUri($uri, $queryParams), $this->defaultHeader($headers), $data);
    }

    protected function generateUri(string $endpoint, array $queryParams = []): string
    {
        $queryParams = ["org" => $this->org, "bucket" => $this->bucket] + $queryParams;
        return rtrim($this->baseUri, '/') . '/' . ltrim($endpoint, '/') . '?' . http_build_query($queryParams);
    }

    protected function defaultHeader(array $header = []): array
    {
        return [
                'Authorization' => 'Token ' . $this->token,
            ] + $header;
    }

    public function getBucket(): string
    {
        return $this->bucket;
    }

}