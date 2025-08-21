<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Illuminate\Support\Str;

class TransactionTest extends TestCase
{

    /** @test */
    public function it_creates_a_credit_transaction_and_updates_wallet_balance()
    {
        $user = User::factory()->create();
        // create wallet for user
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 1000.00,
        ]);

        $payload = [
            'type' => 'credit',
            'amount' => 200.50,
            'reference' => null,
            'description' => 'Top up',
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/transactions', $payload);

        $response->assertStatus(201)
                 ->assertJson([
                     'status' => true,
                     'message' => 'Transaction successful.',
                 ]);

        // Transaction persisted
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => 200.50,
            'description' => 'Top up',
        ]);

        // Wallet updated
        $wallet->refresh();
        $this->assertEquals(1200.50, (float) $wallet->balance);
    }

    /** @test */
    public function it_prevents_duplicate_reference()
    {
        $user = User::factory()->create();
        $wallet = Wallet::create([
            'user_id' => $user->id,
            'balance' => 500.00,
        ]);

        $reference = Str::upper(Str::random(12));

        // Create existing transaction with the reference
        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'amount' => 100,
            'reference' => $reference,
            'description' => 'existing txn',
        ]);

        $payload = [
            'type' => 'credit',
            'amount' => 100,
            'reference' => $reference,
            'description' => 'attempt duplicate',
        ];

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/transactions', $payload);

        $response->assertStatus(422)
                 ->assertJson([
                     'status' => false,
                     'message' => 'Transaction already completed',
                 ]);

        // ensure only one transaction with that reference exists
        $this->assertEquals(1, Transaction::where('wallet_id', $wallet->id)->where('reference', $reference)->count());
    }
}
