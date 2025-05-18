<?php

namespace EncoreDigitalGroup\MergeModels\Mergers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        // Get all BelongsToMany relationships from duplicateModel
        $relationships = $this->getBelongsToManyRelationships($duplicateModel);

        foreach ($relationships as $relationship) {
            // Get the relationship method name
            $method = $relationship->getMethodName();

            // Get the BelongsToMany relationship instance
            $relation = $duplicateModel->$method();

            if ($relation instanceof BelongsToMany) {
                // Get the related model IDs and pivot data
                $relatedIds = $duplicateModel->$method->pluck('id')->toArray();
                $pivotData = $this->getPivotData($relation, $duplicateModel);

                // Sync relationships to baseModel
                $baseModel->$method()->sync($relatedIds);

                // Update pivot data if exists
                if (!empty($pivotData)) {
                    $this->updatePivotData($baseModel->$method(), $pivotData);
                }
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

        // Get all public methods of the model
        $methods = (new \ReflectionClass($model))->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            // Skip methods that belong to parent classes
            if ($method->getDeclaringClass()->getName() !== get_class($model)) {
                continue;
            }

            try {
                $return = $model->{$method->getName()}();

                if ($return instanceof BelongsToMany) {
                    $relationships[] = $method;
                }
            } catch (\Exception $e) {
                // Skip methods that throw exceptions or aren't relationships
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

        // Get all related models with pivot data
        $related = $relation->get()->mapWithKeys(function ($item) {
            return [$item->getKey() => $item->pivot->getAttributes()];
        })->toArray();

        foreach ($related as $relatedId => $attributes) {
            // Only include pivot attributes that aren't primary/foreign keys
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