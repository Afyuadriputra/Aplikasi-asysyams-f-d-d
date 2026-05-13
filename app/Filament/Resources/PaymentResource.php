<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Features\Payments\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    use \App\Filament\Concerns\ChecksResourcePermission;

    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationGroup = 'Keuangan';

    protected static ?string $navigationLabel = 'Data Pembayaran';

    protected static function permission(): string
    {
        return 'payments.manage';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\Select::make('semester_id')
                        ->relationship('semester', 'name')
                        ->required(),
                    
                    Forms\Components\Select::make('user_id')
                        ->relationship('student', 'name') // Pastikan relasi di model Payment namanya 'student'
                        ->label('Siswa')
                        ->searchable()
                        ->required(),

                    Forms\Components\TextInput::make('amount')
                        ->label('Jumlah Bayar')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),

                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending (Menunggu)',
                            'success' => 'Lunas (Success)',
                            'failed' => 'Gagal',
                        ])
                        ->required(),
                        
                    Forms\Components\TextInput::make('order_id')
                        ->label('Order ID (Unik)')
                        ->default('MANUAL-' . time())
                        ->required()
                        ->disabledOn('edit'),
                ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_id')
                    ->label('ID Transaksi')
                    ->searchable()
                    ->copyable()
                    ->limit(15),

                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('semester.name')
                    ->label('Semester')
                    ->sortable(),

                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->label('Nominal'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Metode')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Tanggal')
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Lunas',
                        'pending' => 'Belum Lunas',
                    ]),
                Tables\Filters\SelectFilter::make('semester_id')
                    ->relationship('semester', 'name')
                    ->label('Filter Semester'),
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
