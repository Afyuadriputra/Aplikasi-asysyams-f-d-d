<?php

namespace App\Filament\Resources\RolePermissionResource\Pages;

use App\Filament\Resources\RolePermissionResource;
use App\Features\Permissions\Models\RolePermission;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ListRolePermissions extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = RolePermissionResource::class;

    protected static string $view = 'filament.resources.role-permission-resource.pages.list-role-permissions';

    public ?array $data = [];

    public function mount(): void
    {
        $this->setPermissionsForRole('guru');
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Role')
                    ->description('Pilih role yang akan diatur. Superadmin tidak ditampilkan karena selalu punya semua akses.')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options(RolePermission::ROLES)
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn (?string $state) => $this->setPermissionsForRole($state ?? 'guru')),

                        Forms\Components\Placeholder::make('info')
                            ->label('Info')
                            ->content('Perubahan hak akses berlaku setelah user melakukan reload halaman.'),
                    ])
                    ->columns(2),

                ...$this->permissionSections(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $role = $state['role'] ?? null;

        abort_unless(array_key_exists($role, RolePermission::ROLES), 403);

        foreach (RolePermission::PERMISSIONS as $permission => $label) {
            RolePermission::updateOrCreate(
                [
                    'role' => $role,
                    'permission' => $permission,
                ],
                [
                    'is_allowed' => (bool) data_get($state, 'permissions.' . $this->permissionStateKey($permission), false),
                ],
            );
        }

        Notification::make()
            ->success()
            ->title('Hak akses berhasil diperbarui.')
            ->body('User terkait akan mengikuti akses terbaru setelah reload halaman.')
            ->send();

        $this->setPermissionsForRole($role);
        $this->form->fill($this->data);
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Simpan Hak Akses')
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }

    public function setPermissionsForRole(string $role): void
    {
        abort_unless(array_key_exists($role, RolePermission::ROLES), 403);

        $allowedPermissions = RolePermission::query()
            ->where('role', $role)
            ->where('is_allowed', true)
            ->pluck('permission')
            ->all();

        $permissions = [];

        foreach (RolePermission::PERMISSIONS as $permission => $label) {
            $permissions[$this->permissionStateKey($permission)] = in_array($permission, $allowedPermissions, true);
        }

        $this->data = [
            'role' => $role,
            'permissions' => $permissions,
        ];
    }

    private function permissionSections(): array
    {
        $sections = [];

        foreach (RolePermission::PERMISSION_GROUPS as $group => $permissions) {
            $fields = [];

            foreach ($permissions as $permission => $label) {
                $fields[] = Forms\Components\Toggle::make('permissions.' . $this->permissionStateKey($permission))
                    ->label($label)
                    ->helperText($permission)
                    ->inline(false);
            }

            $sections[] = Forms\Components\Section::make($group)
                ->schema([
                    Forms\Components\Grid::make([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])->schema($fields),
                ])
                ->collapsible();
        }

        return $sections;
    }

    private function permissionStateKey(string $permission): string
    {
        return str_replace('.', '__', $permission);
    }
}
