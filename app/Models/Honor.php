<?php

namespace App\Models;

use App\Domain\Ehrungen\HonorRule;
use App\Domain\Ehrungen\RuleBasis;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

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

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function procedure(): HasOne
    {
        return $this->hasOne(Procedure::class);
    }

    public function ruleVersions(): HasMany
    {
        return $this->hasMany(RuleVersion::class)->orderByDesc('version');
    }

    public function publishedRule(): HasOne
    {
        return $this->hasOne(RuleVersion::class)->where('status', RuleVersion::STATUS_PUBLISHED);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

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
            basis: $version?->basis ?? RuleBasis::PendingConfirmation,
            region: $this->region,
            hasPublishedRule: $version !== null,
            ruleVersionId: $version?->id,
        );
    }
}
