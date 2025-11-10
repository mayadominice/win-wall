<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StickyNote extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'text',
        'color',
        'icon',
        'x',
        'y',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
