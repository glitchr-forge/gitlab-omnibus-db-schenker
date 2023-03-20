<?php

namespace Omnibus\DbSchenker;

use Omnibus\Core\GatewayFactory;
use Omnibus\DbSchenker\Action\ShippingAction;
use Omnibus\DbSchenker\Action\TrackingAction;
use Omnibus\DbSchenker\Action\PackingSlipAction;

class DbSchenkerGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config)
    {
        $config->defaults([
            'omnibus.factory_name' => 'db-schenker',
            'omnibus.factory_title' => 'DB Schenker'
        ]);
    }
}