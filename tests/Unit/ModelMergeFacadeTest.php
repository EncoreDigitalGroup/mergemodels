<?php

namespace EncoreDigitalGroup\MergeModels\Tests\Unit;

use EncoreDigitalGroup\MergeModels\Facades\MergeModels;
use Illuminate\Database\Eloquent\Model;

class ModelMergeFacadeTest extends BaseTestCase
{
    public function test_facade()
    {
        $modelA = DummyContact::make(['firstname' => 'John', 'age' => 33]);
        $modelB = DummyContact::make(['firstname' => 'John', 'lastname' => 'Doe']);

        $mergedModel = MergeModels::setBaseModel($modelA)->setDuplicateModel($modelB)->merge();

        $this->assertInstanceOf(Model::class, $mergedModel, 'Merged model should extend an Eloquent Model');
    }
}
