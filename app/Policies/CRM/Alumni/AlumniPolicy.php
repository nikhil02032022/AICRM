<?php

declare(strict_types=1);

namespace App\Policies\CRM\Alumni;

use App\Models\CRM\Alumni\AlumniPipeline;
use App\Models\User;

class AlumniPolicy
{
    public function viewAny(User $user): bool { return $user->can('crm.alumni.pipeline.view'); }
    public function view(User $user, AlumniPipeline $record): bool { return $user->can('crm.alumni.pipeline.view') && $user->institution_id === $record->institution_id; }
    public function create(User $user): bool { return $user->can('crm.alumni.pipeline.manage'); }
    public function update(User $user, AlumniPipeline $record): bool { return $user->can('crm.alumni.pipeline.manage') && $user->institution_id === $record->institution_id; }
    public function delete(User $user, AlumniPipeline $record): bool { return false; }
}
