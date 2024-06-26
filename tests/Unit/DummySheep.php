<?php

namespace EncoreDigitalGroup\MergeModels\Tests\Unit;

use Illuminate\Database\Eloquent\Model;

/**
 * DummySheep is an example child model simulating a typical child relationship.
 */
class DummySheep extends Model
{
    protected $fillable = ['id', 'dummy_contact_id', 'name', 'color', 'created_at'];

    protected $hidden = [];

    protected $dates = ['created_at', 'deleted_at'];

    public function owner()
    {
        return $this->belongsTo(DummyContact::class, 'dummy_contact_id');
    }
}
