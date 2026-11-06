<?php

namespace App\Models;

use App\Domain\Ehrungen\RuleBasis;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function honor(): BelongsTo
    {
        return $this->belongsTo(Honor::class);
    }

    public function sources(): BelongsToMany
    {
        return $this->belongsToMany(Source::class)->withPivot('locator')->orderBy('code');
    }

    public function events(): HasMany
    {
        return $this->hasMany(RuleVersionEvent::class)->latest('id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

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
