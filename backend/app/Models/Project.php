<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Project extends Model
{
    /** @use HasFactory<\Database\Factories\ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'goal',
        'source_code',
        'live_demo',
    ];

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }

    public function techStacks(): MorphMany
    {
        return $this->morphMany(TechStack::class, 'stackable', 'tech_stackbales');
    }

}
