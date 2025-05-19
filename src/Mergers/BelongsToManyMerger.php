<?php

namespace EncoreDigitalGroup\MergeModels\Mergers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

class BelongsToManyMerger
{
    public static function make(): self
    {
        return new self;
    }

    /**
     * Transfer BelongsToMany relationships from duplicateModel to baseModel
     *
     * @param Model $baseModel
     * @param Model $duplicateModel
     * @return void
     */
    public function transfer(Model $baseModel, Model $duplicateModel): void
    {
        $relationships = $this->getBelongsToManyRelationships($duplicateModel);

        foreach ($relationships as $relationship) {
            $method = $relationship->name;
            $relation = $duplicateModel->$method();

            if ($relation instanceof BelongsToMany) {
                $relatedIds = $duplicateModel->$method->pluck('id')->toArray();
                $pivotData = $this->getPivotData($relation, $duplicateModel);

                $baseModel->$method()->sync($relatedIds);

                if (!empty($pivotData)) {
                    $this->updatePivotData($baseModel->$method(), $pivotData);
                }

                /**
                 * TODO:    It was staring me right in the face. Instead of detaching, we should
                 *          probably be transferring the relation to the base model.
                 */
                $duplicateModel->$method()->detach();
            }
        }
    }

    /**
     * Get all BelongsToMany relationships defined in the model
     *
     * @param Model $model
     * @return array
     */
    protected function getBelongsToManyRelationships(Model $model): array
    {
        $relationships = [];

        $methods = (new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->getDeclaringClass()->getName() !== get_class($model)) {
                continue;
            }

            try {
                $return = $model->{$method->getName()}();

                if ($return instanceof BelongsToMany) {
                    $relationships[] = $method;
                }
            } catch (Throwable $e) {
                continue;
            }
        }

        return $relationships;
    }

    /**
     * Get pivot data for a BelongsToMany relationship
     *
     * @param BelongsToMany $relation
     * @param Model $model
     * @return array
     */
    protected function getPivotData(BelongsToMany $relation, Model $model): array
    {
        $pivotData = [];

        $related = $relation->get()->mapWithKeys(function ($item) {
            return [$item->getKey() => $item->pivot?->getAttributes()];
        })->toArray();

        foreach ($related as $relatedId => $attributes) {
            if(is_null($attributes)) {
                continue;
            }

            $pivotData[$relatedId] = array_filter($attributes, function ($key) use ($relation) {
                return !in_array($key, [
                    $relation->getForeignPivotKeyName(),
                    $relation->getRelatedPivotKeyName(),
                    'created_at',
                    'updated_at'
                ]);
            }, ARRAY_FILTER_USE_KEY);
        }

        return array_filter($pivotData);
    }

    /**
     * Update pivot data for a BelongsToMany relationship
     *
     * @param BelongsToMany $relation
     * @param array $pivotData
     * @return void
     */
    protected function updatePivotData(BelongsToMany $relation, array $pivotData): void
    {
        foreach ($pivotData as $relatedId => $attributes) {
            if (!empty($attributes)) {
                $relation->updateExistingPivot($relatedId, $attributes);
            }
        }
    }
}