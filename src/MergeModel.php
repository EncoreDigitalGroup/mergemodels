<?php

namespace EncoreDigitalGroup\MergeModels;

use EncoreDigitalGroup\MergeModels\Exceptions\ModelsBelongToDivergedParentsException;
use EncoreDigitalGroup\MergeModels\Exceptions\ModelsNotDupeException;
use EncoreDigitalGroup\MergeModels\Strategies\MergeModelSimple;
use EncoreDigitalGroup\MergeModels\Strategies\MergeModelStrategy;
use Illuminate\Database\Eloquent\Model;

class MergeModel
{
    protected Model $modelA;

    protected Model $modelB;

    protected $strategy;

    protected ?array $keys = null;

    protected array $relationships = [];

    protected ?string $belongsTo = null;

    public function __construct($strategy = null)
    {
        $this->useStrategy($strategy);
    }

    /**
     * Pick a strategy class for merge operation.
     */
    public function useStrategy(MergeModelStrategy $strategy = null)
    {
        $this->strategy = $strategy === null ? new MergeModelSimple() : $strategy;

        return $this;
    }

    /**
     * Set model A
     *
     * @param Model $model
     *
     * @return  $this
     */
    public function setModelA(Model $model)
    {
        $this->modelA = $model;

        return $this;
    }

    public function getModelA()
    {
        return $this->modelA;
    }

    public function getModelB()
    {
        return $this->modelB;
    }

    public function getBase()
    {
        return $this->getModelA();
    }

    public function getDupe()
    {
        return $this->getModelB();
    }

    /**
     * Set model B
     *
     * @param Model $model
     *
     * @return  $this
     */
    public function setModelB(Model $model)
    {
        $this->modelB = $model;

        return $this;
    }

    /**
     * Alias for setModelA
     *
     * @param Model $baseModel
     */
    public function setBase($baseModel)
    {
        $this->setModelA($baseModel);

        return $this;
    }

    /**
     * Alias for setModelB
     *
     * @param Model $dupeModel
     */
    public function setDupe($dupeModel)
    {
        $this->setModelB($dupeModel);

        return $this;
    }

    /**
     * Specify a compound key to match models and verify identity.
     *
     * @param string|array $keys Keys that make the model identifiable
     *
     * @return $this
     */
    public function withKey($keys)
    {
        if (is_array($keys)) {
            $this->keys = $keys;
        }

        if (is_string($keys)) {
            $this->keys = [$keys];
        }

        return $this;
    }

    /**
     * Executes the merge for A and B Models
     *
     * @return Illuminate\Database\Eloquent\Model The model A with merged attributes from model B
     */
    public function merge()
    {
        $this->validateKeys();

        $this->validateBelongsToSameParent();

        $this->transferRelationships();

        return $this->strategy->merge($this->modelA, $this->modelB);
    }

    /**
     * Executes the merge and performs save/delete accordingly to preserve base and discard dupe
     *
     * @return Illuminate\Database\Eloquent\Model The model A (base)
     */
    public function unifyOnBase()
    {
        $mergeModel = $this->merge();

        $this->modelA->fill($mergeModel->toArray());

        $this->modelA->save();

        $this->modelB->delete();

        return $this->modelA;
    }

    /**
     * Prefer the oldest of the models to be preserved
     *
     * @return $this
     */
    public function preferOldest()
    {
        if ($this->modelB->created_at < $this->modelA->created_at) {
            $this->swapPriority();
        }

        return $this;
    }

    /**
     * Prefer the newest of the models to be preserved
     *
     * @return $this
     */
    public function preferNewest()
    {
        if ($this->modelB->created_at > $this->modelA->created_at) {
            $this->swapPriority();
        }

        return $this;
    }

    /**
     * Swap models from base to dupe and vice versa
     *
     * @return $this
     */
    public function swapPriority()
    {
        $tmp = $this->modelA;

        $this->modelA = $this->modelB;
        $this->modelB = $tmp;

        return $this;
    }

    public function belongsTo($belongsTo = null)
    {
        $this->belongsTo = $belongsTo;

        return $this;
    }

    /**
     * Alias for belongsTo
     *
     * @param string $belongsTo Relationship name
     * @return $this
     */
    public function mustBelongToSame($belongsTo = null)
    {
        return $this->belongsTo($belongsTo);
    }

    public function withRelationships(array $relationships)
    {
        $this->relationships = $relationships;

        return $this;
    }

    public function transferRelationships()
    {
        foreach ($this->relationships as $relationship) {
            $this->transferChilds($relationship);
        }
    }

    public function transferChilds($relationship)
    {
        foreach ($this->modelB->$relationship as $child) {
            $this->modelA->$relationship()->save($child);
        }
    }

    protected function validateKeys()
    {
        if ($this->keys === null) {
            return;
        }

        $dataA = $this->modelA->only($this->keys);
        $dataB = $this->modelB->only($this->keys);

        if ($dataA != $dataB) {
            throw new ModelsNotDupeException('Models are not dupes', 1);
        }
    }

    protected function validateBelongsToSameParent()
    {
        if ($this->belongsTo === null) {
            return;
        }

        if ($this->modelA->{$this->belongsTo} != $this->modelB->{$this->belongsTo}) {
            throw new ModelsBelongToDivergedParentsException('Models do not belong to same parent', 1);
        }
    }
}
