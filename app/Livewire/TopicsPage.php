<?php

namespace App\Livewire;

use App\Models\Site;
use App\Services\Content\TopicPlanner;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('components.layouts.app')]
#[Title('Topik')]
class TopicsPage extends Component
{
    public Site $site;

    public string $title = '';

    public string $keyword = '';

    public ?string $flash = null;

    public ?string $flashType = null;

    public function mount(int $siteId): void
    {
        // Scoped to the current workspace: another customer's website is a 404.
        $this->site = Site::query()->findOrFail($siteId);
    }

    public function add(TopicPlanner $planner): void
    {
        $this->validate(['title' => 'required|string|max:180', 'keyword' => 'nullable|string|max:180'], ['title.required' => 'Isi tajuk topik.']);

        $topic = $planner->add($this->site, $this->title, $this->keyword ?: $this->title, null, null, 'manual');

        if ($topic === null) {
            $this->say('Topik ini sama dengan topik atau artikel sedia ada, atau menyentuh topik dilarang.', 'warning');

            return;
        }

        // Manual topics go first.
        $topic->forceFill(['position' => (int) $this->site->topics()->where('status', 'planned')->min('position') - 1])->save();
        $this->reset('title', 'keyword');
        $this->say('Topik ditambah di atas senarai.');
    }

    public function plan(TopicPlanner $planner): void
    {
        try {
            $added = $planner->plan($this->site);
            $this->say("{$added} topik baru dijana.");
        } catch (Throwable $e) {
            report($e);
            $this->say($e->getMessage(), 'danger');
        }
    }

    public function moveUp(int $id): void
    {
        $topic = $this->site->topics()->findOrFail($id);
        $topic->forceFill(['position' => (int) $this->site->topics()->where('status', 'planned')->min('position') - 1])->save();
    }

    public function skip(int $id): void
    {
        $this->site->topics()->whereKey($id)->update(['status' => 'skipped']);
    }

    private function say(string $message, string $type = 'success'): void
    {
        $this->flash = $message;
        $this->flashType = $type;
    }

    public function render()
    {
        return view('livewire.topics-page', [
            'planned' => $this->site->topics()->where('status', 'planned')->orderBy('position')->orderBy('id')->get(),
            'used' => $this->site->topics()->where('status', '!=', 'planned')->latest('updated_at')->take(30)->get(),
        ]);
    }
}
