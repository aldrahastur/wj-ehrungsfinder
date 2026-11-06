<?php

namespace App\Models;

use App\Domain\Ehrungen\QuestionDefinition;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasTranslations;

    protected $fillable = ['code', 'answer_type', 'text', 'criterion', 'explanation', 'region', 'implied_by', 'sort', 'active'];

    protected function casts(): array
    {
        return [
            'text' => 'array',
            'criterion' => 'array',
            'explanation' => 'array',
            'implied_by' => 'array',
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('active', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort')->orderBy('id');
    }

    public function toDefinition(): QuestionDefinition
    {
        return new QuestionDefinition($this->code, $this->region, $this->implied_by ?? []);
    }
}
