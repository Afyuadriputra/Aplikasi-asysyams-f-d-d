<?php

namespace App\Filament\Resources;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Grades\Models\Grade;
use App\Features\Grades\Services\GradeReportService;
use App\Filament\Resources\GradeResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class GradeResource extends Resource
{
    use \App\Filament\Concerns\ChecksResourcePermission;

    protected static ?string $model = Grade::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Akademik (Ustad)';

    protected static ?string $navigationLabel = 'Nilai Santri';

    protected static ?int $navigationSort = 4;

    protected static function permission(): string
    {
        return 'grades.manage';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('student', 'name', modifyQueryUsing: function ($query) {
                        $user = auth()->user();
                        if ($user && $user->role === 'guru') {
                            $query->whereHas('classGroups', fn ($q) => $q->where('teacher_id', $user->id));
                        }
                    })
                    ->label('Santri')
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('subject_id')
                    ->relationship('subject', 'name', modifyQueryUsing: function ($query) {
                        $user = auth()->user();
                        if ($user && $user->role === 'guru') {
                            $query->whereHas('classGroups', fn ($q) => $q->where('teacher_id', $user->id));
                        }
                    })
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('semester_id')
                    ->relationship('semester', 'name', modifyQueryUsing: function ($query) {
                        $user = auth()->user();
                        if ($user && $user->role === 'guru') {
                            $query->whereHas('classGroups', fn ($q) => $q->where('teacher_id', $user->id));
                        }
                    })
                    ->label('Semester')
                    ->searchable()
                    ->required(),

                Forms\Components\TextInput::make('score')
                    ->label('Nilai')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->required(),

                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Santri')
                    ->searchable(),

                Tables\Columns\TextColumn::make('subject.name')
                    ->label('Mata Pelajaran')
                    ->badge(),

                Tables\Columns\TextColumn::make('semester.name')
                    ->label('Semester'),

                Tables\Columns\TextColumn::make('score')
                    ->label('Nilai')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('lihat_tabel_santri')
                    ->label('Lihat Tabel Santri')
                    ->icon('heroicon-o-table-cells')
                    ->modalHeading(fn (Grade $record): string => 'Tabel Nilai ' . $record->student?->name)
                    ->modalWidth('7xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->visible(fn (Grade $record): bool => static::canAccessStudentControl($record))
                    ->modalContent(fn (Grade $record) => view('grades.student-control-modal', app(GradeReportService::class)->buildStudentControlReport($record->student, $record))),

                Tables\Actions\Action::make('download_pdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (Grade $record): bool => static::canAccessStudentControl($record))
                    ->url(fn (Grade $record): string => route('admin.grades.student-control.pdf', [
                        'user' => $record->user_id,
                        'grade' => $record->id,
                    ]))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListGrades::route('/'),
            'create' => Pages\CreateGrade::route('/create'),
            'edit' => Pages\EditGrade::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->role === 'guru') {
            return $query->whereExists(function ($sub) use ($user) {
                $sub->selectRaw(1)
                    ->from('class_groups')
                    ->join('class_group_student', 'class_groups.id', '=', 'class_group_student.class_group_id')
                    ->where('class_groups.teacher_id', $user->id)
                    ->whereColumn('class_groups.subject_id', 'grades.subject_id')
                    ->whereColumn('class_groups.semester_id', 'grades.semester_id')
                    ->whereColumn('class_group_student.user_id', 'grades.user_id')
                    ->whereNull('class_group_student.deleted_at');
            });
        }

        return $query;
    }

    protected static function canTeacherAccessGrade(?User $user, ?Grade $record): bool
    {
        if (! $user || ! $record) {
            return false;
        }

        if ($user->role !== 'guru') {
            return true;
        }

        return ClassGroup::query()
            ->where('teacher_id', $user->id)
            ->where('subject_id', $record->subject_id)
            ->where('semester_id', $record->semester_id)
            ->whereHas('students', fn ($q) => $q->where('users.id', $record->user_id))
            ->exists();
    }

    public static function canView($record): bool
    {
        if (! static::canAccessAction('view')) {
            return false;
        }

        $user = auth()->user();
        if ($user && $user->role === 'guru') {
            return static::canTeacherAccessGrade($user, $record);
        }

        return true;
    }

    public static function canEdit($record): bool
    {
        if (! static::canAccessAction('update')) {
            return false;
        }

        $user = auth()->user();
        if ($user && $user->role === 'guru') {
            return static::canTeacherAccessGrade($user, $record);
        }

        return true;
    }

    public static function canDelete($record): bool
    {
        if (! static::canAccessAction('delete')) {
            return false;
        }

        $user = auth()->user();
        if ($user && $user->role === 'guru') {
            return static::canTeacherAccessGrade($user, $record);
        }

        return true;
    }

    private static function canAccessStudentControl(Grade $record): bool
    {
        $user = Auth::user();

        if (! $user || ! $record->student) {
            return false;
        }

        if ($user->role === 'guru' && ! static::canTeacherAccessGrade($user, $record)) {
            return false;
        }

        return app(GradeReportService::class)->canAccessStudentReport($user, $record->student, $record);
    }
}
