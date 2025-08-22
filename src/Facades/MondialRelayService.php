<?php

namespace Bmwsly\MondialRelayApi\Facades;

use Bmwsly\MondialRelayApi\Services\MondialRelayService as MondialRelayServiceClass;
use Illuminate\Support\Facades\Facade;

class MondialRelayService extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MondialRelayServiceClass::class;
    }
}
