<?php

namespace EncoreDigitalGroup\MergeModels\Strategies;

use EncoreDigitalGroup\MergeModels\Strategies\MergeModelStrategy;

class MergeModelSimple implements MergeModelStrategy
{
    public function merge($modelA, $modelB)
    {
        $dataA = $modelA->toArray();
        $dataB = $modelB->toArray();

        $dataMerge = array_merge($dataB, $dataA);

        $modelA->fill($dataMerge);

        return $modelA;
    }
}
