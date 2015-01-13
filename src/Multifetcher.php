<?php

namespace Marmelab\Multifetch;

use KzykHys\Parallel\Parallel;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Multifetcher
{
    public function fetch(array $parameters, $renderer, $parallelize = false)
    {
        if (isset($parameters['_parallel'])) {
            $parallelize = (bool) $parameters['_parallel'];
            unset($parameters['_parallel']);
        }

        if ($parallelize && !class_exists('\KzykHys\Parallel\Parallel')) {
            throw new \RuntimeException(
                '"kzykhys/parallel" library is required to execute requests in parallel.
                To install it, run "composer require kzykhys/parallel 0.1"'
            );
        }

        $requests = array();
        foreach ($parameters as $resource => $url) {
            $requests[$resource] = function () use ($resource, $url, $renderer) {
                try {
                    $response = $renderer($url);

                } catch (HttpException $e) {
                    $reflectionClass = new \ReflectionClass($e);
                    $type = $reflectionClass->getShortName();

                    return array(
                        'code' => $e->getStatusCode(),
                        'headers' => $this->formatHeaders($e->getHeaders()),
                        'body' => json_encode(array('error' => $e->getMessage(), 'type' => $type)),
                    );
                } catch (\Exception $e) {

                    return array(
                        'code' => 500,
                        'headers' => array(),
                        'body' => json_encode(array('error' => $e->getMessage(), 'type' => 'InternalServerError')),
                    );
                }

                return array(
                    'code' => $response->getStatusCode(),
                    'headers' => $this->formatHeaders($response->headers->all()),
                    'body' => $response->getContent(),
                );
            };
        }

        if ($parallelize) {
            $parallel = new Parallel();

            return $parallel->values($requests);
        }

        foreach ($requests as $resource => $callback) {
            $responses[$resource] = $callback();
        }

        return $responses;
    }

    private function formatHeaders(array $headers)
    {
        return array_map(function ($name, $value) {
            return array('name' => $name, 'value' => current($value));
        }, array_keys($headers), $headers);
    }
}
