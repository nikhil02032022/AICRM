<?php

declare(strict_types=1);

namespace App\Policies\CRM\Admin;

use App\Models\CRM\Admin\AcademicYear;
use App\Models\User;

class AcademicYearPolicy
{
    public function viewAny(User $user): bool { return $user->can('crm.admin.academic-years.manage'); }
    public function create(User $user): bool { return $user->can('crm.admin.academic-years.manage'); }
    public function update(User $user, AcademicYear $year): bool { return $user->can('crm.admin.academic-years.manage') && $user->institution_id === $year->institution_id; }
    public function delete(User $user, AcademicYear $year): bool { return false; }
}
