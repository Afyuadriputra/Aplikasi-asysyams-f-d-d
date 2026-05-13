<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Filament\Resources\ReportResource\RelationManagers;
use App\Features\Academic\Models\ClassGroup;
use Filament\Resources\Resource;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Table;

class ReportResource extends Resource
{
    use \App\Filament\Concerns\ChecksResourcePermission;

    protected static ?string $model = ClassGroup::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Akademik (Ustad)';
    protected static ?string $navigationLabel = 'Raport Santri';
    protected static ?string $slug = 'raport';
    protected static ?string $label = 'Raport Santri';
    protected static ?string $pluralLabel = 'Raport Santri';

    protected static function permission(): string
    {
        return 'reports.view';
    }


    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Kelas & Raport')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')->label('Nama Kelas')->weight('bold'),
                        Infolists\Components\TextEntry::make('subject.name')->label('Mata Pelajaran')->badge()->color('info'),
                        Infolists\Components\TextEntry::make('teacher.name')->label('Ustad Pengampu'),
                        Infolists\Components\TextEntry::make('semester.name')->label('Semester'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama Kelas')->searchable(),
                Tables\Columns\TextColumn::make('subject.name')->label('Mapel')->badge(),
                Tables\Columns\TextColumn::make('teacher.name')->label('Ustad'),
                Tables\Columns\TextColumn::make('students_count')->label('Jml Santri')->counts('students')->badge(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Lihat Raport')->icon('heroicon-o-eye'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Daftarkan Relation Manager yang kita buat tadi di sini
            RelationManagers\StudentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'view' => Pages\ViewReport::route('/{record}'),
        ];
    }
}
