<?php

namespace EncoreDigitalGroup\MergeModels\Facades;

use Illuminate\Support\Facades\Facade;

class MergeModels extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'mergemodels';
    }
}
