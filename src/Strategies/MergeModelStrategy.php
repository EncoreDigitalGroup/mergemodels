<?php

namespace EncoreDigitalGroup\MergeModels\Strategies;

interface MergeModelStrategy
{
    public function merge($modelA, $modelB);
}
