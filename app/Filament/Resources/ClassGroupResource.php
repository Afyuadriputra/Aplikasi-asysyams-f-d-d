<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClassGroupResource\Pages;
use App\Features\Academic\Models\ClassGroup;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ClassGroupResource extends Resource
{
    use \App\Filament\Concerns\ChecksResourcePermission;

    protected static ?string $model = ClassGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Akademik (Ustad)';

    protected static ?string $navigationLabel = 'Kelola Kelas';
    protected static ?string $label = 'Kelola Kelas';
    protected static ?string $pluralLabel = 'Kelola Kelas';

    protected static ?int $navigationSort = 1;

    protected static function permission(): string
    {
        return 'classes.manage';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\Select::make('subject_id')
                        ->label('Mata Pelajaran')
                        ->options(Subject::all()->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (string $operation, $state, Forms\Set $set, Forms\Get $get) {
                            if ($operation === 'create') {
                                static::generateClassName($set, $get);
                            }
                        }),

                    Forms\Components\Select::make('semester_id')
                        ->label('Semester')
                        ->options(Semester::all()->pluck('name', 'id'))
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (string $operation, $state, Forms\Set $set, Forms\Get $get) {
                            if ($operation === 'create') {
                                static::generateClassName($set, $get);
                            }
                        }),

                    Forms\Components\TextInput::make('name')
                        ->label('Nama Kelas')
                        ->required()
                        ->placeholder('Otomatis terisi setelah pilih mapel & semester')
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\Select::make('teacher_id')
                        ->label('Ustad / Pengajar')
                        ->options(User::whereIn('role', ['superadmin', 'guru'])->pluck('name', 'id'))
                        ->searchable()
                        ->nullable(),

                    // Forms\Components\Textarea::make('description')
                    //     ->label('Deskripsi (Opsional)')
                    //     ->rows(2)
                    //     ->columnSpanFull(),

                    Forms\Components\Hidden::make('slug'),
                ])->columns(2)
            ]);
    }

    protected static function generateClassName(Forms\Set $set, Forms\Get $get): void
    {
        $subjectId = $get('subject_id');
        $semesterId = $get('semester_id');

        if (!$subjectId || !$semesterId) {
            return;
        }

        $subject = Subject::find($subjectId);
        if (!$subject) {
            return;
        }

        $existingCount = ClassGroup::where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->count();

        $letter = chr(65 + $existingCount);

        $name = $subject->name . ' ' . $letter;
        $set('name', $name);
        $set('slug', Str::slug($name));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Kelas')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Mapel')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('semester.name')
                    ->label('Semester')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Ustad')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Dibuat')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('subject_id')
                    ->label('Filter Mapel')
                    ->options(Subject::pluck('name', 'id')),

                Tables\Filters\SelectFilter::make('semester_id')
                    ->label('Filter Semester')
                    ->options(Semester::pluck('name', 'id')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Gunakan full path/namespace seperti ini:
            \App\Filament\Resources\ClassGroupResource\RelationManagers\StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassGroups::route('/'),
            'create' => Pages\CreateClassGroup::route('/create'),
            'view' => Pages\ViewClassGroup::route('/{record}'),
            'edit' => Pages\EditClassGroup::route('/{record}/edit'),
        ];
    }
}
