<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Gespeichertes, anonymes Ergebnis einer Vorprüfung.
 *
 * @property int $id
 * @property string $uuid
 * @property string $country
 * @property string|null $region
 * @property string $recipient_group
 * @property string $locale
 * @property array<string, string> $answers
 * @property list<array<string, mixed>> $results
 * @property bool $completed
 * @property int|null $duration_seconds
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
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

    /**
     * @return HasMany<Feedback, $this>
     */
    public function feedback(): HasMany
    {
        return $this->hasMany(Feedback::class);
    }
}
