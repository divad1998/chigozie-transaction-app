<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    function store(Request $req){
        $user = auth()->user();
        // Important: use DB::transaction + lockForUpdate to prevent races
        return DB::transaction(function () use ($user, $req) {
            // Lock the wallet row for this user
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->firstOrFail();

            // If reference is provided and a transaction already exists, return it
            $ref = $req->reference;
            if ($ref) {
                $existing = Transaction::where('wallet_id', $wallet->id)
                                    ->where('reference', $ref)
                                    ->first();
                if ($existing) {
                    return response()->json([
                        'status' => false, 'message' => 'Transaction already completed'], 422);
                }
            }

            //
            $type = $req->type;
            $amount = $req->amount;
            $amount = \Illuminate\Support\Str::startsWith($type, 'debit') ? -abs($amount) : abs($amount);

            // Validate funds for debit
            if ($type === 'debit' && $wallet->balance < $amount) {
                return response()->json([
                    'status' => false,
                    'message' => 'Insufficient funds'], 422);
            }

            // Create transaction record
            $txn = Transaction::create([
                'wallet_id' => $wallet->id,
                'type' => $req->type,
                'amount' => $req->amount,
                'reference' => $ref,
                'description' => $req->description,
            ]);

            // remember wallet is locked
            $wallet->balance = $wallet->balance + ($req->type === 'debit' ? -$req->amount : $req->amount);
            $wallet->save();

            return response()->json([
                'status' => true,
                'message' => 'Transaction successful.',
                'transaction' => $txn, 
                'balance' => $wallet->balance], 201);
        });
    }
}
