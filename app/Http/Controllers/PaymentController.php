<?php

namespace App\Http\Controllers;

use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    /**
     * Handle Midtrans webhook notification
     */
    public function webhook(Request $request)
    {
        try {
            // Get notification data
            $notification = json_decode($request->getContent());
            
            // Log the notification for debugging
            Log::info('Midtrans Webhook Received', [
                'notification' => $notification
            ]);
            
            // Verify signature
            $serverKey = config('services.midtrans.server_key');
            $orderId = $notification->order_id;
            $statusCode = $notification->status_code;
            $grossAmount = $notification->gross_amount;
            
            $signatureKey = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
            
            if ($signatureKey !== $notification->signature_key) {
                Log::error('Invalid Midtrans signature', [
                    'expected' => $signatureKey,
                    'received' => $notification->signature_key
                ]);
                return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
            }
            
            // Handle the notification
            $result = $this->midtransService->handleNotification($notification);
            
            Log::info('Midtrans Webhook Processed', [
                'order_id' => $orderId,
                'result' => $result
            ]);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Midtrans Webhook Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Payment finish page
     */
    public function finish(Request $request)
    {
        $orderId = $request->get('order_id');
        $statusCode = $request->get('status_code');
        $transactionStatus = $request->get('transaction_status');
        
        // Redirect to appropriate controller based on user role
        if (auth()->check()) {
            if (auth()->user()->isOrtu()) {
                return app(\App\Http\Controllers\Ortu\TagihanController::class)
                    ->paymentFinish($request);
            }
        }
        
        return redirect()->route('login')
            ->with('info', 'Silakan login untuk melihat status pembayaran.');
    }

    /**
     * Payment unfinish page
     */
    public function unfinish(Request $request)
    {
        return redirect()->route('ortu.tagihan.index')
            ->with('warning', 'Pembayaran belum selesai. Silakan coba lagi.');
    }

    /**
     * Payment error page
     */
    public function error(Request $request)
    {
        return redirect()->route('ortu.tagihan.index')
            ->with('error', 'Terjadi kesalahan dalam proses pembayaran.');
    }
}