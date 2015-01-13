<?php

namespace Marmelab\Multifetch;

use KzykHys\Parallel\Parallel;

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
                $response = $renderer($url);

                $headers = array();
                foreach ($response->headers->all() as $name => $value) {
                    $headers[] = array('name' => $name, 'value' => current($value));
                }

                return array(
                    'code' => $response->getStatusCode(),
                    'headers' => $headers,
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
}
