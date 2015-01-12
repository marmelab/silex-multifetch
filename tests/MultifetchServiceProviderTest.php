<?php

namespace Marmelab\MultifetchTest;

use Marmelab\Multifetch\MultifetchServiceProvider;
use Silex\Application;
use Silex\Provider\HttpFragmentServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MultifetchServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testMultifetch()
    {
        $app = new Application();
        $app['debug'] = true;

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider());

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $request = Request::create('/multi/?one=/url1&two=/url2');
        $response = $app->handle($request);

        $responses = $this->getReponsesAsArray($response);
        $this->assertEquals(array(
            'one' => array(
                'code' => 200,
                'headers' =>
                array(
                    array(
                        'name' => 'cache-control',
                        'value' => 'no-cache',
                    ),
                    array(
                        'name' => 'content-type',
                        'value' => 'application/json',
                    ),
                ),
                'body' => '{"field_1_1":"value_1_1","field_2":2}',
            ),
            'two' => array (
                'code' => 200,
                'headers' =>
                array(
                    array(
                        'name' => 'cache-control',
                        'value' => 'no-cache',
                    ),
                    array (
                        'name' => 'content-type',
                        'value' => 'application/json',
                    ),
                ),
                'body' => '{"field_2_1":"value_2_1","field_2":3}',
            ),
        ), $responses);

        $request = Request::create('/multi/?one=/url2&two=/url1');
        $response = $app->handle($request);

        $responses = $this->getReponsesAsArray($response);
        $this->assertEquals(array(
            'one' => array(
                'code' => 200,
                'headers' =>
                array(
                    array(
                        'name' => 'cache-control',
                        'value' => 'no-cache',
                    ),
                    array(
                        'name' => 'content-type',
                        'value' => 'application/json',
                    ),
                ),
                'body' => '{"field_2_1":"value_2_1","field_2":3}',
            ),
            'two' => array (
                'code' => 200,
                'headers' =>
                array(
                    array(
                        'name' => 'cache-control',
                        'value' => 'no-cache',
                    ),
                    array (
                        'name' => 'content-type',
                        'value' => 'application/json',
                    ),
                ),
                'body' => '{"field_1_1":"value_1_1","field_2":2}',
            ),
        ), $responses);
    }

    private function getReponsesAsArray(Response $response)
    {
        $responses = json_decode($response->getContent(), true);
        foreach ($responses as $key => $singleResponse) {
            $headers = array();
            foreach ($singleResponse['headers'] as $header) {
                if ('date' === $header['name']) {
                    continue;
                }

                $headers[] = $header;
            }
            $responses[$key]['headers'] = $headers;
        }

        return $responses;
    }
}
