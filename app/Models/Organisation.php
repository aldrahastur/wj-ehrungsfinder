<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $code
 * @property array<string, string> $name
 * @property string $type
 * @property int|null $parent_id
 * @property string|null $country
 * @property string|null $region
 * @property-read OrganisationMembership $pivot  nur beim Laden über User::organisations()
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class Organisation extends Model
{
    use HasTranslations;

    protected $fillable = ['code', 'name', 'type', 'parent_id', 'country', 'region'];

    protected function casts(): array
    {
        return ['name' => 'array'];
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<Honor, $this>
     */
    public function honors(): HasMany
    {
        return $this->hasMany(Honor::class);
    }

    /**
     * @return BelongsToMany<User, $this, OrganisationMembership, 'pivot'>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(OrganisationMembership::class)->withPivot('role')->withTimestamps();
    }
}
