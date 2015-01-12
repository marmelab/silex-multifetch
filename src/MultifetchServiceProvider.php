<?php

namespace Marmelab\Multifetch;

use Silex\Application;
use Silex\ServiceProviderInterface;
use KzykHys\Parallel\Parallel;

class MultifetchServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['multifetch.url'] = 'multi';
        $app['multifetch.parallelize'] = false;

        $app['multifetch.builder'] = function () use ($app) {
            $controllers = $app['controllers_factory'];

            $controllers->get('/', function () use ($app) {
                $parameters = $app['request']->query->all();

                $parallelized = (bool) $app['multifetch.parallelize'];
                if (isset($parameters['_parallelized'])) {
                    $parallelized = (bool) $parameters['_parallelized'];
                    unset($parameters['_parallelized']);
                }

                if ($parallelized && !class_exists('\KzykHys\Parallel\Parallel')) {
                    throw new \RuntimeException('"kzykhys/parallel" library is required. To install it, run "composer require kzykhys/parallel 0.1"');
                }

                $requests = array();
                foreach ($parameters as $resource => $url) {
                    $requests[$resource] = function () use ($resource, $url, $app) {
                        $response = $app['fragment.renderer.inline']->render($url, $app['request']);

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

                if ($parallelized) {
                    $parallel = new Parallel();
                    $responses = $parallel->values($requests);

                } else {
                    foreach ($requests as $resource => $callback) {
                        $responses[$resource] = $callback();
                    }
                }

                return $app->json($responses);
            })
            ->bind('multifetch')
            ;

            $app['controllers']->mount($app['multifetch.url'], $controllers);
        };
    }

    public function boot(Application $app)
    {
        $app['multifetch.builder'];
    }
}