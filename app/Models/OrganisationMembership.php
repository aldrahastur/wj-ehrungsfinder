<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Fachliche Rolle eines Users in einem Regelpaket (Organisation).
 *
 * @property int $organisation_id
 * @property int $user_id
 * @property string $role
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
class OrganisationMembership extends Pivot
{
    protected $table = 'organisation_user';
}
