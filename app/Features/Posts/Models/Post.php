<?php

namespace App\Features\Posts\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'category',
        'content',
        'image',
        'image_caption',
        'published_at',
        'is_published',
        'views',
    ];

    protected $casts = [
        'published_at' => 'date',
        'is_published' => 'boolean',
    ];

    // Relasi: Berita ditulis oleh User
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
