<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Challenge extends Model
{
    protected $fillable = [
        'title',
        'sub_title',
        'description',
        'project_id'
    ];

    public function project(): BelongsTo 
    {
        return $this->belongsTo(Project::class);
    }
}
