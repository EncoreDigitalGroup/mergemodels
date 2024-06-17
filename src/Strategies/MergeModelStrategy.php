<?php

namespace EncoreDigitalGroup\MergeModels\Strategies;

use Illuminate\Database\Eloquent\Model;

interface MergeModelStrategy
{
    public function merge(Model $baseModel, Model $duplicateModel): Model;
}
