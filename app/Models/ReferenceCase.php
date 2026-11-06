<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $region
 * @property string $recipient_group
 * @property array<string, string> $answers
 * @property array<string, string> $expected
 * @property bool $show_as_example
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class ReferenceCase extends Model
{
    protected $fillable = ['name', 'region', 'recipient_group', 'answers', 'expected', 'show_as_example'];

    protected function casts(): array
    {
        return ['answers' => 'array', 'expected' => 'array', 'show_as_example' => 'boolean'];
    }
}
