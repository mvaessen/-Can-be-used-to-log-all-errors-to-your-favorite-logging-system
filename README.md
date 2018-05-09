# Binance API Wrapper PHP
Simple Binance API Wrapper, written in PHP. Includes some basic methods to work with the API and assumes you know your way around. Check out the [Binance API Documentation](https://github.com/binance-exchange/binance-official-api-docs) for more information about the available endpoints. Will throw an exception when errors are encountered.

### Please note:
- Does NOT include mechanism to intercept rate limit.
- Comes without any support.
- Use at your own risk.

#### Getting started
`composer require mvaessen/binance-api`
```php
require 'vendor/autoload.php';
$api = new Mvaessen\BinanceApi\Client('<api key>','<secret>');
```


#### Get account information
```php
$result = $api->account();
print $result;
```

##### Public endpoint call
```php
$result = $api->queryPublic('<method>', '<endpoint>', '<request>');
```

##### Private endpoint call
```php
$result = $api->queryPrivate('<method>', '<endpoint>', '<request>');
```


## Custom error reporting
You can choose to overwrite the `processErrorCode` and `processException` methods to report the errors to your favorite bugreporting software.

```
<?php
namespace App;

use Mvaessen\BinanceApi\BinanceApiException;
use Mvaessen\BinanceApi\Client;

class BinanceApi extends Client
{
   protected function processErrorCode($response, $method, $url, $request)
   {
       //todo report to bugtracking software

       throw new BinanceApiException($response['msg']);
   }
   
   protected function processException($e, $method, $url, $request)
   {
       //todo report to bugtracking software

       throw new BinanceApiException($e->getMessage());
   }
}
```