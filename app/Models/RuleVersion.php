<?php

namespace App\Models;

use App\Domain\Ehrungen\RuleBasis;
use App\Models\Concerns\HasTranslations;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $honor_id
 * @property int $version
 * @property string $status
 * @property RuleBasis $basis
 * @property array<string, string>|null $basis_note
 * @property array<string, mixed>|null $criteria
 * @property CarbonImmutable|null $valid_from
 * @property CarbonImmutable|null $valid_until
 * @property string|null $change_note
 * @property int|null $created_by
 * @property int|null $submitted_by
 * @property CarbonImmutable|null $submitted_at
 * @property int|null $approved_by
 * @property CarbonImmutable|null $approved_at
 * @property string|null $approval_role
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Honor $honor
 * @property-read RuleVersionSource $pivot  nur beim Laden über Source::ruleVersions()
 * @property-read Collection<int, Source> $sources
 */
class RuleVersion extends Model
{
    use HasTranslations;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_IN_REVIEW = 'in_review';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_SUPERSEDED = 'superseded';

    protected $fillable = [
        'honor_id', 'version', 'status', 'basis', 'basis_note', 'criteria', 'valid_from', 'valid_until', 'change_note',
        'created_by', 'submitted_by', 'submitted_at', 'approved_by', 'approved_at', 'approval_role', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'basis' => RuleBasis::class,
            'basis_note' => 'array',
            'criteria' => 'array',
            'valid_from' => 'date',
            'valid_until' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Honor, $this>
     */
    public function honor(): BelongsTo
    {
        return $this->belongsTo(Honor::class);
    }

    /**
     * @return BelongsToMany<Source, $this, RuleVersionSource, 'pivot'>
     */
    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(Source::class)->using(RuleVersionSource::class)->withPivot('locator')->orderBy('code');
    }

    /**
     * @return HasMany<RuleVersionEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(RuleVersionEvent::class)->latest('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function label(): string
    {
        return 'v'.$this->version;
    }
}
