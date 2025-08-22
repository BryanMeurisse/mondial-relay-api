<?php

namespace Bmwsly\MondialRelayApi\Facades;

use Bmwsly\MondialRelayApi\MondialRelayClient;
use Illuminate\Support\Facades\Facade;

class MondialRelay extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MondialRelayClient::class;
    }
}
