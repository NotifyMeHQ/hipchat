<?php

/*
 * This file is part of NotifyMe.
 *
 * (c) Cachet HQ <support@cachethq.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace NotifyMeHQ\Hipchat;

use GuzzleHttp\Client;
use NotifyMeHQ\NotifyMe\Arr;
use NotifyMeHQ\NotifyMe\GatewayInterface;
use NotifyMeHQ\NotifyMe\HttpGatewayTrait;
use NotifyMeHQ\NotifyMe\Response;

class HipchatGateway implements GatewayInterface
{
    use HttpGatewayTrait;

    /**
     * Gateway api endpoint.
     *
     * @var string
     */
    protected $endpoint = 'https://api.hipchat.com';

    /**
     * Hipchat api version.
     *
     * @var string
     */
    protected $version = 'v2';

    /**
     * Hipchat message background colours.
     *
     * @var string[]
     */
    protected $colours = [
        'yellow',
        'red',
        'gray',
        'green',
        'purple',
        'random',
    ];

    /**
     * The http client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * Configuration options.
     *
     * @var string[]
     */
    protected $config;

    /**
     * Create a new hipchat gateway instance.
     *
     * @param \GuzzleHttp\Client $client
     * @param string[]           $config
     *
     * @return void
     */
    public function __construct(Client $client, array $config)
    {
        $this->client = $client;
        $this->config = $config;
    }

    /**
     * Send a notification.
     *
     * @param string   $to
     * @param string   $message
     * @param string[] $options
     *
     * @return \NotifyMeHQ\NotifyMe\Response
     */
    public function notify($to, $message, array $options = [])
    {
        $options['to'] = $to;

        $params = $this->addMessage($message, $params, $options);

        return $this->commit('post', $this->buildUrlFromString("room/{$to}/message"), $params);
    }

    /**
     * Add a message to the request.
     *
     * @param string   $message
     * @param string[] $params
     * @param string[] $options
     *
     * @return array
     */
    protected function addMessage($message, array $params, array $options)
    {
        $params['auth_token'] = Arr::get($options, 'token', $this->config['token']);

        $params['id'] = Arr::get($options, 'to', '');
        $params['from'] = Arr::get($options, 'from', $this->config['from']);

        $color = Arr::get($options, 'color', 'yellow');

        if (!in_array($color, $this->colours)) {
            $color = 'yellow';
        }

        $params['color'] = $color;
        $params['message'] = $message;
        $params['notify'] = Arr::get($options, 'notify', false);
        $params['message_format'] = Arr::get($options, 'format', 'text');

        return $params;
    }

    /**
     * Commit a HTTP request.
     *
     * @param string   $method
     * @param string   $url
     * @param string[] $params
     * @param string[] $options
     *
     * @return mixed
     */
    protected function commit($method = 'post', $url, array $params = [], array $options = [])
    {
        $success = false;

        $token = $params['auth_token'];

        unset($params['auth_token']);

        $rawResponse = $this->client->{$method}($url, [
            'exceptions'      => false,
            'timeout'         => '80',
            'connect_timeout' => '30',
            'headers'         => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
            'json' => $params,
        ]);

        if ($rawResponse->getStatusCode() == 204) {
            $response = [];
            $success = true;
        } else {
            $response = $this->responseError($rawResponse);
        }

        return $this->mapResponse($success, $response);
    }

    /**
     * Map HTTP response to response object.
     *
     * @param bool  $success
     * @param array $response
     *
     * @return \NotifyMeHQ\NotifyMe\Response
     */
    protected function mapResponse($success, $response)
    {
        return (new Response())->setRaw($response)->map([
            'success' => $success,
            'message' => $success ? 'Message sent' : $response['error']['message'],
        ]);
    }

    /**
     * Get the default json response.
     *
     * @param string $rawResponse
     *
     * @return array
     */
    protected function jsonError($rawResponse)
    {
        $msg = 'API Response not valid.';
        $msg .= " (Raw response API {$rawResponse->getBody()})";

        return [
            'error' => [
                'message' => $msg,
            ],
        ];
    }

    /**
     * Get the request url.
     *
     * @return string
     */
    protected function getRequestUrl()
    {
        return $this->endpoint.'/'.$this->version;
    }
}
