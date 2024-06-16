<?php

namespace EncoreDigitalGroup\MergeModels\Tests\Unit;

use EncoreDigitalGroup\MergeModels\Facades\MergeModels;
use EncoreDigitalGroup\MergeModels\Tests\Unit\BaseTestCase;
use Illuminate\Database\Eloquent\Model;
use EncoreDigitalGroup\MergeModels\Tests\Unit\DummyContact;

// use Tests\TestCase as BaseTestCase;

class ModelMergeFacadeTest extends BaseTestCase
{
    public function test_facade()
    {
        $modelA = DummyContact::make(['firstname' => 'John', 'age' => 33]);
        $modelB = DummyContact::make(['firstname' => 'John', 'lastname' => 'Doe']);

        $mergedModel = MergeModels::setModelA($modelA)->setModelB($modelB)->merge();

        $this->assertInstanceOf(Model::class, $mergedModel, 'Merged model should extend an Eloquent Model');
    }
}
