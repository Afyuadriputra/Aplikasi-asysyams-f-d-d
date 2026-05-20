<?php

namespace App\Filament\Resources\SiteSettingResource\Pages;

use App\Filament\Resources\SiteSettingResource;
use App\Features\SiteSettings\Models\SiteSetting;
use App\Features\TeacherAttendances\Services\TeacherAttendanceService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ManageTeacherAttendanceSchedule extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SiteSettingResource::class;

    protected static string $view = 'filament.pages.manage-teacher-attendance-schedule';

    protected static ?string $title = 'Atur Jadwal Absensi Ustad';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Jadwal Absensi Ustad';

    protected static ?int $navigationSort = 2;

    public ?array $data = [];

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->hasAnyAccess(['settings.update', 'settings.manage']) ?? false;
    }

    public function mount(): void
    {
        $this->data['late_after'] = SiteSetting::query()
            ->where('key', TeacherAttendanceService::LATE_AFTER_SETTING_KEY)
            ->value('value') ?: TeacherAttendanceService::DEFAULT_LATE_AFTER;

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Jadwal Absensi Ustad')
                    ->description('Atur batas waktu check-in. Guru yang check-in setelah jam ini otomatis berstatus Terlambat.')
                    ->schema([
                        Forms\Components\TimePicker::make('late_after')
                            ->label('Batas Terlambat')
                            ->helperText('Contoh: 08:00. Check-in 08:01 ke atas menjadi Terlambat.')
                            ->seconds(false)
                            ->required()
                            ->native(false),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Simpan Jadwal')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        SiteSetting::updateOrCreate(
            ['key' => TeacherAttendanceService::LATE_AFTER_SETTING_KEY],
            ['value' => $data['late_after']]
        );

        Notification::make()
            ->success()
            ->title('Berhasil')
            ->body('Jadwal absensi ustad telah diperbarui.')
            ->send();
    }
}
