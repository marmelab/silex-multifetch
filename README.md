# Marmelab Silex Multifetch

Multifetch is a Silex provider which adds multifetch capabilities to any Silex project. Based on [Facebook's Batch Requests philosophy](https://developers.facebook.com/docs/graph-api/making-multiple-requests).

## Installation

Use Composer to install the package in your project:

```bash
composer require marmelab/silex-multifetch "~1.0@dev"
```

Enable `HttpFragmentServiceProvider` in your application:

```php
$app->register(new Silex\Provider\HttpFragmentServiceProvider());

$app->register(new Marmelab\Multifetch\MultifetchServiceProvider(), array(
    'multifetch.url' => 'multi', // this is the default value
    'multifetch.methods' => array('POST'), // this is the default value
    'multifetch.parallel' => false, // this is the default value
    'multifetch.headers' => true, // this is the default value
));
```

## Usage

Send a request to the route where the provider is listening ('/multi' by default), and pass the requests to be fetched as a JSON object in the request body. For instance, to fetch `/products/1` and `/users` with a single HTTP request, make the following request:

```
POST /multi HTTP/1.1
Content-Type: application/json
{
    "product": "/products/1",
    "all_users": "/users"
}
```

The provider will call both HTTP resources, and return a response with a composite body once all the requests are fetched:

```json
{ 
    "product": {
        "code": 200,
        "headers": [
            { "name": "Content-Type", "value": "application/json" }
        ],
        "body": "{ id: 1, name: \"ipad2\", stock: 34 }"
    },
    "all_users": {
        "code": 200,
        "headers": [
            { "name": "Content-Type", "value": "application/json" }
        ],
        "body": "[{ id: 2459, login: \"paul\" }, { id: 7473, login: \"joe\" }]"
    },
}
```

Any header present in the multifetch request will be automatically added to all sub-requests.

### Request method

By default, the '/multi' route listens only for POST requests. However, you can configure the provider to also (or only) accept `GET` requests. To enable it, just set the `multifetch.methods` provider configuration to `array('GET')`.

The provider then reads the query parameters to determine the requests to fetch:

```
GET /multi?product=/product/1&all_users=/users HTTP/1.1
```

If you want to enable both `POST` and `GET` routes, set `array('POST', 'GET')` value a `multifetch.methods` value.

### Parallelize requests

A multifetch request can fetche subrequests in parallel, if you add the `_parallel` parameter:

```
POST /multi HTTP/1.1
Content-Type: application/json
{
    "product": "/products/1",
    "all_users": "/users",
    "_parallel": true
}
```

You can also, if you want, enable parallel fetching for all queries by setting the `'mutltifetch.parallel'` provider parameter to `true`. In that case, if you want to disable parallel fetching for only one query, you can do:

```
POST /multi HTTP/1.1
Content-Type: application/json
{
    "product": "/products/1",
    "all_users": "/users",
    "_parallel": false
}
```

**Warning**: The `parallel` option forks a new thread for each sub-request, which may or may not be faster than executing all requests in series, depending on your usage scenario, and the amount of I/O spent in the subrequests.

### Removing headers from the response

You may want to remove `headers` from the response for more efficiency. Set the `_headers` parameter to `false` in your query:

```
POST /multi HTTP/1.1
Content-Type: application/json
{
    "product": "/products/1",
    "all_users": "/users",
    "_headers": false
}
```

You can also remove `headers` from the response for all your queries by setting `multifetch.headers` to `false` in the provider configuration.

### Errors

It's possible that one of your requested operation may throw an error. A similar response will be returned, but with a custom status and body. Successfull requests will be returned, as normal, with a 200 status code.

Here is a response example:

```
POST /multi HTTP/1.1
Content-Type: application/json
{
    "product": "/products/1",
    "all_users": "/non_existing_route",
    "single_user": "/users/brian" // will trigger a 500 error
}
```

```json
{
    "product": {
        "code": 200,
        "headers": [
            { "name": "Content-Type", "value": "application/json" }
        ],
        "body": "{ id: 1, name: \"ipad2\", stock: 34 }"
    },
    "all_users": {
        "code": 404,
        "headers": [],
        "body": "{ error: \"No route found for \\\"GET \\\/non_existing_route\\\"\", type: \"NotFoundHttpException\" }"
    },
    "single_user": {
        "code": 500,
        "headers": [],
        "body": "{ error: \"Oops! Something went wrong.\", type: \"InternalServerError\" }"
    },
}
```

## Tests

Run the tests suite with the following commands:

```bash
make install
make test
```

## License

Silex Multifetch is licensed under the [MIT License](LICENSE), courtesy of [marmelab](http://marmelab.com).
