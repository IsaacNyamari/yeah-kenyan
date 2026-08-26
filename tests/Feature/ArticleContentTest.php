<?php

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\ArticleHtml;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

/*
 * Article bodies are stored as sanitized HTML and rendered unescaped. Escaping
 * them printed the tags to the page as visible text, which no test caught.
 */

it('renders body markup instead of printing the tags', function () {
    $post = Post::factory()->create([
        'slug' => 'markup-post',
        'body' => '<h4>A subheading</h4><p>A paragraph with <strong>emphasis</strong>.</p>',
    ]);

    $response = $this->get(route('news.show', $post->slug))->assertOk();

    $response->assertSee('<h4>A subheading</h4>', escape: false)
        ->assertSee('<strong>emphasis</strong>', escape: false)
        ->assertDontSee('&lt;h4&gt;', escape: false);
});

it('shows the category once in the article header', function () {
    $category = Category::factory()->create(['name' => 'Politics', 'slug' => 'politics']);
    $post = Post::factory()->for($category)->create(['slug' => 'header-post']);

    $html = $this->get(route('news.show', $post->slug))->assertOk()->getContent();

    $header = substr($html, strpos($html, '<nav'), strpos($html, '<h1') - strpos($html, '<nav'));

    expect(preg_match_all('/>\s*Politics\s*</', $header))->toBe(1);
});

it('links the breadcrumb category to its filtered listing', function () {
    $category = Category::factory()->create(['slug' => 'politics']);
    $post = Post::factory()->for($category)->create(['slug' => 'breadcrumb-post']);

    $this->get(route('news.show', $post->slug))
        ->assertOk()
        ->assertSee(route('news.index', ['category' => 'politics']), escape: false);
});

it('leaves no blade attributes leaking into the page', function () {
    // A bad find-and-replace once left `title" :description="$post->excerpt">`
    // rendering as visible text at the top of the article.
    $post = Post::factory()->create(['slug' => 'clean-markup']);

    $this->get(route('news.show', $post->slug))
        ->assertOk()
        ->assertDontSee(':description=', escape: false)
        ->assertDontSee('$post->', escape: false);
});

describe('sanitizer', function () {
    beforeEach(fn () => $this->html = app(ArticleHtml::class));

    it('keeps the markup an article actually needs', function (string $tag) {
        expect($this->html->sanitize("<$tag>content</$tag>"))->toContain("<$tag>");
    })->with(['p', 'h4', 'strong', 'em', 'blockquote', 'ul', 'li']);

    it('strips scripts and their contents', function () {
        $clean = $this->html->sanitize('<p>Safe</p><script>alert("xss")</script>');

        expect($clean)->toContain('Safe')
            ->not->toContain('script')
            ->not->toContain('alert');
    });

    it('removes attributes, including event handlers and javascript urls', function () {
        $clean = $this->html->sanitize('<p onclick="steal()" class="x">Text</p>');

        expect($clean)->toBe('<p>Text</p>');
    });

    it('drops paragraphs left empty by stripped markup', function () {
        expect($this->html->sanitize('<p></p><h4>Heading</h4>'))->toBe('<h4>Heading</h4>');
    });

    it('builds a plain-text excerpt from markup', function () {
        $excerpt = $this->html->excerpt('<h4>Title</h4><p>Body <strong>text</strong> here.</p>');

        expect($excerpt)->toBe('Title Body text here.')
            ->not->toContain('<');
    });
});

it('sanitizes whatever an editor pastes into the admin', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.posts')
        ->set('title', 'Pasted Article')
        ->set('category_id', $category->id)
        ->set('body', '<p>Real copy</p><script>alert(1)</script>')
        ->set('photo', UploadedFile::fake()->image('cover.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $body = Post::firstWhere('title', 'Pasted Article')->body;

    expect($body)->toContain('Real copy')->not->toContain('script');
});
