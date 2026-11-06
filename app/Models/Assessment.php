<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gespeichertes, anonymes Ergebnis einer Vorprüfung.
 */
class Assessment extends Model
{
    protected $fillable = ['uuid', 'country', 'region', 'recipient_group', 'locale', 'answers', 'results', 'completed', 'duration_seconds'];

    protected function casts(): array
    {
        return ['answers' => 'array', 'results' => 'array', 'completed' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }
}
