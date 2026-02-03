<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Notifies the agent-service webhook when a provider is added/registered,
 * so buyer match can be triggered for relevant jobs.
 */
class AgentWebhookService
{
    /**
     * Call the provider-registered webhook. No-op if AGENT_SERVICE_WEBHOOK_URL is not set.
     *
     * @param int $providerId
     * @param int|null $serviceCategoryId
     * @param int|null $subCategoryId
     * @param float|null $lat
     * @param float|null $long
     * @return void
     */
    public static function notifyProviderRegistered(
        int $providerId,
        ?int $serviceCategoryId = null,
        ?int $subCategoryId = null,
        ?float $lat = null,
        ?float $long = null
    ): void {
        $url = config('services.agent_webhook.url');
        if (empty($url)) {
            return;
        }

        $payload = [
            'provider_id' => $providerId,
            'event' => 'provider_registered',
        ];
        if ($serviceCategoryId !== null) {
            $payload['service_category_id'] = $serviceCategoryId;
        }
        if ($subCategoryId !== null) {
            $payload['sub_category_id'] = $subCategoryId;
        }
        if ($lat !== null) {
            $payload['lat'] = $lat;
        }
        if ($long !== null) {
            $payload['long'] = $long;
        }

        $headers = ['Content-Type' => 'application/json'];
        $secret = config('services.agent_webhook.secret');
        if (!empty($secret)) {
            $headers['X-Webhook-Secret'] = $secret;
        }

        try {
            $response = Http::timeout(5)->withHeaders($headers)->post(rtrim($url, '/') . '/webhook/provider-registered', $payload);
            if (!$response->successful()) {
                Log::warning('Agent webhook provider-registered failed', [
                    'provider_id' => $providerId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Agent webhook provider-registered error', [
                'provider_id' => $providerId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
