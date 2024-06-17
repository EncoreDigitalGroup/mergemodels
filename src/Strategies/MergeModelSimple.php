<?php

namespace EncoreDigitalGroup\MergeModels\Strategies;

use Illuminate\Database\Eloquent\Model;

/** @api */
class MergeModelSimple implements MergeModelStrategy
{
    public function merge(Model $baseModel, Model $duplicateModel): Model
    {
        $base = $baseModel->toArray();
        $duplicate = $duplicateModel->toArray();

        $dataMerge = array_merge($duplicate, $base);

        $baseModel->fill($dataMerge);

        return $baseModel;
    }
}
