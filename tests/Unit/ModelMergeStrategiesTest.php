<?php

namespace EncoreDigitalGroup\MergeModels\Tests\Unit;

use EncoreDigitalGroup\MergeModels\ModelMerge;
use EncoreDigitalGroup\MergeModels\Strategies\MergeModelSimple;

class ModelMergeStrategiesTest extends BaseTestCase
{
    public function test_simple_merge_strategy()
    {
        $modelA = DummyContact::make(['firstname' => 'John', 'age' => 33]);
        $modelB = DummyContact::make(['firstname' => 'John', 'lastname' => 'Doe']);

        $modelMerge = new ModelMerge(new MergeModelSimple);
        $modelMerge->setModelA($modelA)->setModelB($modelB);
        $mergedModel = $modelMerge->merge();

        $this->assertEquals($mergedModel->firstname, 'John');
        $this->assertEquals($mergedModel->lastname, 'Doe');
        $this->assertEquals($mergedModel->age, 33);
    }
}
