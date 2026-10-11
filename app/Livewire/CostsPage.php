<?php

namespace App\Livewire;

use App\Services\Costs\CostReport;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Kos')]
class CostsPage extends Component
{
    public function render(CostReport $report)
    {
        return view('livewire.costs-page', ['m' => $report->month()]);
    }
}
