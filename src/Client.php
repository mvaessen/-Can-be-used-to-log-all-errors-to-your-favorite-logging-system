<?php

namespace Mvaessen\BinanceApi;

/**
 * Reference implementation for Binance's REST API.
 *
 * See https://github.com/binance-exchange/binance-official-api-docs/blob/master/rest-api.md for more info.
 *
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2018 Max Vaessen
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class BinanceApiException extends \ErrorException {};

class Client
{
    private $key;
    private $secret;

    protected $url;
    protected $version;
    protected $curl;

    public $recvWindow = 5000;

    /**
     * BinanceApi constructor.
     *
     * @param        $key
     * @param        $secret
     * @param string $url
     * @param string $version
     * @param bool   $sslverify
     */
    public function __construct(
        $key,
        $secret,
        $url = 'https://api.binance.com/api',
        $version = 'v3',
        $sslverify = true
    ) {
        $this->key = $key;
        $this->secret = $secret;
        $this->url = $url;
        $this->version = $version;
        $this->curl = curl_init();

        curl_setopt_array($this->curl, array(
            CURLOPT_SSL_VERIFYPEER => $sslverify,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'Binance PHP API wrapper',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 20
        ));
    }

    /**
     * @return mixed
     */
    public function account()
    {
        return $this->queryPrivate('get', 'account');
    }

    /**
     * @return array
     * @throws BinanceApiException
     */
    public function balance()
    {
        $result = $this->account();

        if (! isset($result['balances'])) {
            throw new BinanceApiException('Missing balances data');
        }

        $output = [];

        foreach ($result['balances'] as $item) {
            $output[$item['asset']] = [
                'free'   => $item['free'],
                'locked' => $item['locked']
            ];
        }

        return $output;
    }

    /**
     * @param bool $symbol
     *
     * @return array
     */
    public function price($symbol = false)
    {
        $request = [];

        if ($symbol) {
            $request['symbol'] = $symbol;
        }

        $result = $this->queryPublic('get', 'ticker/price', $request);

        if (isset($result['symbol'])) {
            return $result['price'];
        }

        $output = [];

        foreach ($result as $item) {
            $output[$item['symbol']] = $item['price'];
        }

        return $output;
    }

    /**
     * @return array
     */
    public function markets()
    {
        $result = $this->queryPublic('get', 'ticker/bookTicker');

        $output = [];

        foreach ($result as $item) {
            $output[$item['symbol']] = [
                'bidPrice' => $item['bidPrice'],
                'bidQty'   => $item['bidQty'],
                'askPrice' => $item['askPrice'],
                'askQty'   => $item['askQty'],
            ];
        }

        return $output;
    }

    /**
     * @param      $symbol
     * @param      $quantity
     * @param bool $test
     *
     * @return mixed
     */
    public function buyMarket(
        $symbol,
        $quantity,
        $test = false
    ) {
        $url = 'order';

        if ($test) {
            $url .= '/test';
        }

        $request = [
            'side'     => 'BUY',
            'symbol'   => $symbol,
            'type'     => 'MARKET',
            'quantity' => $quantity
        ];

        return $this->queryPrivate('post', $url, $request);
    }

    public function sellMarket(
        $symbol,
        $quantity,
        $test = false
    ) {
        $url = 'order';

        if ($test) {
            $url .= '/test';
        }

        $request = [
            'side'     => 'SELL',
            'symbol'   => $symbol,
            'type'     => 'MARKET',
            'quantity' => $quantity
        ];

        return $this->queryPrivate('post', $url, $request);
    }

    /**
     * queryPublic should be used for all public calls, which do not require the AUTH header
     *
     * @param       $method
     * @param       $url
     * @param array $request
     *
     * @return mixed
     */
    public function queryPublic(
        $method,
        $url,
        array $request = array()
    ) {
        try {
            return $this->request(
                $method,
                $url,
                $request,
                false
            );
        } catch (BinanceApiException $e) {
            return $this->processException($e, $method, $url, $request);
        }
    }

    /**
     * queryPrivate should be used for all calls that require the AUTH header to be present
     *
     * @param       $method
     * @param       $url
     * @param array $request
     *
     * @return mixed
     */
    public function queryPrivate(
        $method,
        $url,
        array $request = array()
    ) {
        try {
            if(!isset($request['recvWindow'])) {
                $request['recvWindow'] = $this->recvWindow;
            }

            return $this->request(
                $method,
                $url,
                $request,
                true
            );
        } catch (BinanceApiException $e) {
            return $this->processException($e, $method, $url, $request);
        }
    }

    /**
     * @param $response
     * @param $method
     * @param $url
     * @param $request
     *
     * Can be used to log all errors to your favorite logging system
     *
     * @throws BinanceApiException
     */
    protected function processErrorCode($response, $method, $url, $request)
    {
        throw new BinanceApiException($response['msg']);
    }

    /**
     * @param $e
     * @param $method
     * @param $url
     * @param $request
     *
     * Can be used to log all errors to your favorite logging system
     *
     * @return mixed
     */
    protected function processException($e, $method, $url, $request)
    {
        throw new BinanceApiException($e->getMessage());
    }

    /**
     * @param       $method
     * @param       $url
     * @param array $request
     * @param bool  $signed to enable the auth headers for the request
     *
     * @return mixed
     * @throws BinanceApiException
     */
    private function request(
        $method,
        $url,
        array $request = array(),
        $signed = false
    ) {
        $query = http_build_query($request, '', '&');

        //determin & set request path
        $path = $this->url . '/' . $this->version . '/' . $url;

        //set auth header
        if ($signed) {
            $timestamp = microtime(true) * 1000;
            $query .= '&timestamp=' . number_format($timestamp, 0, '.', '');

            $signature = hash_hmac('sha256', $query, $this->secret);
            $query .= '&signature=' . $signature;

            curl_setopt($this->curl, CURLOPT_HTTPHEADER, array(
                'X-MBX-APIKEY: ' . $this->key,
            ));
        }

        if ($query) {
            $path .= '?' . $query;
        }

        curl_setopt($this->curl, CURLOPT_URL, $path);

        //set method
        switch ($method) {
            case 'POST':
            case 'post':
                curl_setopt($this->curl, CURLOPT_POSTFIELDS, $request);
                curl_setopt($this->curl, CURLOPT_POST, true);
                break;

            case 'DELETE':
            case 'delete':
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;

            case 'GET':
            case 'get':
                break;

            default:
                throw new BinanceApiException('Unsupported method');
        }

        //execute request
        $result = curl_exec($this->curl);
        $httpcode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        if ($result === false) {
            throw new BinanceApiException('CURL error: ' . curl_error($this->curl), $httpcode);
        }

        if ($httpcode != 200) {
            throw new BinanceApiException($result, $httpcode);
        }

        // decode results
        $output = json_decode($result, true);
        if (! is_array($output)) {
            throw new BinanceApiException('JSON decode error');
        }

        if (isset($output['code'])) {
            return $this->processErrorCode($output, $method, $url, $request);
        }

        return $output;
    }
}