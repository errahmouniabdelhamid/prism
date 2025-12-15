<?php

declare(strict_types=1);

namespace Prism\Prism\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Prism\Prism\Models\PrismUsage;

class ObservabilityController
{
    public function __invoke(): View
    {
        $usages = PrismUsage::latest()
            ->take(50)
            ->get();

        $stats = [
            'total_requests' => PrismUsage::count(),
            'prompt_tokens' => (int) PrismUsage::sum('prompt_tokens'),
            'completion_tokens' => (int) PrismUsage::sum('completion_tokens'),
        ];

        $topModels = PrismUsage::select('model', DB::raw('count(*) as total'))
            ->groupBy('model')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return view('prism::observability.dashboard', [
            'usages' => $usages,
            'stats' => $stats,
            'topModels' => $topModels,
        ]);
    }
}
