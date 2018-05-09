<?php
namespace Mvaessen\BinanceApi\Tests;

use Dotenv\Dotenv;
use Mvaessen\BinanceApi\BinanceApiException;
use PHPUnit\Framework\TestCase;
use Mvaessen\BinanceApi\Client;

class BasicApiTest extends TestCase
{
    protected $api;

    protected function setUp()
    {
        $dotenv = new Dotenv(__DIR__ . '/..');
        $dotenv->load();

        $this->api = new Client(
            getenv('BINANCE_API_ID'),
            getenv('BINANCE_API_SECRET')
        );
    }

    public function testBalance()
    {
        $result = $this->api->balance();
        $this->assertTrue(isset($result['BTC']));
    }

    public function testReceiveAllPrices()
    {
        $result = $this->api->price();

        $this->assertTrue(
            count($result) > 0 and
            isset($result['ZECBTC']) and
            $result['ZECBTC'] > 0
        );
    }

    public function testReceiveIndividualPrice()
    {
        $result = $this->api->price('ZECBTC');

        $this->assertTrue(is_numeric($result));
    }

    public function testOrder()
    {
        $result = $this->api->buyMarket(
            'ZECBTC',
            10,
            true
        );

        $this->assertTrue(is_array($result) and !count($result));
    }

    public function testSmallOrder()
    {
        $fail = true;

        try {
            $this->api->sellMarket(
                'ZECBTC',
                0.0000001
            );
        } catch(BinanceApiException $e) {
            $this->assertContains('legal range is', $e->getMessage());
            $fail = false;
        }

        $this->assertFalse($fail);
    }

    public function testAvailablePairs()
    {
        $result = $this->api->markets();

        $this->assertTrue(
            count($result) > 0 and
            isset($result['ZECBTC']['bidPrice']) and
            $result['ZECBTC']['bidPrice'] > 0
        );
    }
}