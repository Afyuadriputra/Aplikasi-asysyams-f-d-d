<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Features\Posts\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str; // Untuk slug otomatis
use Illuminate\Support\Facades\Auth;

class PostResource extends Resource
{
    use \App\Filament\Concerns\ChecksResourcePermission;

    protected static ?string $model = Post::class;
    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'Publikasi';
    protected static ?string $navigationLabel = 'Berita & Artikel';
    protected static ?string $label = 'Berita & Artikel';
    protected static ?string $pluralLabel = 'Berita & Artikel';

    protected static function permission(): string
    {
        return 'posts.manage';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\Grid::make(2)->schema([
                        // Judul & Slug Otomatis
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Berita')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => 
                                $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true),
                    ]),

                    Forms\Components\Select::make('category')
                        ->label('Kategori')
                        ->options([
                            'Pendidikan' => 'Pendidikan',
                            'Prestasi' => 'Prestasi',
                            'Dakwah' => 'Dakwah',
                            'Sosial' => 'Sosial',
                            'Umum' => 'Umum',
                        ])
                        ->required(),

                    Forms\Components\RichEditor::make('content')
                        ->label('Isi Berita')
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\FileUpload::make('image')
                        ->label('Foto Utama')
                        ->image()
                        ->directory('posts')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('image_caption')
                        ->label('Deskripsi Foto (Caption)')
                        ->placeholder('Contoh: Santri sedang panen raya'),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\DatePicker::make('published_at')
                            ->label('Tanggal Terbit')
                            ->default(now()),
                            
                        Forms\Components\Toggle::make('is_published')
                            ->label('Terbitkan?')
                            ->default(true),
                    ]),

                    // Hidden Field: User ID otomatis diisi penulis yang login
                    Forms\Components\Hidden::make('user_id')
                        ->default(fn () => Auth::id()),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('Foto'),
                
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('category')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('author.name')
                    ->label('Penulis'),

                Tables\Columns\TextColumn::make('views')
                    ->icon('heroicon-o-eye')
                    ->label('Dilihat'),

                Tables\Columns\IconColumn::make('is_published')
                    ->boolean()
                    ->label('Aktif'),
                    
                Tables\Columns\TextColumn::make('published_at')
                    ->date()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
