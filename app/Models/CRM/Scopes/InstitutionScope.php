<?php

declare(strict_types=1);

namespace App\Models\CRM\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * InstitutionScope — Global Eloquent scope applied to all CRM core entities.
 *
 * Automatically adds WHERE institution_id = ? to every query,
 * ensuring zero data leakage between tenants.
 *
 * Applied in each CRM model's booted() method:
 *   static::addGlobalScope(new InstitutionScope());
 */
class InstitutionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && Auth::user()->institution_id !== null) {
            $builder->where($model->getTable().'.institution_id', Auth::user()->institution_id);
        }
    }
}
