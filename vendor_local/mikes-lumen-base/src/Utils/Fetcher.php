<?php

namespace MikesLumenBase\Utils;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\MessageBag;

class Fetcher
{
    /**
     * Make an http request to other services.
     *
     * @param  string
     * @param  string
     * @param  string
     * @param  array
     * @param  array
     * @param  boolean
     * @return array
     */
    public function fetch(string $method, string $baseUri, string $path, $body = [], $headers = [], $isMultipart = false)
    {
        \Log::info('[' . $method . '] ' . $baseUri . $path);

        if (getenv('APP_ENV') == 'testing') {
            // Prevent internal request on testing for safe.
            // Instead use mock for testing purpose.
            return [];
        }

        $res = null;
        if ($isMultipart) {
            $res = $this->multipartRequest($baseUri, $path, $body, $headers);
        } else {
            $res = $this->request($baseUri, $method, $path, $body, $headers);
        }
        return json_decode((string)$res->getBody(), true);
    }

    public function get(string $baseUri, string $path, $body = [], $headers = [])
    {
        return $this->fetch('GET', $baseUri, $path, $body, $headers, false);
    }

    public function encodeSearchData($searchData)
    {
        $search = [];
        foreach ($searchData as $key => $value) {
            $search[] = $key . ':' . urlencode($value);
        }
        return implode(';', $search);
    }

    public function post(string $baseUri, string $path, $body = [], $headers = [], $isMultipart = false)
    {
        return $this->fetch('POST', $baseUri, $path, $body, $headers, $isMultipart);
    }

    public function put(string $baseUri, string $path, $body = [], $headers = [])
    {
        return $this->fetch('PUT', $baseUri, $path, $body, $headers);
    }

    public function delete(string $baseUri, string $path, $body = [], $headers = [])
    {
        return $this->fetch('DELETE', $baseUri, $path, $body, $headers);
    }

    public function safeDelete(string $baseUri, string $path, $body = [], $headers = [])
    {
        try {
            return $this->fetch('DELETE', $baseUri, $path, $body, $headers);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::info('[ALREADY DELETED] ' . $baseUri . $path);
        }
    }

    private static function request(string $baseUri, $method = 'GET', string $path = '', $body = [], $headers = [])
    {
        $client = new Client(['base_uri' => $baseUri]);
        $method = strtoupper($method);

        $requestKey = 'body';
        switch ($method) {
            case 'GET':
                $requestKey = 'query';
                break;
            case 'POST':
            case 'DELETE':
            case 'PUT':
                $requestKey = 'form_params';
                break;
            default:
                break;
        }

        $params = [$requestKey => $body];
        if ($headers) {
            $params['headers'] = $headers;
        }

        try {
            return $client->request($method, $path, $params);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                $body = json_decode($response->getBody(), true);
                if (!empty($body['code'])) {
                    switch ($body['code']) {
                        case 'database.model_not_found':
                            throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
                        case 'repository.validation_failed':
                            $messageBag = new MessageBag();
                            foreach ($body['errors'] as $error) {
                                $messageBag->add($error['field'], $error['message']);
                            }
                            throw new \Prettus\Validator\Exceptions\ValidatorException($messageBag);
                    }
                }
            }
            throw $e;
        }
    }

    private static function multipartRequest(string $baseUri, string $path, $body = [], $headers = [])
    {
        $client = new Client(['base_uri' => $baseUri]);
        $method = strtoupper('POST');

        $params = [];
        foreach ($body as $key => $value) {
            if (gettype($value) == 'object') {
                $params[] = [
                    'name' => $key,
                    'contents' => fopen($value, 'r')
                ];
            } else {
                $params[] = [
                    'name' => $key,
                    'contents' => $value
                ];
            }
        }
        if ($headers) {
            $params[]['headers'] = $headers;
        }

        return $client->request($method, $path, ['multipart' => $params]);
    }
}
