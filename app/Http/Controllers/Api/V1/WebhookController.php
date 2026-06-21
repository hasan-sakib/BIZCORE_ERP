<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends BaseApiController
{
    public function bkash(Request $request): JsonResponse
    {
        Log::channel('stack')->info('bKash webhook received', $request->all());

        $paymentId = $request->input('paymentID');
        $status    = $request->input('transactionStatus');

        if ($status !== 'Completed') {
            return $this->success(['message' => 'Acknowledged.']);
        }

        Log::info("bKash payment completed: {$paymentId}");

        return $this->success(['message' => 'Processed.']);
    }

    public function nagad(Request $request): JsonResponse
    {
        Log::channel('stack')->info('Nagad webhook received', $request->all());

        $orderId = $request->input('order_id');
        $status  = $request->input('status');

        if ($status !== 'Success') {
            return $this->success(['message' => 'Acknowledged.']);
        }

        Log::info("Nagad payment success for order: {$orderId}");

        return $this->success(['message' => 'Processed.']);
    }
}
