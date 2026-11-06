<?php

namespace App\Models;

use App\Domain\Ehrungen\HonorRule;
use App\Domain\Ehrungen\RuleBasis;
use App\Models\Concerns\HasTranslations;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $code
 * @property int $organisation_id
 * @property string $level
 * @property string|null $region
 * @property string $recipient_type
 * @property array<string, string> $title
 * @property array<string, string>|null $scope
 * @property int $sort
 * @property bool $active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Organisation $organisation
 * @property-read Procedure|null $procedure
 * @property-read RuleVersion|null $publishedRule
 */
class Honor extends Model
{
    use HasTranslations;

    protected $fillable = ['code', 'organisation_id', 'level', 'region', 'recipient_type', 'title', 'scope', 'sort', 'active'];

    protected function casts(): array
    {
        return ['title' => 'array', 'scope' => 'array', 'active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * @return BelongsTo<Organisation, $this>
     */
    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    /**
     * @return HasOne<Procedure, $this>
     */
    public function procedure(): HasOne
    {
        return $this->hasOne(Procedure::class);
    }

    /**
     * @return HasMany<RuleVersion, $this>
     */
    public function ruleVersions(): HasMany
    {
        return $this->hasMany(RuleVersion::class)->orderByDesc('version');
    }

    /**
     * @return HasOne<RuleVersion, $this>
     */
    public function publishedRule(): HasOne
    {
        return $this->hasOne(RuleVersion::class)->where('status', RuleVersion::STATUS_PUBLISHED);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort')->orderBy('id');
    }

    public function nextVersionNumber(): int
    {
        return (int) $this->ruleVersions()->max('version') + 1;
    }

    /**
     * Sicht für die Regel-Engine – auf Basis der veröffentlichten oder einer übergebenen Regelversion.
     */
    public function toRule(?RuleVersion $version = null): HonorRule
    {
        $version ??= $this->publishedRule;

        return new HonorRule(
            honorCode: $this->code,
            criteria: $version?->criteria,
            basis: $version->basis ?? RuleBasis::PendingConfirmation,
            region: $this->region,
            hasPublishedRule: $version !== null,
            ruleVersionId: $version?->id,
        );
    }
}
