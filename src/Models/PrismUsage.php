<?php

declare(strict_types=1);

namespace Prism\Prism\Models;

use Illuminate\Database\Eloquent\Model;

class PrismUsage extends Model
{
    protected $guarded = [];

    protected $casts = [
        'messages' => 'array',
        'response' => 'array',
        'tool_calls' => 'array',
        'tool_results' => 'array',
    ];
}
