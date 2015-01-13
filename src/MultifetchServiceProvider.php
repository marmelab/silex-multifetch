<?php

namespace Marmelab\Multifetch;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class MultifetchServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['multifetch.url'] = 'multi';
        $app['multifetch.parallel'] = false;

        $app['multifetch.builder'] = function () use ($app) {
            $controllers = $app['controllers_factory'];
            $renderer = function ($url) use ($app) {
                return $app['fragment.renderer.inline']->render($url, $app['request']);
            };
            $multifetcher = new Multifetcher();
            $parallelize = (bool) $app['multifetch.parallel'];

            $controllers
                ->get('/', function (Application $app) use ($multifetcher, $renderer, $parallelize) {
                    $responses = $multifetcher->fetch($app['request']->query->all(), $renderer, $parallelize);

                    return $app->json($responses);
                })
            ;

            $controllers
                ->post('/', function (Application $app) use ($multifetcher, $renderer, $parallelize) {
                    $responses = $multifetcher->fetch($app['request']->request->all(), $renderer, $parallelize);

                    return $app->json($responses);
                })
                ->before(function (Request $request) {
                    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
                        $data = json_decode($request->getContent(), true);
                        $request->request->replace(is_array($data) ? $data : array());
                    }
                })
            ;

            $app['controllers']->mount($app['multifetch.url'], $controllers);
        };
    }

    public function boot(Application $app)
    {
        $app['multifetch.builder'];
    }
}
