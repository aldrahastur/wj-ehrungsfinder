<?php

namespace App\Models;

use App\Domain\Ehrungen\QuestionDefinition;
use App\Models\Concerns\HasTranslations;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property string $answer_type
 * @property array<string, string> $text
 * @property array<string, string> $criterion
 * @property array<string, string>|null $explanation
 * @property string|null $region
 * @property list<array{question: string, answer: string, implies: string}>|null $implied_by
 * @property int $sort
 * @property bool $active
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
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

    public function toDefinition(): QuestionDefinition
    {
        return new QuestionDefinition($this->code, $this->region, $this->implied_by ?? []);
    }
}
