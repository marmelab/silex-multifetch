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
));
```

## Tests

Run the tests suite with the following commands:

```bash
make install
make test
```

## Usage

Send a request to the route where the provider is listening, passing the requests to fetch in parallel as a JSON object in the request body. For instance, to fetch `/products/1` and `/users` in parallel, make the following request:

```
GET /multi?product=/product/1&all_users=/users HTTP/1.1
```

The provider will call both HTTP resources, and return a response with a composite body once all the requests are fetched:

```json
{ 
    "product": {
        "code":"200",
        "headers":[
            { "name": "Content-Type", "value": "application/json" }
        ],
        "body": "{ id: 1, name: \"ipad2\", stock: 34 }"
    },
    "all_users": {
        "code":"200",
        "headers":[
            { "name": "Content-Type", "value": "application/json" }
        ],
        "body": "[{ id: 2459, login: \"paul\" }, { id: 7473, login: \"joe\" }]"
    }
}
```

Any header present in the multifetch request will be automatically added to all sub-requests.

## License

Silex Multifetch is licensed under the [MIT License](LICENSE), courtesy of [marmelab](http://marmelab.com).