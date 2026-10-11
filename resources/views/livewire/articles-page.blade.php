<div class="space-y-4">
    <x-page-header title="Artikel" subtitle="Semua artikel, terbaru di atas." />

    @if ($flash)
        <x-alert :type="$flashType">{{ $flash }}</x-alert>
    @endif

    <select wire:model.live="site" class="input">
        <option value="">Semua website</option>
        @foreach ($sites as $s)
            <option value="{{ $s->id }}">{{ $s->name }}</option>
        @endforeach
    </select>

    @forelse ($articles as $article)
        <div class="card overflow-hidden" wire:key="a-{{ $article->id }}">
            @if ($article->image_path && $article->status === \App\Enums\ArticleStatus::Published)
                <img src="{{ $article->site->baseUrl() }}/{{ $article->image_path }}" alt="" class="aspect-video w-full object-cover" loading="lazy">
            @endif
            <div class="p-4">
                <div class="flex items-start justify-between gap-3">
                    <p class="font-semibold leading-snug">{{ $article->title ?? 'Tanpa tajuk' }}</p>
                    <span class="chip shrink-0 ring-1 ring-inset {{ $article->status->color() }}">{{ $article->status->label() }}</span>
                </div>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $article->site->name }}
                    @if ($article->published_at) · {{ \App\Services\Publishing\BlogRenderer::dateMs($article->published_at) }} @endif
                    @if ($article->word_count) · {{ $article->word_count }} perkataan @endif
                </p>

                @if ($article->status === \App\Enums\ArticleStatus::Failed)
                    <div class="mt-2 rounded-xl bg-rose-50 p-2.5 text-xs text-rose-800">
                        {{ $article->error }}
                        @foreach ((array) $article->check_notes as $note)
                            <span class="block">• {{ $note }}</span>
                        @endforeach
                    </div>
                @endif

                @if ($editingId === $article->id)
                    <form wire:submit="save" class="mt-3 space-y-3">
                        <x-field label="Tajuk" name="edit.title"><input type="text" wire:model="edit.title" class="input"></x-field>
                        <x-field label="Meta description" name="edit.meta_description"><textarea wire:model="edit.meta_description" rows="2" class="input"></textarea></x-field>
                        <x-field label="Ringkasan kad" name="edit.summary"><textarea wire:model="edit.summary" rows="2" class="input"></textarea></x-field>
                        <x-field label="Isi (HTML)" name="edit.body_html" hint="Tag dibenarkan: h2, h3, p, ul, ol, li, strong, em, a (/blog/...), blockquote, table."><textarea wire:model="edit.body_html" rows="12" class="input font-mono text-xs"></textarea></x-field>
                        <div class="flex gap-2">
                            <button type="submit" class="btn-primary flex-1" wire:loading.attr="disabled">Simpan & terbit</button>
                            <button type="button" wire:click="cancelEdit" class="btn-soft">Batal</button>
                        </div>
                    </form>
                @else
                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($article->status === \App\Enums\ArticleStatus::Published)
                            <a href="{{ $article->url() }}" target="_blank" rel="noopener" class="btn-soft px-3 py-2">Lihat</a>
                        @endif
                        @if (in_array($article->status, [\App\Enums\ArticleStatus::Published, \App\Enums\ArticleStatus::Ready]))
                            <button wire:click="startEdit({{ $article->id }})" class="btn-soft px-3 py-2"><x-icon name="pencil" class="h-4 w-4" /> Edit</button>
                            <button wire:click="regenerateImage({{ $article->id }})" wire:confirm="Jana gambar baru (kos satu gambar AI)?" class="btn-soft px-3 py-2" wire:loading.attr="disabled"><x-icon name="refresh" class="h-4 w-4" /> Gambar baru</button>
                        @endif
                        @if ($article->status === \App\Enums\ArticleStatus::Ready)
                            <button wire:click="publish({{ $article->id }})" class="btn-primary px-3 py-2" wire:loading.attr="disabled">Terbit sekarang</button>
                        @endif
                        @if ($article->status !== \App\Enums\ArticleStatus::Writing)
                            <button wire:click="delete({{ $article->id }})" wire:confirm="Padam artikel ini dari website?" class="btn-soft px-3 py-2 text-rose-600"><x-icon name="trash" class="h-4 w-4" /> Padam</button>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @empty
        <x-empty icon="note" title="Belum ada artikel">Aktifkan website, dan artikel pertama akan ditulis automatik.</x-empty>
    @endforelse

    {{ $articles->links() }}
</div>
