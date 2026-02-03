<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class LandingPage extends Model
{
    protected $fillable = [
        'about_me',
        'skills',

    ];

    public function techStacks(): MorphToMany
    {
        return $this->morphToMany(TechStack::class, 'stackable', 'tech_stackables');
    }
}
