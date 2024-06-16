<?php

namespace EncoreDigitalGroup\MergeModels\Strategies;

use Illuminate\Database\Eloquent\Model;

/** @api */
class MergeModelSimple implements MergeModelStrategy
{
    public function merge(Model $modelA, Model $modelB): Model
    {
        $dataA = $modelA->toArray();
        $dataB = $modelB->toArray();

        $dataMerge = array_merge($dataB, $dataA);

        $modelA->fill($dataMerge);

        return $modelA;
    }
}
