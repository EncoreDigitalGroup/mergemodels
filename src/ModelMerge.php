<?php

namespace EncoreDigitalGroup\MergeModels;

use EncoreDigitalGroup\MergeModels\Exceptions\ModelsBelongToDivergedParentsException;
use EncoreDigitalGroup\MergeModels\Exceptions\ModelsNotDupeException;
use EncoreDigitalGroup\MergeModels\Strategies\MergeModelSimple;
use EncoreDigitalGroup\MergeModels\Strategies\MergeModelStrategy;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/** @api */
class ModelMerge
{
    protected Model $modelA;
    protected Model $modelB;
    protected ?MergeModelStrategy $strategy;
    protected ?array $keys = null;
    protected array $relationships = [];
    protected ?string $belongsTo = null;

    public function __construct(?MergeModelStrategy $strategy = null)
    {
        $this->useStrategy($strategy);
    }

    /**
     * Pick a strategy class for merge operation.
     */
    public function useStrategy(?MergeModelStrategy $strategy = null): static
    {
        $this->strategy = $strategy instanceof MergeModelStrategy ? $strategy : new MergeModelSimple();

        return $this;
    }

    /**
     * Set model A
     *
     *
     * @return $this
     */
    public function setModelA(Model $model): static
    {
        $this->modelA = $model;

        return $this;
    }

    public function getModelA(): Model
    {
        return $this->modelA;
    }

    public function getModelB(): Model
    {
        return $this->modelB;
    }

    public function getBase(): Model
    {
        return $this->getModelA();
    }

    public function getDupe(): Model
    {
        return $this->getModelB();
    }

    /**
     * Set model B
     *
     *
     * @return $this
     */
    public function setModelB(Model $model): static
    {
        $this->modelB = $model;

        return $this;
    }

    /**
     * Alias for setModelA
     */
    public function setBase(Model $baseModel): static
    {
        $this->setModelA($baseModel);

        return $this;
    }

    /**
     * Alias for setModelB
     */
    public function setDupe(Model $dupeModel): static
    {
        $this->setModelB($dupeModel);

        return $this;
    }

    /**
     * Specify a compound key to match models and verify identity.
     *
     * @param  string|array  $keys  Keys that make the model identifiable
     * @return $this
     */
    public function withKey($keys): static
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
     */
    public function merge(): Model
    {
        $this->validateKeys();

        $this->validateBelongsToSameParent();

        $this->transferRelationships();

        if (is_null($this->strategy)) {
            throw new LogicException('Strategy must not be null');
        }

        return $this->strategy->merge($this->modelA, $this->modelB);
    }

    /**
     * Executes the merge and performs save/delete accordingly to preserve base and discard dupe
     */
    public function unifyOnBase(): Model
    {
        $mergeModel = $this->merge();

        $this->modelA->fill($mergeModel->toArray());

        $this->modelA->save();

        $this->modelB->delete();

        return $this->modelA;
    }

    /**
     * Prefer the oldest of the models to be preserved
     */
    public function preferOldest(): static
    {
        //@phpstan-ignore-next-line
        if ($this->modelB->created_at < $this->modelA->created_at) {
            $this->swapPriority();
        }

        return $this;
    }

    /**
     * Prefer the newest of the models to be preserved
     */
    public function preferNewest(): static
    {
        // @phpstan-ignore-next-line
        if ($this->modelB->created_at > $this->modelA->created_at) {
            $this->swapPriority();
        }

        return $this;
    }

    /**
     * Swap models from base to dupe and vice versa
     */
    public function swapPriority(): static
    {
        $tmp = $this->modelA;

        $this->modelA = $this->modelB;
        $this->modelB = $tmp;

        return $this;
    }

    public function belongsTo(?string $belongsTo = null): static
    {
        $this->belongsTo = $belongsTo;

        return $this;
    }

    /**
     * Alias for belongsTo
     */
    public function mustBelongToSame(?string $belongsTo = null): static
    {
        return $this->belongsTo($belongsTo);
    }

    public function withRelationships(array $relationships): static
    {
        $this->relationships = $relationships;

        return $this;
    }

    public function transferRelationships(): void
    {
        foreach ($this->relationships as $relationship) {
            $this->transferChilds($relationship);
        }
    }

    public function transferChilds(mixed $relationship): void
    {
        foreach ($this->modelB->$relationship as $child) {
            $this->modelA->$relationship()->save($child);
        }
    }

    protected function validateKeys(): void
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

    protected function validateBelongsToSameParent(): void
    {
        if ($this->belongsTo === null) {
            return;
        }

        if ($this->modelA->{$this->belongsTo} != $this->modelB->{$this->belongsTo}) {
            throw new ModelsBelongToDivergedParentsException('Models do not belong to same parent', 1);
        }
    }
}
