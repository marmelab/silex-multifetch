<?php

namespace Marmelab\Multifetch;

use Silex\Application;
use Silex\ServiceProviderInterface;

class MultifetchServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['multifetch.url'] = 'multi';

        $app['multifetch.builder'] = function () use ($app) {
            $controllers = $app['controllers_factory'];

            $controllers->get('/', function () use ($app) {
                $responses = array();

                foreach ($app['request']->query->all() as $resource => $url) {
                    $response = $app['fragment.renderer.inline']->render($url, $app['request']);

                    $headers = array();
                    foreach ($response->headers->all() as $name => $value) {
                        $headers[] = array('name' => $name, 'value' => current($value));
                    }

                    $responses[$resource] = array(
                        'code' => $response->getStatusCode(),
                        'headers' => $headers,
                        'body' => $response->getContent(),
                    );
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