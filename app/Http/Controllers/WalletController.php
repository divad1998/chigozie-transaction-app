<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    function create() {
        $wallet = new Wallet;
        $wallet->user_id = auth()->id();
        $wallet->save();

        return response()->json([
            'status' => true,
            'message' => 'Wallet created successfully.', 
            'data' => $wallet
        ]);
    }

    function getBalance() {
        $wallet = Wallet::where('user_id', auth()->id())
                        ->first();
        if(!$wallet) {
            return response()->json([
                'status' => false,
                'message' => 'No wallet found.'
            ]);
        } 
        return response()->json([
                'status' => true,
                'message' => 'Wallet and balance fetched.',
                'data' => $wallet
            ]);
    }
}
