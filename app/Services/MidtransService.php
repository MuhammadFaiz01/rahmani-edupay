<?php

namespace App\Services;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use Illuminate\Support\Str;

class MidtransService
{
    public function __construct()
    {
        // Set Midtrans configuration
        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = config('services.midtrans.is_production', false);
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;
    }

    /**
     * Create Snap token for payment
     */
    public function createSnapToken(Tagihan $tagihan, $customerId, $customerName, $customerEmail)
    {
        $orderId = 'ORDER-' . $tagihan->id_tagihan . '-' . time();
        
        // Create pembayaran record
        $pembayaran = Pembayaran::create([
            'id_tagihan' => $tagihan->id_tagihan,
            'midtrans_order_id' => $orderId,
            'jml_dibayar' => $tagihan->jumlah_tagihan,
            'status_pembayaran' => 'pending',
        ]);

        $params = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $tagihan->jumlah_tagihan,
            ],
            'item_details' => [
                [
                    'id' => $tagihan->id_tagihan,
                    'price' => (int) $tagihan->jumlah_tagihan,
                    'quantity' => 1,
                    'name' => $tagihan->nama_tagihan,
                    'brand' => 'Rahmani EduPay',
                    'category' => 'Education',
                ]
            ],
            'customer_details' => [
                'first_name' => $customerName,
                'email' => $customerEmail,
                'customer_id' => $customerId,
            ],
            'enabled_payments' => [
                'credit_card', 'bca_va', 'bni_va', 'bri_va', 'echannel', 'permata_va',
                'other_va', 'gopay', 'shopeepay', 'qris'
            ],
            'vtweb' => [],
            'callbacks' => [
                'finish' => route('payment.finish'),
            ]
        ];

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($params);
            return [
                'snap_token' => $snapToken,
                'pembayaran_id' => $pembayaran->id_pembayaran,
                'order_id' => $orderId
            ];
        } catch (\Exception $e) {
            // Delete the pembayaran record if token creation fails
            $pembayaran->delete();
            throw $e;
        }
    }

    /**
     * Handle Midtrans notification/webhook
     */
    public function handleNotification($notification)
    {
        $orderId = $notification->order_id;
        $statusCode = $notification->status_code;
        $grossAmount = $notification->gross_amount;
        $transactionStatus = $notification->transaction_status;
        $paymentType = $notification->payment_type;
        $transactionId = $notification->transaction_id;
        $fraudStatus = isset($notification->fraud_status) ? $notification->fraud_status : null;

        // Find pembayaran by order_id
        $pembayaran = Pembayaran::where('midtrans_order_id', $orderId)->first();
        
        if (!$pembayaran) {
            return ['status' => 'error', 'message' => 'Payment not found'];
        }

        // Update pembayaran with transaction details
        $pembayaran->midtrans_trx_id = $transactionId;
        $pembayaran->metode_pembayaran = $paymentType;

        if ($transactionStatus == 'capture') {
            if ($fraudStatus == 'challenge') {
                $pembayaran->status_pembayaran = 'pending';
            } else if ($fraudStatus == 'accept') {
                $pembayaran->status_pembayaran = 'success';
                $pembayaran->tgl_pembayaran = now();
                $this->updateTagihanStatus($pembayaran->tagihan);
            }
        } else if ($transactionStatus == 'settlement') {
            $pembayaran->status_pembayaran = 'success';
            $pembayaran->tgl_pembayaran = now();
            $this->updateTagihanStatus($pembayaran->tagihan);
        } else if ($transactionStatus == 'pending') {
            $pembayaran->status_pembayaran = 'pending';
        } else if ($transactionStatus == 'deny') {
            $pembayaran->status_pembayaran = 'failed';
        } else if ($transactionStatus == 'expire') {
            $pembayaran->status_pembayaran = 'failed';
        } else if ($transactionStatus == 'cancel') {
            $pembayaran->status_pembayaran = 'failed';
        }

        $pembayaran->save();

        return ['status' => 'success', 'message' => 'Payment status updated'];
    }

    /**
     * Update tagihan status based on payment
     */
    private function updateTagihanStatus(Tagihan $tagihan)
    {
        $totalPaid = $tagihan->pembayaranSuccess()->sum('jml_dibayar');
        
        if ($totalPaid >= $tagihan->jumlah_tagihan) {
            $tagihan->status_tagihan = 'paid';
            $tagihan->save();
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus($orderId)
    {
        try {
            $status = \Midtrans\Transaction::status($orderId);
            return $status;
        } catch (\Exception $e) {
            return null;
        }
    }
}