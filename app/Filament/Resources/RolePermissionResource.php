<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RolePermissionResource\Pages;
use App\Features\Permissions\Models\RolePermission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RolePermissionResource extends Resource
{
    protected static ?string $model = RolePermission::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Hak Akses Role';

    protected static ?string $navigationGroup = 'Manajemen Akses';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->role === 'superadmin';
    }

    public static function canViewAny(): bool
    {
        return Auth::user()?->role === 'superadmin';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('role')
                    ->label('Role')
                    ->options(RolePermission::ROLES)
                    ->required()
                    ->native(false),

                Forms\Components\CheckboxList::make('permissions')
                    ->label('Permission')
                    ->options(RolePermission::PERMISSIONS)
                    ->columns(2),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('role', array_keys(RolePermission::ROLES));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRolePermissions::route('/'),
        ];
    }

    public static function notifyPermissionUpdated(): void
    {
        Notification::make()
            ->success()
            ->title('Hak akses berhasil diperbarui.')
            ->body('User terkait akan mengikuti akses terbaru setelah reload halaman.')
            ->send();
    }
}
