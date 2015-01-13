<?php

namespace Marmelab\MultifetchTest;

use Marmelab\Multifetch\MultifetchServiceProvider;
use Silex\Application;
use Silex\Provider\HttpFragmentServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class MultifetchServiceProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testMultifetchGet()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider());

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $request = Request::create('/multi/', 'GET', array('one' => '/url1', 'two' => '/url2'));
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
    }

    public function testMultifetchPost()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider());

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $content = json_encode(array('one' => '/url1', 'two' => '/url2'));
        $server = array('CONTENT_TYPE' => 'application/json');
        $request = Request::create('/multi/', 'POST', array(), array(), array(), $server, $content);
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
    }

    public function testMultifetchCustomUrl()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider(), array(
            'multifetch.url' => '',
        ));

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $request = Request::create('/', 'GET', array('one' => '/url1', 'two' => '/url2'));
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
    }

    public function testMultifetchRespectOrder()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider());

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $request = Request::create('/multi/', 'GET', array('one' => '/url2', 'two' => '/url1'));
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

    public function testMultifetchParallelConfig()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider(), array(
            'multifetch.parallel' => 1,
        ));

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $request = Request::create('/multi/', 'GET', array('one' => '/url1', 'two' => '/url2'));
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
    }

    public function testMultifetchParallelParameter()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider());

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $request = Request::create('/multi/', 'GET', array('one' => '/url1', 'two' => '/url2', '_parallel' => 1));
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
    }

    public function testMultifetchParallelConfigOveriden()
    {
        $app = new Application();

        $app->register(new HttpFragmentServiceProvider());
        $app->register(new MultifetchServiceProvider(), array(
            'multifetch.parallel' => 1,
        ));

        $app->get('/url1', function () use ($app) {
            return $app->json(array('field_1_1' => 'value_1_1', 'field_2' => 2));
        });

        $app->get('/url2', function () use ($app) {
            return $app->json(array('field_2_1' => 'value_2_1', 'field_2' => 3));
        });

        $request = Request::create('/multi/', 'GET', array('one' => '/url1', 'two' => '/url2', '_parallel' => 0));
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
