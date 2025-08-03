<?php

namespace Kstmostofa\LaravelEsl\Facades;

use Illuminate\Support\Facades\Facade;

class Esl extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Kstmostofa\LaravelEsl\EslConnection::class;
    }
}
