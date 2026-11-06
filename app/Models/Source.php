<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property string $code
 * @property string $title
 * @property string|null $url
 * @property array<string, string>|null $note
 * @property CarbonImmutable|null $retrieved_at
 * @property-read RuleVersionSource $pivot  nur beim Laden über RuleVersion::sources()
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Source extends Model
{
    use HasTranslations;

    protected $fillable = ['code', 'title', 'url', 'note', 'retrieved_at'];

    protected function casts(): array
    {
        return ['note' => 'array', 'retrieved_at' => 'date'];
    }

    /**
     * @return BelongsToMany<RuleVersion, $this, RuleVersionSource, 'pivot'>
     */
    public function ruleVersions(): BelongsToMany
    {
        return $this->belongsToMany(RuleVersion::class)->using(RuleVersionSource::class)->withPivot('locator');
    }
}
