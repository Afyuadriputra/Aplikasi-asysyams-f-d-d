<?php

namespace App\Filament\Resources\SemesterResource\RelationManagers;

use App\Features\Academic\Models\Semester;
use App\Features\Payments\Enums\PaymentStatus;
use App\Features\Payments\Models\Payment;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Status Pembayaran SPP Siswa';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('student', 'name')
                    ->label('Siswa')
                    ->required()
                    ->searchable()
                    ->disabled(), // Disabled karena biasanya auto-generated
                
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->label('Tagihan'),

                Forms\Components\Select::make('status')
                    ->options(PaymentStatus::options())
                    ->default(PaymentStatus::Paid->value)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('order_id')
            ->columns([
                Tables\Columns\TextColumn::make('student.name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('student.nisn')
                    ->label('NISN')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Tagihan')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'paid', 'success' => 'Lunas',
                        'pending' => 'Menunggu',
                        'failed' => 'Gagal',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'paid', 'success' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->label('Status Bayar'),

                Tables\Columns\TextColumn::make('payment_type')
                    ->label('Via')
                    ->default('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(PaymentStatus::filterOptions()),
            ])
            ->headerActions([
                // FITUR GENERATE TAGIHAN MASSAL
                Tables\Actions\Action::make('generate_invoice')
                    ->label('Buat Tagihan Untuk Semua Siswa')
                    ->icon('heroicon-o-document-plus')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Tagihan SPP')
                    ->modalDescription('Sistem akan membuat data tagihan (status Pending) untuk semua siswa aktif di semester ini. Lanjutkan?')
                    ->action(function () {
                        $count = static::generateInvoicesForSemester($this->getOwnerRecord());

                        Notification::make()
                            ->title("Berhasil membuat $count tagihan baru.")
                            ->success()
                            ->send();
                    }),
                    
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Manual'),
            ])
            ->actions([
                // Action untuk ubah status jadi LUNAS (Misal bayar cash ke admin)
                Tables\Actions\Action::make('set_lunas')
                    ->label('Set Lunas')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => ! in_array($record->status, [PaymentStatus::Paid->value, 'success'], true))
                    ->requiresConfirmation()
                    ->action(fn ($record) => static::markAsPaid($record)),
                    
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function generateInvoicesForSemester(Semester $semester): int
    {
        $students = User::where('role', 'student')->where('is_active', true)->get();
        $count = 0;

        foreach ($students as $student) {
            $exists = $semester->payments()->where('user_id', $student->id)->exists();

            if (! $exists) {
                $semester->payments()->create([
                    'user_id' => $student->id,
                    'order_id' => 'INV-' . $semester->id . '-' . $student->id . '-' . time() . '-' . Str::random(4),
                    'amount' => $semester->tuition_fee,
                    'status' => PaymentStatus::Pending->value,
                ]);
                $count++;
            }
        }

        return $count;
    }

    public static function markAsPaid(Payment $payment): void
    {
        $payment->update([
            'status' => PaymentStatus::Paid->value,
            'payment_type' => 'manual_cash',
        ]);
    }

    public static function getStatusOptions(): array
    {
        return PaymentStatus::options();
    }

    public static function getStatusFilterOptions(): array
    {
        return PaymentStatus::filterOptions();
    }
}