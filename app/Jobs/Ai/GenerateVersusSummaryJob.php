<?php

namespace App\Jobs\Ai;

use App\Models\ShopifyApp;
use App\Services\Ai\VersusSummaryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateVersusSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $accountId,
        public readonly int $mineAppId,
        public readonly array $competitorIds,
        public readonly array $payload,
    ) {
    }

    public function handle(VersusSummaryService $service): void
    {
        $mine = ShopifyApp::findOrFail($this->mineAppId);
        $competitors = ShopifyApp::whereIn('id', $this->competitorIds)->get();
        $service->generate($this->accountId, $mine, $competitors, $this->payload);
    }
}
