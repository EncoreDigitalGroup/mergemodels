<?php

namespace EncoreDigitalGroup\MergeModels\Tests\Unit;

use EncoreDigitalGroup\MergeModels\MergeModel;
use EncoreDigitalGroup\MergeModels\Strategies\MergeModelSimple;
use Illuminate\Database\Eloquent\Model;
use EncoreDigitalGroup\MergeModels\Tests\Unit\BaseTestCase;
use EncoreDigitalGroup\MergeModels\Tests\Unit\DummyContact;

class ModelMergeStrategiesTest extends BaseTestCase
{
    public function test_simple_merge_strategy()
    {
        $modelA = DummyContact::make(['firstname' => 'John', 'age' => 33]);
        $modelB = DummyContact::make(['firstname' => 'John', 'lastname' => 'Doe']);

        $modelMerge = new MergeModel(new MergeModelSimple);
        $modelMerge->setModelA($modelA)->setModelB($modelB);
        $mergedModel = $modelMerge->merge();

        $this->assertEquals($mergedModel->firstname, 'John');
        $this->assertEquals($mergedModel->lastname, 'Doe');
        $this->assertEquals($mergedModel->age, 33);
    }
}
