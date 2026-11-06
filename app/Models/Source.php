<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Source extends Model
{
    use HasTranslations;

    protected $fillable = ['code', 'title', 'url', 'note', 'retrieved_at'];

    protected function casts(): array
    {
        return ['note' => 'array', 'retrieved_at' => 'date'];
    }

    public function ruleVersions(): BelongsToMany
    {
        return $this->belongsToMany(RuleVersion::class)->withPivot('locator');
    }
}
