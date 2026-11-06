<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $rule_version_id
 * @property int|null $user_id
 * @property string $action
 * @property string|null $note
 * @property CarbonImmutable|null $created_at
 */
class RuleVersionEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['rule_version_id', 'user_id', 'action', 'note'];

    /**
     * @return BelongsTo<RuleVersion, $this>
     */
    public function ruleVersion(): BelongsTo
    {
        return $this->belongsTo(RuleVersion::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
