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
    protected Model $baseModel;
    protected Model $duplicateModel;
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
    public function setBaseModel(Model $model): static
    {
        $this->baseModel = $model;

        return $this;
    }

    public function getBaseModel(): Model
    {
        return $this->baseModel;
    }

    public function getDuplicateModel(): Model
    {
        return $this->duplicateModel;
    }

    public function getBase(): Model
    {
        return $this->getBaseModel();
    }

    public function getDuplicate(): Model
    {
        return $this->getDuplicateModel();
    }

    /**
     * Set model B
     */
    public function setDuplicateModel(Model $model): static
    {
        $this->duplicateModel = $model;

        return $this;
    }

    /**
     * Alias for setBaseModel
     */
    public function setBase(Model $baseModel): static
    {
        $this->setBaseModel($baseModel);

        return $this;
    }

    /**
     * Alias for setDuplicateModel
     */
    public function setDuplicate(Model $duplicateModel): static
    {
        $this->setDuplicateModel($duplicateModel);

        return $this;
    }

    /**
     * Specify a compound key to match models and verify identity.
     *
     * @param  string|array  $keys  Keys that make the model identifiable
     * @return $this
     */
    public function withKey(string|array $keys): static
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
     * Executes the merge for base and duplicate models
     */
    public function merge(): Model
    {
        $this->validateKeys();

        $this->validateBelongsToSameParent();

        $this->transferRelationships();

        if (is_null($this->strategy)) {
            throw new LogicException('Strategy must not be null');
        }

        return $this->strategy->merge($this->baseModel, $this->duplicateModel);
    }

    /**
     * Executes the merge and performs save/delete accordingly to preserve base and discard dupe
     */
    public function unifyOnBase(): Model
    {
        $mergeModel = $this->merge();

        $this->baseModel->fill($mergeModel->toArray());

        $this->baseModel->save();

        $this->duplicateModel->delete();

        return $this->baseModel;
    }

    /**
     * Prefer the oldest of the models to be preserved
     */
    public function preferOldest(): static
    {
        //@phpstan-ignore-next-line
        if ($this->duplicateModel->created_at < $this->baseModel->created_at) {
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
        if ($this->duplicateModel->created_at > $this->baseModel->created_at) {
            $this->swapPriority();
        }

        return $this;
    }

    /**
     * Swap models from base to dupe and vice versa
     */
    public function swapPriority(): static
    {
        $tmp = $this->baseModel;

        $this->baseModel = $this->duplicateModel;
        $this->duplicateModel = $tmp;

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
        foreach ($this->duplicateModel->$relationship as $child) {
            $this->baseModel->$relationship()->save($child);
        }
    }

    protected function validateKeys(): void
    {
        if ($this->keys === null) {
            return;
        }

        $dataA = $this->baseModel->only($this->keys);
        $dataB = $this->duplicateModel->only($this->keys);

        if ($dataA != $dataB) {
            throw new ModelsNotDupeException('Models are not dupes', 1);
        }
    }

    protected function validateBelongsToSameParent(): void
    {
        if ($this->belongsTo === null) {
            return;
        }

        if ($this->baseModel->{$this->belongsTo} != $this->duplicateModel->{$this->belongsTo}) {
            throw new ModelsBelongToDivergedParentsException('Models do not belong to same parent', 1);
        }
    }
}
