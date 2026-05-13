<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingResource\Pages;
use App\Filament\Resources\MeetingResource\RelationManagers;
use App\Features\Meetings\Models\Meeting;
use App\Features\Academic\Models\ClassGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MeetingResource extends Resource
{
    use \App\Filament\Concerns\ChecksResourcePermission;

    protected static ?string $model = Meeting::class;
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Akademik (Ustad)';
    protected static ?string $navigationLabel = 'Jadwal & Absensi';
    protected static ?string $label = 'Jadwal & Absensi';
    protected static ?string $pluralLabel = 'Jadwal & Absensi';

    protected static function permission(): string
    {
        return 'meetings.manage';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Pertemuan')->schema([
                    Forms\Components\Select::make('class_group_id')
                        ->relationship('classGroup', 'name')
                        ->label('Pilih Kelas')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live() // PENTING: Memicu form update saat kelas dipilih
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            // Jika kelas kosong, kosongkan juga tabel siswa
                            if (!$state) {
                                $set('students_attendance', []);
                                return;
                            }

                            // Ambil siswa berdasarkan kelas yang dipilih
                            $classGroup = \App\Features\Academic\Models\ClassGroup::find($state);
                            
                            $attendanceData = []; // Inisialisasi array penampung
                            
                            if ($classGroup && $classGroup->students) {
                                // Looping untuk memasukkan semua siswa ke dalam repeater
                                foreach ($classGroup->students as $student) {
                                    $attendanceData[] = [
                                        'user_id' => $student->id,
                                        'name' => $student->name,
                                        // Pastikan value ini huruf kecil agar cocok dengan ENUM database
                                        'status' => 'present', 
                                    ];
                                }
                            }

                            // Masukkan data ke Repeater
                            $set('students_attendance', $attendanceData);
                        }),

                    Forms\Components\Hidden::make('user_id')
                        ->default(fn () => Auth::id()),
                    
                    Forms\Components\TextInput::make('title')
                        ->label('Topik / Materi')
                        ->placeholder('Contoh: Makhrajul Huruf Ba')
                        ->required()
                        ->maxLength(255),
                    
                    Forms\Components\DatePicker::make('date')
                        ->label('Tanggal Pertemuan')
                        ->default(now())
                        ->required(),
                ])->columns(2),

                // TABEL ABSENSI SISWA MUNCUL DI SINI
                Forms\Components\Section::make('Daftar Kehadiran Santri')
                    ->schema([
                        Forms\Components\Repeater::make('students_attendance')
                            ->hiddenLabel()
                            ->schema([
                                Forms\Components\Hidden::make('user_id'),
                                
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Santri')
                                    ->disabled() // Disable agar Ustad tidak bisa ganti nama santri
                                    ->columnSpan(2),

                                // Menggunakan ToggleButtons agar seperti pill/tombol warna-warni yang rapi
                                Forms\Components\ToggleButtons::make('status')
                                    ->label('Status')
                                    ->options([
                                        // Key di sebelah kiri (huruf kecil) yang akan masuk ke database
                                        'present' => 'Hadir',
                                        'sick' => 'Sakit',
                                        'permission'  => 'Izin',
                                        'alpha' => 'Alpha',
                                    ])
                                    ->colors([
                                        'present' => 'success',
                                        'sick' => 'info',
                                        'permission'  => 'warning',
                                        'alpha' => 'danger',
                                    ])
                                    ->inline()
                                    ->required()
                                    ->columnSpan(2),
                            ])
                            ->columns(4)
                            ->addable(false) // Matikan tombol tambah baris
                            ->deletable(false) // Matikan tombol hapus baris
                            ->reorderable(false) // Matikan tombol geser
                    ])
                    // Section ini hanya muncul kalau Ustad sudah milih kelas
                    ->visible(fn (Forms\Get $get) => filled($get('class_group_id'))), 
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('classGroup.name')
                    ->label('Kelas')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('title')
                    ->label('Materi')
                    ->searchable(),

                Tables\Columns\TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('attendances_count')
                    ->counts('attendances')
                    ->label('Jml Siswa')
                    ->badge(),

                Tables\Columns\TextColumn::make('teacher.name')
                    ->label('Pengajar')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('class_group_id')
                    ->relationship('classGroup', 'name')
                    ->label('Filter Kelas'),
            ])
            ->actions([
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
            RelationManagers\AttendancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeetings::route('/'),
            'create' => Pages\CreateMeeting::route('/create'),
            'edit' => Pages\EditMeeting::route('/{record}/edit'),
        ];
    }
}
