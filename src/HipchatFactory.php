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
use NotifyMeHQ\Contracts\FactoryInterface;
use NotifyMeHQ\NotifyMe\Arr;

class HipchatFactory implements FactoryInterface
{
    /**
     * Create a new hipchat gateway instance.
     *
     * @param string[] $config
     *
     * @return \NotifyMeHQ\Hipchat\HipchatGateway
     */
    public function make(array $config)
    {
        Arr::requires($config, ['token']);

        $client = new Client();

        return new HipchatGateway($client, $config);
    }
}
