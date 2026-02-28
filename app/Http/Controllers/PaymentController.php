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

    public function initiateDeposit(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:100']);

        $user = Auth::user();
        $amount = $request->amount;
        $reference = 'DEP_' . Str::random(10) . '_' . time();

        $payment = Payment::create([
            'user_id' => $user->id,
            'transaction_reference' => $reference,
            'amount' => $amount,
            'status' => 'pending',
            'description' => 'Deposit via Pesapal'
        ]);

        Log::info('Payment record created', ['payment_id' => $payment->id]);

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

            if (!isset($response['redirect_url'])) {
                throw new \Exception('No redirect URL returned: ' . json_encode($response));
            }

            Log::info('Pesapal redirect URL', ['url' => $response['redirect_url']]);
            return redirect()->away($response['redirect_url']);

        } catch (\Exception $e) {
            Log::error('Pesapal deposit error', ['message' => $e->getMessage()]);
            $payment->update(['status' => 'failed']);
            return back()->with('error', 'Pesapal error: ' . $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        $trackingId = $request->query('OrderTrackingId');
        $merchantReference = $request->query('OrderMerchantReference');

        Log::info('Callback received', [
            'tracking_id' => $trackingId,
            'merchant_ref' => $merchantReference,
            'all_params' => $request->all()
        ]);

        if (!$trackingId || !$merchantReference) {
            Log::error('Callback missing parameters', $request->all());
            return redirect()->route('wallet.index')->with('error', 'Payment callback missing tracking info.');
        }

        $payment = Payment::where('transaction_reference', $merchantReference)->first();
        if (!$payment) {
            return redirect()->route('wallet.index')->with('error', 'Payment not found.');
        }

        if ($payment->status === 'completed') {
            return redirect()->route('wallet.index')->with('info', 'Payment already processed.');
        }

        try {
            $statusData = $this->pesapal->queryPaymentStatus($merchantReference, $trackingId);
            $paymentStatus = strtoupper($statusData['status'] ?? $statusData['payment_status_description'] ?? '');

            if ($paymentStatus === 'COMPLETED') {
                DB::transaction(function () use ($payment, $trackingId) {
                    $payment->update(['status' => 'completed', 'payment_method' => 'Pesapal']);
                    $wallet = Wallet::where('user_id', $payment->user_id)->first();
                    $wallet->balance += $payment->amount;
                    $wallet->save();
                    Transaction::create([
                        'receiver_wallet_id' => $wallet->id,
                        'amount' => $payment->amount,
                        'type' => 'deposit',
                        'description' => 'Deposit via Pesapal (Ref: ' . $trackingId . ')'
                    ]);
                });

                return redirect()->route('wallet.index')->with('success', 'Payment successful! TZS ' . number_format($payment->amount,2).' added.');
            } else {
                $payment->update(['status' => 'failed']);
                return redirect()->route('wallet.index')->with('error', 'Payment not completed. Status: ' . $paymentStatus);
            }
        } catch (\Exception $e) {
            Log::error('Callback error', ['message' => $e->getMessage()]);
            return redirect()->route('wallet.index')->with('error', 'Error verifying payment: ' . $e->getMessage());
        }
    }

    public function ipn(Request $request)
    {
        $trackingId = $request->query('OrderTrackingId');
        $merchantReference = $request->query('OrderMerchantReference');

        Log::info('IPN received', [
            'tracking_id' => $trackingId,
            'merchant_ref' => $merchantReference,
            'all_params' => $request->all()
        ]);

        if (!$trackingId || !$merchantReference) {
            return response('Missing parameters', 400);
        }

        $payment = Payment::where('transaction_reference', $merchantReference)->first();
        if (!$payment) return response('Payment not found', 404);
        if ($payment->status === 'completed') return response('Already processed', 200);

        try {
            $statusData = $this->pesapal->queryPaymentStatus($merchantReference, $trackingId);
            $paymentStatus = strtoupper($statusData['status'] ?? $statusData['payment_status_description'] ?? '');

            if ($paymentStatus === 'COMPLETED') {
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
                Log::warning('IPN payment not completed', ['status' => $paymentStatus]);
            }

            return response('OK', 200);
        } catch (\Exception $e) {
            Log::error('IPN error', ['message' => $e->getMessage()]);
            return response('Error', 500);
        }
    }
}