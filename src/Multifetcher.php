<?php

namespace Marmelab\Multifetch;

use KzykHys\Parallel\Parallel;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Multifetcher
{
    public function fetch(array $parameters, $renderer, array $options = array())
    {
        $options = array_replace(array(
            'parallel' => false,
            'headers' => true,
        ), $options);

        foreach ($options as $name => $value) {
            if (isset($parameters['_'.$name])) {
                $options[$name] = $parameters['_'.$name];
                unset($parameters['_'.$name]);
            }
        }

        if ($options['parallel'] && !class_exists('\KzykHys\Parallel\Parallel')) {
            throw new \RuntimeException(
                '"tiagobutzke/phparallel" library is required to execute requests in parallel.
                To install it, run `composer require tiagobutzke/phparallel "~0.1"`'
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

        if ($options['parallel']) {
            $parallel = new Parallel();

            $responses = $parallel->values($requests);

        } else {
            foreach ($requests as $resource => $callback) {
                $responses[$resource] = $callback();
            }
        }

        if (!$options['headers']) {
            array_walk($responses, function (&$value) {
                unset($value['headers']);
            });
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
