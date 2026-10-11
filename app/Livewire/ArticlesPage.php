<?php

namespace App\Livewire;

use App\Enums\ArticleStatus;
use App\Models\Article;
use App\Models\Site;
use App\Services\Sites\ArticleService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Artikel')]
class ArticlesPage extends Component
{
    use WithPagination;

    #[Url]
    public ?int $site = null;

    public ?int $editingId = null;

    public array $edit = [];

    public ?string $flash = null;

    public ?string $flashType = null;

    public function updatedSite(): void
    {
        $this->resetPage();
    }

    public function startEdit(int $id): void
    {
        $article = Article::query()->findOrFail($id);
        $this->editingId = $article->id;
        $this->edit = $article->only(['title', 'meta_description', 'summary', 'body_html']);
        $this->resetErrorBag();
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function save(ArticleService $articles): void
    {
        $data = $this->validate([
            'edit.title' => 'required|string|max:120',
            'edit.meta_description' => 'required|string|max:160',
            'edit.summary' => 'required|string|max:400',
            'edit.body_html' => 'required|string',
        ], ['required' => 'Wajib diisi.'])['edit'];

        $this->attempt(function () use ($articles, $data) {
            $articles->update(Article::query()->findOrFail($this->editingId), $data);
            $this->editingId = null;
        }, 'Artikel dikemas kini di website.');
    }

    public function delete(ArticleService $articles, int $id): void
    {
        $this->attempt(fn () => $articles->delete(Article::query()->findOrFail($id)), 'Artikel dipadam dari website.');
    }

    public function publish(ArticleService $articles, int $id): void
    {
        $this->attempt(fn () => $articles->publish(Article::query()->findOrFail($id)), 'Artikel diterbitkan.');
    }

    public function regenerateImage(ArticleService $articles, int $id): void
    {
        $this->attempt(function () use ($articles, $id) {
            if (! $articles->regenerateImage(Article::query()->findOrFail($id))) {
                throw new \RuntimeException('Gambar tidak dapat dijana. Cuba lagi nanti.');
            }
        }, 'Gambar baru dijana.');
    }

    private function attempt(callable $action, string $success): void
    {
        try {
            $action();
            $this->flash = $success;
            $this->flashType = 'success';
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);
            $this->flash = $e->getMessage();
            $this->flashType = 'danger';
        }
    }

    public function render()
    {
        return view('livewire.articles-page', [
            'sites' => Site::query()->orderBy('name')->get(['id', 'name']),
            'articles' => Article::query()->with('site')
                ->where('status', '!=', ArticleStatus::Deleted)
                ->when($this->site, fn ($q) => $q->where('site_id', $this->site))
                ->orderByRaw('published_at IS NULL DESC')
                ->latest('published_at')->latest('id')
                ->paginate(15),
        ]);
    }
}
