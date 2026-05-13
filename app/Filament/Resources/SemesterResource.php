<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SemesterResource\Pages;
use App\Features\Academic\Models\Semester;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SemesterResource extends Resource
{
    use \App\Filament\Concerns\ChecksResourcePermission;

    protected static ?string $model = Semester::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    protected static ?string $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Periode Semester';

    protected static function permission(): string
    {
        return 'semesters.manage';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama Semester')
                        ->placeholder('Contoh: Ganjil 2024/2025')
                        ->required()
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('tuition_fee')
                        ->label('Biaya SPP (Rupiah)')
                        ->prefix('Rp')
                        ->numeric()
                        ->required(),
                        
                    Forms\Components\DatePicker::make('start_date')
                        ->label('Tanggal Mulai')
                        ->required(),
                        
                    Forms\Components\DatePicker::make('end_date')
                        ->label('Tanggal Berakhir')
                        ->required(),
                        
                    Forms\Components\Toggle::make('is_active')
                        ->label('Set Semester Aktif')
                        ->helperText('Hanya satu semester yang boleh aktif. Mengaktifkan ini akan menonaktifkan semester lain (Logic nanti).')
                        ->default(false),
                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Periode')
                    ->sortable()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('tuition_fee')
                    ->label('SPP')
                    ->money('IDR'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('start_date')
                    ->date(),
                    
                Tables\Columns\TextColumn::make('end_date')
                    ->date(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListSemesters::route('/'),
            'create' => Pages\CreateSemester::route('/create'),
            'edit' => Pages\EditSemester::route('/{record}/edit'),
        ];
    }
}
