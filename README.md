# Marmelab Silex Multifetch

Multifetch is a Silex provider which add multifetch capabilities to your Silex project. Based on [Facebook's Batch Requests philosophy](https://developers.facebook.com/docs/graph-api/making-multiple-requests).

## Installation

To install Silex Multifetch provider, run the command below and you will get the latest version:

```bash
composer require marmelab/silex-multifetch "~1.0@dev"
```

Enable `HttpFragmentServiceProvider` in your application:

```php
$app->register(new Silex\Provider\HttpFragmentServiceProvider());

$app->register(new Marmelab\Multifetch\MultifetchServiceProvider(), array(
    'multifetch.url' => 'multi', // this is the default value
    'multifetch.parallel' => false, // this is the default value
));
```

## Tests

Run the tests suite with the following commands:

```bash
make install
make test
```

## Usage

Send a request to the route where the provider is listening, passing the requests to fetch as a JSON object in the request body. For instance, to fetch `/products/1` and `/users` with a single HTTP request, make the following request:

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
        "code": 200,                // Response code returned by the user route
        "headers": [                                    // All response headers
            { "name": "Content-Type", "value": "application/json" }
        ],
        "body": "{ id: 1, name: \"ipad2\", stock: 34 }" // The actual json body
    },
    "all_users": {
        "code": 200,
        "headers": [
            { "name": "Content-Type", "value": "application/json" }
        ],
        "body": "[{ id: 2459, login: \"paul\" }, { id: 7473, login: \"joe\" }]"
    },
    "_error": false        // _error will be true if one of the requests failed
}
```

Any header present in the multifetch request will be automatically added to all sub-requests.

**Tip**: a GET route is available, the provider reads the query parameters to determine requests to fetch:

```
GET /multi?product=/product/1&all_users=/users&_parallel=1 HTTP/1.1
```

# Parallelize requests

You can also fetch all requests in parallel using the `_parallel`  parameter:

```
POST /multi HTTP/1.1
Content-Type: application/json
{
    "product": "/products/1",
    "all_users": "/users",
    "_parallel": true
}
```

You can also, if you want, enable parallel fetching for all queries by setting `'mutltifetch.parallel'` provider parameter to `true`. But in that case, if you want to disable parallelizing for only one query, you can do:

```
POST /multi HTTP/1.1
Content-Type: application/json
{
    "product": "/products/1",
    "all_users": "/users",
    "_parallel": false
}
```

**Warning**: The `parallel` option forks a new thread for each sub-request, which may or may not be faster than executing all requests in series, depending on your usage scenario, and the amount of I/O spend in the subrequests.

## License

Silex Multifetch is licensed under the [MIT License](LICENSE), courtesy of [marmelab](http://marmelab.com).