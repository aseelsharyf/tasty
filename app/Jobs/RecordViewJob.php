<?php

namespace App\Jobs;

use App\Models\PostView;
use App\Models\ProductView;
use App\Services\AnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordViewJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{type: string, model_id: int, user_id: int|null, ip_address: string|null, user_agent: string|null, referrer: string|null, session_id: string|null}  $data
     */
    public function __construct(
        public array $data
    ) {}

    public function handle(): void
    {
        $result = match ($this->data['type']) {
            'post' => PostView::record([
                'post_id' => $this->data['model_id'],
                'user_id' => $this->data['user_id'],
                'ip_address' => $this->data['ip_address'],
                'user_agent' => $this->data['user_agent'],
                'referrer' => $this->data['referrer'],
                'session_id' => $this->data['session_id'],
            ]),
            'product' => ProductView::record([
                'product_id' => $this->data['model_id'],
                'user_id' => $this->data['user_id'],
                'ip_address' => $this->data['ip_address'],
                'user_agent' => $this->data['user_agent'],
                'referrer' => $this->data['referrer'],
                'session_id' => $this->data['session_id'],
            ]),
            default => null,
        };

        // Only flush cache if a new view was actually recorded (not deduplicated)
        if ($result !== null) {
            match ($this->data['type']) {
                'post' => AnalyticsService::flushArticleCache(),
                'product' => AnalyticsService::flushProductCache(),
                default => null,
            };
        }
    }
}
