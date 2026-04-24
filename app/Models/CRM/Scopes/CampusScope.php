<?php

declare(strict_types=1);

namespace App\Models\CRM\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class CampusScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && Auth::user()->campus_id !== null) {
            $builder->where($model->getTable().'.campus_id', Auth::user()->campus_id);
        }
    }
}
