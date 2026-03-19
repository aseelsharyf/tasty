<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Payment\BmlGatewayService;
use BmlConnect\BmlConnect;
use BmlConnect\DTOs\Transaction;
use BmlConnect\DTOs\WebhookEvent;
use BmlConnect\Exceptions\AuthenticationException;
use BmlConnect\Resources\TransactionResource;
use BmlConnect\Resources\WebhookResource;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->order = Order::factory()->accepted()->create([
        'total' => 150.00,
        'currency' => 'MVR',
    ]);
});

function mockBmlWithTransactions(TransactionResource $transactions): void
{
    $mock = Mockery::mock(BmlConnect::class)->makePartial();
    $mock->shouldReceive('__get')->with('transactions')->andReturn($transactions);

    // Use reflection to set the readonly property
    $ref = new ReflectionClass($mock);

    app()->instance(BmlConnect::class, $mock);
}

function mockBmlWithWebhooks(WebhookResource $webhooks): void
{
    $mock = Mockery::mock(BmlConnect::class)->makePartial();
    $mock->shouldReceive('__get')->with('webhooks')->andReturn($webhooks);

    app()->instance(BmlConnect::class, $mock);
}

it('initiates a BML payment and returns redirect URL', function () {
    $mockTransaction = Transaction::fromArray([
        'id' => 'txn_test_123',
        'amount' => 15000,
        'currency' => 'MVR',
        'state' => 'INITIATED',
        'url' => 'https://pay.merchants.bankofmaldives.com.mv/txn_test_123',
        'localId' => $this->order->order_number,
    ]);

    $mockTransactions = mock(TransactionResource::class);
    $mockTransactions->shouldReceive('create')->once()->andReturn($mockTransaction);

    $fake = new class($mockTransactions)
    {
        public readonly TransactionResource $transactions;

        public function __construct(TransactionResource $transactions)
        {
            $this->transactions = $transactions;
        }
    };
    app()->instance(BmlConnect::class, $fake);

    $service = new BmlGatewayService;
    $result = $service->initiate($this->order);

    expect($result->success)->toBeTrue()
        ->and($result->redirectUrl)->toBe('https://pay.merchants.bankofmaldives.com.mv/txn_test_123')
        ->and($result->transactionId)->toBe('txn_test_123');

    $this->order->refresh();
    expect($this->order->payment_method)->toBe(PaymentMethod::BmlGateway)
        ->and($this->order->status)->toBe(OrderStatus::PaymentPending)
        ->and($this->order->metadata['bml_transaction_id'])->toBe('txn_test_123');
});

it('handles initiation failure gracefully', function () {
    $mockTransactions = mock(TransactionResource::class);
    $mockTransactions->shouldReceive('create')->once()->andThrow(
        new \BmlConnect\Exceptions\BmlConnectException('API error', 500)
    );

    $fake = new class($mockTransactions)
    {
        public readonly TransactionResource $transactions;

        public function __construct(TransactionResource $transactions)
        {
            $this->transactions = $transactions;
        }
    };
    app()->instance(BmlConnect::class, $fake);

    $service = new BmlGatewayService;
    $result = $service->initiate($this->order);

    expect($result->success)->toBeFalse()
        ->and($result->errorMessage)->toContain('unavailable');
});

it('handles webhook with confirmed payment', function () {
    $this->order->update([
        'payment_method' => PaymentMethod::BmlGateway,
        'status' => OrderStatus::PaymentPending,
        'metadata' => ['bml_transaction_id' => 'txn_test_123'],
    ]);

    $webhookEvent = WebhookEvent::fromArray([
        'id' => 'evt_123',
        'eventType' => 'TRANSACTION_UPDATE',
        'transactionId' => 'txn_test_123',
        'state' => 'CONFIRMED',
        'localId' => $this->order->order_number,
        'amount' => 15000,
        'currency' => 'MVR',
    ]);

    $mockWebhooks = mock(WebhookResource::class);
    $mockWebhooks->shouldReceive('parsePayload')->once()->andReturn($webhookEvent);

    $fake = new class($mockWebhooks)
    {
        public readonly WebhookResource $webhooks;

        public function __construct(WebhookResource $webhooks)
        {
            $this->webhooks = $webhooks;
        }
    };
    app()->instance(BmlConnect::class, $fake);

    $service = new BmlGatewayService;
    $result = $service->handleCallback(request());

    expect($result->success)->toBeTrue()
        ->and($result->transactionId)->toBe('txn_test_123')
        ->and($result->orderNumber)->toBe($this->order->order_number);

    $this->order->refresh();
    expect($this->order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($this->order->status)->toBe(OrderStatus::PaymentReceived)
        ->and($this->order->paid_at)->not->toBeNull();
});

it('handles webhook with failed payment', function () {
    $this->order->update([
        'payment_method' => PaymentMethod::BmlGateway,
        'status' => OrderStatus::PaymentPending,
        'metadata' => ['bml_transaction_id' => 'txn_test_456'],
    ]);

    $webhookEvent = WebhookEvent::fromArray([
        'id' => 'evt_456',
        'eventType' => 'TRANSACTION_UPDATE',
        'transactionId' => 'txn_test_456',
        'state' => 'CANCELLED',
        'localId' => $this->order->order_number,
        'amount' => 15000,
        'currency' => 'MVR',
    ]);

    $mockWebhooks = mock(WebhookResource::class);
    $mockWebhooks->shouldReceive('parsePayload')->once()->andReturn($webhookEvent);

    $fake = new class($mockWebhooks)
    {
        public readonly WebhookResource $webhooks;

        public function __construct(WebhookResource $webhooks)
        {
            $this->webhooks = $webhooks;
        }
    };
    app()->instance(BmlConnect::class, $fake);

    $service = new BmlGatewayService;
    $result = $service->handleCallback(request());

    expect($result->success)->toBeFalse()
        ->and($result->errorMessage)->toContain('CANCELLED');

    $this->order->refresh();
    expect($this->order->payment_status)->toBe(PaymentStatus::Failed);
});

it('handles webhook with invalid signature', function () {
    $mockWebhooks = mock(WebhookResource::class);
    $mockWebhooks->shouldReceive('parsePayload')->once()->andThrow(
        new AuthenticationException('Invalid signature', 401)
    );

    $fake = new class($mockWebhooks)
    {
        public readonly WebhookResource $webhooks;

        public function __construct(WebhookResource $webhooks)
        {
            $this->webhooks = $webhooks;
        }
    };
    app()->instance(BmlConnect::class, $fake);

    $service = new BmlGatewayService;
    $result = $service->handleCallback(request());

    expect($result->success)->toBeFalse()
        ->and($result->errorMessage)->toContain('verification failed');
});

it('verifies a confirmed transaction', function () {
    $mockTransaction = Transaction::fromArray([
        'id' => 'txn_test_789',
        'amount' => 15000,
        'currency' => 'MVR',
        'state' => 'CONFIRMED',
    ]);

    $mockTransactions = mock(TransactionResource::class);
    $mockTransactions->shouldReceive('get')->with('txn_test_789')->once()->andReturn($mockTransaction);

    $fake = new class($mockTransactions)
    {
        public readonly TransactionResource $transactions;

        public function __construct(TransactionResource $transactions)
        {
            $this->transactions = $transactions;
        }
    };
    app()->instance(BmlConnect::class, $fake);

    $service = new BmlGatewayService;
    $result = $service->verify('txn_test_789');

    expect($result->verified)->toBeTrue()
        ->and($result->status)->toBe('CONFIRMED');
});

it('returns not verified for non-confirmed transaction', function () {
    $mockTransaction = Transaction::fromArray([
        'id' => 'txn_test_000',
        'amount' => 15000,
        'currency' => 'MVR',
        'state' => 'INITIATED',
    ]);

    $mockTransactions = mock(TransactionResource::class);
    $mockTransactions->shouldReceive('get')->with('txn_test_000')->once()->andReturn($mockTransaction);

    $fake = new class($mockTransactions)
    {
        public readonly TransactionResource $transactions;

        public function __construct(TransactionResource $transactions)
        {
            $this->transactions = $transactions;
        }
    };
    app()->instance(BmlConnect::class, $fake);

    $service = new BmlGatewayService;
    $result = $service->verify('txn_test_000');

    expect($result->verified)->toBeFalse()
        ->and($result->status)->toBe('INITIATED');
});

it('redirects to BML payment page via gateway route', function () {
    $mockTransaction = Transaction::fromArray([
        'id' => 'txn_route_test',
        'amount' => 15000,
        'currency' => 'MVR',
        'state' => 'INITIATED',
        'url' => 'https://pay.merchants.bankofmaldives.com.mv/txn_route_test',
        'localId' => $this->order->order_number,
    ]);

    $mockTransactions = mock(TransactionResource::class);
    $mockTransactions->shouldReceive('create')->once()->andReturn($mockTransaction);

    $fake = new class($mockTransactions)
    {
        public readonly TransactionResource $transactions;

        public function __construct(TransactionResource $transactions)
        {
            $this->transactions = $transactions;
        }
    };
    app()->instance(BmlConnect::class, $fake);

    $response = $this->post(route('payment.gateway', $this->order), [
        'method' => 'bml_gateway',
    ]);

    $response->assertRedirect('https://pay.merchants.bankofmaldives.com.mv/txn_route_test');
});

it('handles BML redirect back with confirmed payment', function () {
    $this->order->update([
        'payment_method' => PaymentMethod::BmlGateway,
        'payment_status' => PaymentStatus::Paid,
        'status' => OrderStatus::PaymentReceived,
        'metadata' => ['bml_transaction_id' => 'txn_confirmed'],
    ]);

    $response = $this->get(route('payment.bml.redirect', $this->order));

    $response->assertRedirect(route('payment.confirmation', $this->order));
});

it('handles BML redirect back with pending payment and verifies', function () {
    $this->order->update([
        'payment_method' => PaymentMethod::BmlGateway,
        'payment_status' => PaymentStatus::Unpaid,
        'status' => OrderStatus::PaymentPending,
        'metadata' => ['bml_transaction_id' => 'txn_pending'],
    ]);

    $mockTransaction = Transaction::fromArray([
        'id' => 'txn_pending',
        'amount' => 15000,
        'currency' => 'MVR',
        'state' => 'CONFIRMED',
    ]);

    $mockTransactions = mock(TransactionResource::class);
    $mockTransactions->shouldReceive('get')->with('txn_pending')->once()->andReturn($mockTransaction);

    $fake = new class($mockTransactions)
    {
        public readonly TransactionResource $transactions;

        public function __construct(TransactionResource $transactions)
        {
            $this->transactions = $transactions;
        }
    };
    app()->instance(BmlConnect::class, $fake);

    $response = $this->get(route('payment.bml.redirect', $this->order));

    $response->assertRedirect(route('payment.confirmation', $this->order));

    $this->order->refresh();
    expect($this->order->payment_status)->toBe(PaymentStatus::Paid);
});

it('shows processing page when payment not yet confirmed', function () {
    $this->order->update([
        'payment_method' => PaymentMethod::BmlGateway,
        'payment_status' => PaymentStatus::Unpaid,
        'status' => OrderStatus::PaymentPending,
        'metadata' => ['bml_transaction_id' => 'txn_waiting'],
    ]);

    $mockTransaction = Transaction::fromArray([
        'id' => 'txn_waiting',
        'amount' => 15000,
        'currency' => 'MVR',
        'state' => 'INITIATED',
    ]);

    $mockTransactions = mock(TransactionResource::class);
    $mockTransactions->shouldReceive('get')->with('txn_waiting')->once()->andReturn($mockTransaction);

    $fake = new class($mockTransactions)
    {
        public readonly TransactionResource $transactions;

        public function __construct(TransactionResource $transactions)
        {
            $this->transactions = $transactions;
        }
    };
    app()->instance(BmlConnect::class, $fake);

    $response = $this->get(route('payment.bml.redirect', $this->order));

    $response->assertOk()
        ->assertViewIs('payment.bml-redirect')
        ->assertSee('Payment Processing');
});

it('allows webhook POST without CSRF token', function () {
    $mockWebhooks = mock(WebhookResource::class);
    $mockWebhooks->shouldReceive('parsePayload')->once()->andThrow(
        new AuthenticationException('Invalid signature', 401)
    );

    $fake = new class($mockWebhooks)
    {
        public readonly WebhookResource $webhooks;

        public function __construct(WebhookResource $webhooks)
        {
            $this->webhooks = $webhooks;
        }
    };
    app()->instance(BmlConnect::class, $fake);

    $response = $this->post(route('payment.bml.webhook'), [], [
        'Content-Type' => 'application/json',
    ]);

    // Should return 200 (not 419 CSRF error)
    $response->assertOk();
});
