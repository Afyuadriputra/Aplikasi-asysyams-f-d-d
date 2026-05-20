<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AssessmentResource\Pages;
use App\Features\Grades\Models\Assessment;
use App\Features\Academic\Models\ClassGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AssessmentResource extends Resource
{
    use \App\Filament\Concerns\ChecksResourcePermission;

    protected static ?string $model = Assessment::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static ?string $navigationGroup = 'Akademik (Ustad)';
    protected static ?string $navigationLabel = 'Input Penilaian Santri';
    protected static ?int $navigationSort = 2;
    protected static ?string $label = 'Input Penilaian';
    protected static ?string $pluralLabel = 'Input Penilaian';

    protected static function permission(): string
    {
        return 'assessments.manage';
    }

    /**
     * Helper untuk mengambil data surat dari API equran.id
     */
    protected static function getSurahData()
    {
        return Cache::remember('alquran_surah', 86400, function () {
            $response = Http::get('https://equran.id/api/v2/surat');
            return $response->json()['data'] ?? [];
        });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\Select::make('class_group_id')
                        ->label('Pilih Kelas')
                        ->options(ClassGroup::all()->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live(),

                    Forms\Components\Select::make('user_id')
                        ->label('Pilih Santri')
                        ->options(function (Forms\Get $get) {
                            $classGroupId = $get('class_group_id');
                            if (!$classGroupId) return [];
                            
                            return ClassGroup::find($classGroupId)
                                ?->students()
                                ->pluck('users.name', 'users.id') ?? []; 
                        })
                        ->required()
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('assessment_type')
                        ->label('Jenis Penilaian')
                        ->options([
                            'tahsin' => 'Tahsin',
                            'tahfidz' => 'Tahfidz',
                            'tajwid' => 'Tajwid',
                        ])
                        ->required(),
                    
                    Forms\Components\Select::make('month')
                        ->label('Bulan')
                        ->options([
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                        ])
                        ->default(now()->month)
                        ->required(),

                    Forms\Components\TextInput::make('year')
                        ->label('Tahun')
                        ->type('number')
                        ->default(now()->year)
                        ->required(),
                ])->columns(3),

                // FORM PENILAIAN YANG LEBIH LENGKAP
                Forms\Components\Section::make('Detail Penilaian')
                    ->description('Catat nama santri, nilai penyetoran, dan detail hafalan/bacaan')
                    ->schema([
                        Forms\Components\Repeater::make('data')
                            ->label('Detail Hafalan/Bacaan')
                            ->schema([
                                Forms\Components\TextInput::make('nama')
                                    ->label('Nama Santri')
                                    ->placeholder('Abdullah bin Usman')
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('nilai_penyetoran')
                                    ->label('Nilai Penyetoran')
                                    ->type('number')
                                    ->placeholder('85')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\Select::make('surah')
                                    ->label('Surah')
                                    ->options(function () {
                                        return collect(static::getSurahData())
                                            ->pluck('namaLatin', 'namaLatin')
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->live() 
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('ayat')
                                    ->label('Ayat')
                                    ->placeholder(function (Forms\Get $get) {
                                        $surahName = $get('surah');
                                        if (!$surahName) return 'Pilih surah dulu...';
                                        
                                        $surah = collect(static::getSurahData())->firstWhere('namaLatin', $surahName);
                                        return $surah ? "Contoh: 1-{$surah['jumlahAyat']}" : "Input ayat";
                                    })
                                    ->hint(function (Forms\Get $get) {
                                        $surahName = $get('surah');
                                        if ($surahName) {
                                            $surah = collect(static::getSurahData())->firstWhere('namaLatin', $surahName);
                                            return $surah ? "Total: {$surah['jumlahAyat']} ayat" : null;
                                        }
                                        return null;
                                    })
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\Select::make('nilai')
                                    ->label('Nilai')
                                    ->options([
                                        'L' => 'L (Lancar)',
                                        'C' => 'C (Cukup)',
                                        'TL' => 'TL (Tidak Lancar)',
                                    ])
                                    ->required()
                                    ->columnSpan(1),

                                Forms\Components\Textarea::make('catatan')
                                    ->label('Catatan Performa')
                                    ->placeholder('Contoh: Bacaan makin lancar, masih perlu latihan mad.')
                                    ->rows(2)
                                    ->maxLength(500)
                                    ->nullable()
                                    ->columnSpanFull(),
                            ])
                            ->columns(5)
                            ->addActionLabel('+ Tambah Baris')
                            ->defaultItems(1),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('classGroup.name')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                // INI PERUBAHANNYA: Menampilkan Ustad pengampu dari relasi Kelas
                // Asumsi di model ClassGroup kamu ada relasi bernama teacher() atau ustad()
                Tables\Columns\TextColumn::make('classGroup.teacher.name') 
                    ->label('Ustad')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Santri')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('assessment_type')
                    ->label('Jenis Penilaian')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ziyadah' => 'success',
                        'murojaah' => 'info',
                        'tahsin' => 'warning',
                        'tilawah' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Tanggal')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('assessment_type')
                    ->options([
                        'ziyadah' => 'Ziyadah',
                        'murojaah' => 'Murojaah',
                        'tahsin' => 'Tahsin',
                        'tilawah' => 'Tilawah',
                    ]),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssessments::route('/'),
            'create' => Pages\CreateAssessment::route('/create'),
            'edit' => Pages\EditAssessment::route('/{record}/edit'),
        ];
    }
}
