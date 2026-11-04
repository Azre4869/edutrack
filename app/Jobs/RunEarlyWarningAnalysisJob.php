<?php

namespace App\Jobs;

use App\Models\AnalisisLog;
use App\Services\EarlyWarning\EarlyWarningService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunEarlyWarningAnalysisJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public function __construct(
        public readonly int $semesterId,
        public readonly ?int $kelasId = null,
        public readonly string $pemicu = 'Manual',
        public readonly ?int $userId = null
    ) {}

    public function handle(EarlyWarningService $service): AnalisisLog
    {
        return $service->runAnalysis(
            semesterId: $this->semesterId,
            kelasId: $this->kelasId,
            pemicu: $this->pemicu,
            userId: $this->userId
        );
    }
}
