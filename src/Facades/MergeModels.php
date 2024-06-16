<?php

namespace EncoreDigitalGroup\MergeModels\Facades;

use Illuminate\Support\Facades\Facade;

class MergeModels extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'mergemodels';
    }
}
