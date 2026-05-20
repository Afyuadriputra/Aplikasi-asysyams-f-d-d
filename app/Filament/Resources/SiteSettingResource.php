<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteSettingResource\Pages;
use App\Features\SiteSettings\Models\SiteSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteSettingResource extends Resource
{
    use \App\Filament\Concerns\ChecksResourcePermission;

    protected static ?string $model = SiteSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Pengaturan';
    
    protected static ?string $navigationLabel = 'Setting Website';

    protected static function permission(): string
    {
        return 'settings.manage';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\TextInput::make('key')
                        ->label('Kunci Pengaturan (Key)')
                        ->helperText('Gunakan "hero_bg" untuk gambar, "spmb_deadline" untuk waktu countdown.')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (string $operation) => $operation === 'edit')
                        ->live(onBlur: true), 

                    // 1. INPUT GAMBAR
                    Forms\Components\FileUpload::make('value_image')
                        ->label('Upload Gambar')
                        ->image()
                        ->directory('settings')
                        ->visible(fn (Forms\Get $get) => 
                            str_contains($get('key') ?? '', 'image') || str_contains($get('key') ?? '', 'bg')
                        ),

                    // 2. INPUT TANGGAL
                    Forms\Components\DateTimePicker::make('value_date')
                        ->label('Waktu Berakhir (Deadline)')
                        ->seconds(false)
                        ->visible(fn (Forms\Get $get) => ($get('key') ?? '') === 'spmb_deadline'),

                    // 3. INPUT TEKS BIASA
                    Forms\Components\Textarea::make('value_text')
                        ->label('Isi Value')
                        ->rows(3)
                        ->visible(fn (Forms\Get $get) => 
                            !str_contains($get('key') ?? '', 'image') && 
                            !str_contains($get('key') ?? '', 'bg') && 
                            ($get('key') ?? '') !== 'spmb_deadline'
                        ),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Setting')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                // Preview Gambar
                Tables\Columns\ImageColumn::make('value_image')
                    ->state(fn ($record) => $record->value)
                    ->label('Preview')
                    ->visible(fn ($record) => 
                        $record && (str_contains($record->key, 'image') || str_contains($record->key, 'bg'))
                    ),

                // Preview Tanggal
                Tables\Columns\TextColumn::make('value_date')
                    ->state(fn ($record) => $record->value)
                    ->label('Waktu')
                    ->date('d F Y H:i')
                    ->visible(fn ($record) => $record && $record->key === 'spmb_deadline'),

                // Preview Teks Biasa
                Tables\Columns\TextColumn::make('value_text')
                    ->state(fn ($record) => $record->value)
                    ->label('Isi')
                    ->limit(50)
                    ->hidden(fn ($record) => 
                        $record && (
                            str_contains($record->key, 'image') || 
                            str_contains($record->key, 'bg') || 
                            $record->key === 'spmb_deadline'
                        )
                    ),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Update')
                    ->dateTime(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiteSettings::route('/'),
            'create' => Pages\CreateSiteSetting::route('/create'),
            'edit' => Pages\EditSiteSetting::route('/{record}/edit'),
            'manage-spmb' => Pages\ManageSPMBDeadline::route('/spmb-deadline'),
            'manage-teacher-attendance' => Pages\ManageTeacherAttendanceSchedule::route('/teacher-attendance-schedule'),
        ];
    }
}
