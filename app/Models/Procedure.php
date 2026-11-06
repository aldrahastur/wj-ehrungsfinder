<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function honor(): BelongsTo
    {
        return $this->belongsTo(Honor::class);
    }
}
