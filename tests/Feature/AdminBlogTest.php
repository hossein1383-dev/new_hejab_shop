<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBlogTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(): User
    {
        $role = Role::create(['name' => 'Admin', 'slug' => 'admin']);
        $permission = Permission::firstOrCreate(['slug' => 'settings.manage'], ['name' => 'Manage Settings', 'group' => 'Settings']);
        $role->permissions()->attach($permission);
        $user = User::factory()->create();
        $user->roles()->attach($role);

        return $user;
    }

    public function test_staff_can_create_and_publish_blog_post(): void
    {
        $this->withoutVite();
        $staff = $this->staffUser();

        $this->actingAs($staff)->post('/admin/blog', [
            'title' => 'مطلب تست',
            'slug' => 'test-post',
            'content' => 'محتوای تست.',
            'status' => 'published',
        ])->assertRedirect(route('admin.blog.index'));

        $this->assertDatabaseHas('blog_posts', ['slug' => 'test-post', 'status' => 'published']);
        $post = BlogPost::where('slug', 'test-post')->first();
        $this->assertNotNull($post->published_at);
    }

    public function test_public_can_view_published_post_but_not_draft(): void
    {
        $this->withoutVite();
        BlogPost::create(['title' => 'منتشرشده', 'slug' => 'published-post', 'content' => 'محتوا', 'status' => 'published', 'published_at' => now()]);
        BlogPost::create(['title' => 'پیش‌نویس', 'slug' => 'draft-post', 'content' => 'محتوا', 'status' => 'draft']);

        $this->get('/blog/published-post')->assertOk()->assertSee('منتشرشده');
        $this->get('/blog/draft-post')->assertStatus(404);
    }

    public function test_blog_index_lists_only_published_posts(): void
    {
        $this->withoutVite();
        BlogPost::create(['title' => 'مطلب فهرست تست', 'slug' => 'list-test', 'content' => 'x', 'status' => 'published', 'published_at' => now()]);
        BlogPost::create(['title' => 'مطلب مخفی', 'slug' => 'hidden-test', 'content' => 'x', 'status' => 'draft']);

        $response = $this->get('/blog');

        $response->assertOk()->assertSee('مطلب فهرست تست')->assertDontSee('مطلب مخفی');
    }
}
