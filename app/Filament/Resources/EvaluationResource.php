<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EvaluationResource\Pages;
use App\Features\Academic\Models\ClassGroup;
use App\Features\Grades\Models\Evaluation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EvaluationResource extends Resource
{
    use \App\Filament\Concerns\ChecksResourcePermission;

    protected static ?string $model = Evaluation::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Akademik (Ustad)';

    protected static ?string $navigationLabel = 'Input Evaluasi';
    protected static ?string $label = 'Input Evaluasi';
    protected static ?string $pluralLabel = 'Input Evaluasi';
    protected static ?int $navigationSort = 3;

    protected static function permission(): string
    {
        return 'evaluations.manage';
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
                        ->live(),

                    Forms\Components\Select::make('user_ids')
                        ->label('Pilih Santri (bisa pilih banyak)')
                        ->options(function (Forms\Get $get) {
                            $classGroupId = $get('class_group_id');
                            if (!$classGroupId) {
                                return [];
                            }
                            return ClassGroup::find($classGroupId)
                                ?->students()
                                ->pluck('users.name', 'users.id') ?? [];
                        })
                        ->multiple()
                        ->required()
                        ->searchable()
                        ->visible(fn (string $operation) => $operation === 'create'),

                    // Single student field for edit mode only
                    Forms\Components\Select::make('user_id')
                        ->label('Santri')
                        ->options(function (Forms\Get $get) {
                            $classGroupId = $get('class_group_id');
                            if (!$classGroupId) {
                                return [];
                            }
                            return ClassGroup::find($classGroupId)
                                ?->students()
                                ->pluck('users.name', 'users.id') ?? [];
                        })
                        ->required()
                        ->searchable()
                        ->visible(fn (string $operation) => $operation === 'edit'),

                    Forms\Components\Select::make('evaluation_number')
                        ->label('Evaluasi Ke-')
                        ->options([
                            1 => 'Evaluasi 1',
                            2 => 'Evaluasi 2',
                            3 => 'Evaluasi 3',
                            4 => 'Evaluasi 4',
                        ])
                        ->required(),

                    Forms\Components\TextInput::make('surah_name')
                        ->label('Nama Surah')
                        ->maxLength(255)
                        ->nullable(),

                    Forms\Components\TextInput::make('song_name')
                        ->label('Nama Lagu')
                        ->maxLength(255)
                        ->nullable(),
                ])->columns(3),

                // Repeater untuk Item Evaluasi
                Forms\Components\Section::make('Item Evaluasi')
                    ->description('Tambahkan item evaluasi dengan checkbox dan nilai')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Item')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Catatan')
                                    ->placeholder('Masukkan Catatan')
                                    ->required()
                                    ->columnSpan(2),

                                Forms\Components\Checkbox::make('checked')
                                    ->label('Tercapai?')
                                    ->columnSpan(1)
                                    ->inline(),

                                Forms\Components\TextInput::make('score')
                                    ->label('Nilai Angka')
                                    ->numeric()
                                    ->maxValue(100)
                                    ->columnSpan(2),
                            ])
                            ->columns(5)
                            ->addActionLabel('+ Tambah Item')
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

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Santri')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('evaluation_number')
                    ->label('Evaluasi Ke-')
                    ->badge()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('surah_name')
                    ->label('Surah')
                    ->searchable()
                    ->limit(24)
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('song_name')
                    ->label('Lagu')
                    ->searchable()
                    ->limit(24)
                    ->placeholder('-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Dibuat')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('evaluation_number')
                    ->label('Filter Evaluasi')
                    ->options([
                        1 => 'Evaluasi 1',
                        2 => 'Evaluasi 2',
                        3 => 'Evaluasi 3',
                        4 => 'Evaluasi 4',
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvaluations::route('/'),
            'create' => Pages\CreateEvaluation::route('/create'),
            'edit' => Pages\EditEvaluation::route('/{record}/edit'),
        ];
    }
}
