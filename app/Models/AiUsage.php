<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiUsage extends Model
{
    protected $fillable = [
        'provider',
        'model',
        'purpose',
        'batch_id',
        'prompt_tokens',
        'output_tokens',
        'cached_tokens',
        'estimated_cost_usd',
        'status',
        'error',
    ];

    protected $casts = [
        'prompt_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cached_tokens' => 'integer',
        'estimated_cost_usd' => 'float',
    ];
}
