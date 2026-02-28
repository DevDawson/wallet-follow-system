<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\PesapalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $pesapal;

    public function __construct(PesapalService $pesapal)
    {
        $this->pesapal = $pesapal;
    }

    /**
     * Initiate deposit via PesaPal
     */
    public function initiateDeposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100'
        ]);

        $user = Auth::user();
        $amount = $request->amount;
        $reference = 'DEP_' . Str::random(10) . '_' . time();

        // Create pending payment record
        $payment = Payment::create([
            'user_id' => $user->id,
            'transaction_reference' => $reference,
            'amount' => $amount,
            'status' => 'pending',
            'description' => 'Deposit via Pesapal'
        ]);

        try {
            $response = $this->pesapal->initiatePayment(
                $amount,
                $reference,
                'Deposit to wallet',
                explode(' ', $user->name)[0] ?? '',
                explode(' ', $user->name)[1] ?? '',
                $user->email,
                $user->phone ?? ''
            );

            // Response should contain redirect_url
            if (isset($response['redirect_url'])) {
                Log::info('PesaPal redirect URL:', ['url' => $response['redirect_url']]);
                return redirect()->away($response['redirect_url']);
            } else {
                throw new \Exception('No redirect URL returned: ' . json_encode($response));
            }

        } catch (\Exception $e) {
            Log::error('PesaPal deposit error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            $payment->update(['status' => 'failed']);

            return back()->with('error', 'PesaPal error: ' . $e->getMessage());
        }
    }

    /**
     * Handle callback after payment (user returns to site)
     */
    public function callback(Request $request)
    {
        // PesaPal sends parameters via GET (query string)
        $trackingId = $request->query('OrderTrackingId');
        $merchantReference = $request->query('OrderMerchantReference');

        if (!$trackingId || !$merchantReference) {
            Log::error('PesaPal callback missing parameters', $request->all());
            return redirect()->route('wallet.index')->with('error', 'Payment callback missing tracking info.');
        }

        Log::info('PesaPal callback received', [
            'tracking_id' => $trackingId,
            'merchant_ref' => $merchantReference
        ]);

        // Find payment record
        $payment = Payment::where('transaction_reference', $merchantReference)->first();

        if (!$payment) {
            Log::error('Payment not found for reference: ' . $merchantReference);
            return redirect()->route('wallet.index')->with('error', 'Payment record not found.');
        }

        // If already completed, skip
        if ($payment->status === 'completed') {
            return redirect()->route('wallet.index')->with('info', 'Payment already processed.');
        }

        try {
            // Query payment status from PesaPal
            $statusData = $this->pesapal->queryPaymentStatus($merchantReference, $trackingId);

            Log::info('PesaPal status response', $statusData);

            $paymentStatus = $statusData['status'] ?? $statusData['payment_status_description'] ?? '';

            if (strtoupper($paymentStatus) === 'COMPLETED') {
                DB::transaction(function () use ($payment, $trackingId) {
                    // Update payment record
                    $payment->update([
                        'status' => 'completed',
                        'payment_method' => 'Pesapal'
                    ]);

                    // Add funds to wallet
                    $wallet = Wallet::where('user_id', $payment->user_id)->first();
                    $wallet->balance += $payment->amount;
                    $wallet->save();

                    // Create transaction record
                    Transaction::create([
                        'receiver_wallet_id' => $wallet->id,
                        'amount' => $payment->amount,
                        'type' => 'deposit',
                        'description' => 'Deposit via Pesapal (Ref: ' . $trackingId . ')'
                    ]);
                });

                return redirect()->route('wallet.index')->with('success', 'Payment successful! TZS ' . number_format($payment->amount, 2) . ' added to your wallet.');
            } else {
                $payment->update(['status' => 'failed']);
                return redirect()->route('wallet.index')->with('error', 'Payment not completed. Status: ' . $paymentStatus);
            }
        } catch (\Exception $e) {
            Log::error('PesaPal callback error: ' . $e->getMessage());
            return redirect()->route('wallet.index')->with('error', 'Error verifying payment: ' . $e->getMessage());
        }
    }

    /**
     * Handle IPN (Instant Payment Notification) from PesaPal
     */
    public function ipn(Request $request)
    {
        // PesaPal sends IPN as GET request with query parameters
        $trackingId = $request->query('OrderTrackingId');
        $merchantReference = $request->query('OrderMerchantReference');

        Log::info('PesaPal IPN received', [
            'tracking_id' => $trackingId,
            'merchant_ref' => $merchantReference,
            'all_params' => $request->all()
        ]);

        if (!$trackingId || !$merchantReference) {
            return response('Missing parameters', 400);
        }

        $payment = Payment::where('transaction_reference', $merchantReference)->first();

        if (!$payment) {
            Log::error('IPN: Payment not found for reference: ' . $merchantReference);
            return response('Payment not found', 404);
        }

        // Avoid double processing
        if ($payment->status === 'completed') {
            return response('Already processed', 200);
        }

        try {
            $statusData = $this->pesapal->queryPaymentStatus($merchantReference, $trackingId);
            $paymentStatus = $statusData['status'] ?? $statusData['payment_status_description'] ?? '';

            if (strtoupper($paymentStatus) === 'COMPLETED') {
                DB::transaction(function () use ($payment, $trackingId) {
                    $payment->update(['status' => 'completed']);

                    $wallet = Wallet::where('user_id', $payment->user_id)->first();
                    $wallet->balance += $payment->amount;
                    $wallet->save();

                    Transaction::create([
                        'receiver_wallet_id' => $wallet->id,
                        'amount' => $payment->amount,
                        'type' => 'deposit',
                        'description' => 'Deposit via Pesapal IPN'
                    ]);
                });

                Log::info('IPN processed successfully for payment ' . $payment->id);
            } else {
                $payment->update(['status' => 'failed']);
                Log::warning('IPN: Payment not completed', ['status' => $paymentStatus]);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('IPN error: ' . $e->getMessage());
            return response('Error', 500);
        }
    }
}