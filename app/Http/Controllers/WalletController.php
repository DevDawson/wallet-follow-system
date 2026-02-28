<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    // Onyesha salio na historia
    public function index()
    {
        $user = Auth::user();
        $wallet = $user->wallet;

        // Historia ya transactions (tuma na pokea)
        $transactions = Transaction::where('sender_wallet_id', $wallet->id)
                        ->orWhere('receiver_wallet_id', $wallet->id)
                        ->with(['senderWallet.user', 'receiverWallet.user'])
                        ->latest()
                        ->get();

        return view('wallet.index', compact('wallet', 'transactions'));
    }

    // Onyesha form ya kuongeza pesa (deposit)
    public function showDepositForm()
    {
        return view('wallet.deposit');
    }

    // Hifadhi deposit
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01'
        ]);

        $user = Auth::user();
        $wallet = $user->wallet;

        DB::transaction(function () use ($wallet, $request) {
            // Ongeza salio
            $wallet->balance += $request->amount;
            $wallet->save();

            // Rekodi transaction
            Transaction::create([
                'receiver_wallet_id' => $wallet->id,
                'amount' => $request->amount,
                'type' => 'deposit',
                'description' => 'Deposit through system'
            ]);
        });

        return redirect()->route('wallet.index')->with('success', 'Deposit imefanikiwa.');
    }

    // Onyesha form ya kuhamisha pesa
    public function showTransferForm()
    {
        $users = User::where('id', '!=', Auth::id())->get(); // watumiaji wengine
        return view('wallet.transfer', compact('users'));
    }

    // Hifadhi transfer
    public function transfer(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01'
        ]);

        $sender = Auth::user();
        $senderWallet = $sender->wallet;
        $receiver = User::findOrFail($request->receiver_id);
        $receiverWallet = $receiver->wallet;

        // Hakikisha sender ana salio la kutosha
        if ($senderWallet->balance < $request->amount) {
            return back()->withErrors(['amount' => 'Salio lako halitoshi.']);
        }

        // Usijiruhusu kutuma kwako mwenyewe
        if ($sender->id == $receiver->id) {
            return back()->withErrors(['receiver_id' => 'Huwezi kujihamishia pesa.']);
        }

        DB::transaction(function () use ($senderWallet, $receiverWallet, $request) {
            // Toa kwa mtumaji
            $senderWallet->balance -= $request->amount;
            $senderWallet->save();

            // Ongeza kwa mpokeaji
            $receiverWallet->balance += $request->amount;
            $receiverWallet->save();

            // Rekodi transaction
            Transaction::create([
                'sender_wallet_id' => $senderWallet->id,
                'receiver_wallet_id' => $receiverWallet->id,
                'amount' => $request->amount,
                'type' => 'transfer',
                'description' => "Transfer to {$receiverWallet->user->name}"
            ]);
        });

        return redirect()->route('wallet.index')->with('success', 'Uhamisho umefanikiwa.');
    }
}