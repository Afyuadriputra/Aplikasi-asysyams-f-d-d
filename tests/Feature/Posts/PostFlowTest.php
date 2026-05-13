<?php

namespace Tests\Feature\Posts;

use App\Models\User;
use App\Features\Posts\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_access_post_detail_by_slug()
    {
        $author = User::factory()->create();
        $post = Post::create([
            'title' => 'Berita Baru',
            'slug' => 'berita-baru',
            'content' => 'Konten berita',
            'user_id' => $author->id,
            'is_published' => true,
            'published_at' => now()
        ]);

        $response = $this->get(route('post.show', ['slug' => 'berita-baru']));

        $response->assertStatus(200);
        $response->assertSee('Berita Baru');
        $response->assertSee('Konten berita');
    }

    public function test_cannot_access_unpublished_post()
    {
        $author = User::factory()->create();
        $post = Post::create([
            'title' => 'Draft Post',
            'slug' => 'draft-post',
            'content' => 'Konten draft',
            'user_id' => $author->id,
            'is_published' => false,
        ]);

        $response = $this->get(route('post.show', ['slug' => 'draft-post']));
        $response->assertStatus(404);
    }

    public function test_post_has_author_relation()
    {
        $author = User::factory()->create(['name' => 'Penulis A']);
        $post = Post::create([
            'title' => 'T',
            'slug' => 't',
            'content' => 'C',
            'user_id' => $author->id,
            'is_published' => true
        ]);

        $this->assertEquals('Penulis A', $post->author->name);
    }
}
