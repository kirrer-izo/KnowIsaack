<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class TechStack extends Model
{
    protected $fillable = [
        'name'
    ];

    public function landingPages(): MorphToMany
    {
        return $this->morphedByMany(LandingPage::class, 'stackable', 'tech_stackables');
    }

    public function projects(): MorphToMany 
    {
        return $this->morphToMany(Project::class, 'stackable', 'tech_stackables');
    }
}
