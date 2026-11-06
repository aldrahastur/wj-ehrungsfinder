<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferenceCase extends Model
{
    protected $fillable = ['name', 'region', 'recipient_group', 'answers', 'expected', 'show_as_example'];

    protected function casts(): array
    {
        return ['answers' => 'array', 'expected' => 'array', 'show_as_example' => 'boolean'];
    }
}
