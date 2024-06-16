<?php

namespace EncoreDigitalGroup\MergeModels\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static setBaseModel($model)
 */
class MergeModels extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'mergemodels';
    }
}
