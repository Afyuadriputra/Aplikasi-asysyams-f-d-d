<?php

namespace Database\Seeders;

use App\Features\Permissions\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'guru' => [
                'dashboard.view',
                'classes.view',
                'classes.manage',
                'meetings.view',
                'meetings.manage',
                'attendances.view',
                'attendances.manage',
                'teacher-attendances.check-in',
                'assessments.view',
                'assessments.manage',
                'evaluations.view',
                'evaluations.manage',
                'grades.view',
                'grades.manage',
                'reports.view',
                'reports.download',
            ],
            'student' => [
                'dashboard.view',
                'reports.view',
                'reports.download',
                'payments.checkout',
            ],
        ];

        foreach (RolePermission::ROLES as $role => $label) {
            foreach (RolePermission::PERMISSIONS as $permission => $permissionLabel) {
                RolePermission::updateOrCreate(
                    [
                        'role' => $role,
                        'permission' => $permission,
                    ],
                    [
                        'is_allowed' => in_array($permission, $defaults[$role] ?? [], true),
                    ]
                );
            }
        }
    }
}
