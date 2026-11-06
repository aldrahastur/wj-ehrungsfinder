<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Zuordnung einer Quelle zu einer Regelversion, optional mit Fundstelle.
 *
 * @property int $rule_version_id
 * @property int $source_id
 * @property string|null $locator
 */
class RuleVersionSource extends Pivot
{
    protected $table = 'rule_version_source';

    public $timestamps = false;
}
