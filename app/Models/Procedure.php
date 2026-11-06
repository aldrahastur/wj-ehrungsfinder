<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $honor_id
 * @property array<string, string>|null $next_step
 * @property array<string, string>|null $nomination_right
 * @property array<string, string>|null $decision_body
 * @property array<string, string>|null $application
 * @property array<string, string>|null $deadlines
 * @property array<string, string>|null $lead_time
 * @property array<string, string>|null $evidence
 * @property string|null $fee_amount
 * @property string|null $fee_currency
 * @property string|null $application_url
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Procedure extends Model
{
    use HasTranslations;

    /** Felder des Steckbriefs, die je Sprache gepflegt werden. */
    public const TEXT_FIELDS = ['next_step', 'nomination_right', 'decision_body', 'application', 'deadlines', 'lead_time', 'evidence'];

    protected $fillable = ['honor_id', 'next_step', 'nomination_right', 'decision_body', 'application', 'deadlines', 'lead_time', 'evidence', 'fee_amount', 'fee_currency', 'application_url'];

    protected function casts(): array
    {
        return array_fill_keys(self::TEXT_FIELDS, 'array') + ['fee_amount' => 'decimal:2'];
    }

    /**
     * @return BelongsTo<Honor, $this>
     */
    public function honor(): BelongsTo
    {
        return $this->belongsTo(Honor::class);
    }
}
