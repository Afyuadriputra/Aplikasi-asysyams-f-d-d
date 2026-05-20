<?php

namespace App\Filament\Resources;

use App\Features\TeacherAttendances\Models\TeacherAttendance;
use App\Filament\Resources\TeacherAttendanceResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TeacherAttendanceResource extends Resource
{
    use \App\Filament\Concerns\ChecksResourcePermission;

    protected static ?string $model = TeacherAttendance::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Akademik (Ustad)';
    protected static ?string $navigationLabel = 'Absensi Ustad';
    protected static ?string $label = 'Absensi Ustad';
    protected static ?string $pluralLabel = 'Absensi Ustad';

    protected static function permission(): string
    {
        return 'teacher-attendances.manage';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(Auth::user()?->role !== 'superadmin', fn (Builder $query): Builder => $query->where('user_id', Auth::id()));
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Guru/Ustad')
                            ->options(fn (): array => User::query()
                                ->where('role', 'guru')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\DatePicker::make('date')
                            ->label('Tanggal')
                            ->default(now())
                            ->required(),

                        Forms\Components\DateTimePicker::make('check_in_at')
                            ->label('Check In'),

                        Forms\Components\DateTimePicker::make('check_out_at')
                            ->label('Check Out'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(TeacherAttendance::STATUSES)
                            ->required()
                            ->native(false),

                        Forms\Components\Select::make('created_by')
                            ->label('Dibuat oleh')
                            ->relationship('creator', 'name')
                            ->default(fn () => Auth::id())
                            ->disabled()
                            ->dehydrated()
                            ->nullable(),

                        Forms\Components\Textarea::make('note')
                            ->label('Catatan')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Nama Guru')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('check_in_at')
                    ->label('Check In')
                    ->dateTime('H:i')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('check_out_at')
                    ->label('Check Out')
                    ->dateTime('H:i')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => TeacherAttendance::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'present' => 'success',
                        'late' => 'warning',
                        'permission' => 'info',
                        'sick' => 'gray',
                        'alpha' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('note')
                    ->label('Catatan')
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Dibuat oleh')
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date')->label('Tanggal'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['date'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('date', $date))),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Guru')
                    ->relationship('user', 'name', fn (Builder $query): Builder => $query->where('role', 'guru')),

                Tables\Filters\SelectFilter::make('status')
                    ->options(TeacherAttendance::STATUSES),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeacherAttendances::route('/'),
            'create' => Pages\CreateTeacherAttendance::route('/create'),
            'view' => Pages\ViewTeacherAttendance::route('/{record}'),
            'edit' => Pages\EditTeacherAttendance::route('/{record}/edit'),
        ];
    }
}
