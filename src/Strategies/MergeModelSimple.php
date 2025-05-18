<?php

namespace EncoreDigitalGroup\MergeModels\Strategies;

use Illuminate\Database\Eloquent\Model;

/** @api */
class MergeModelSimple implements MergeModelStrategy
{
    public function merge(Model $baseModel, Model $duplicateModel): Model
    {
        $base = $baseModel->getAttributes();
        $duplicate = $duplicateModel->getAttributes();
        $dataMerge = array_merge($duplicate, $base);

        $baseModel->fill($dataMerge);

        return $baseModel;
    }
}
