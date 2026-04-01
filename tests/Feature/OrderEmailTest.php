<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Jobs\SendPaymentReminderEmails;
use App\Mail\Orders\AffiliateOrderAcceptedGatewayMail;
use App\Mail\Orders\AffiliateOrderAcceptedTransferMail;
use App\Mail\Orders\AffiliateOrderReceivedGatewayMail;
use App\Mail\Orders\AffiliateOrderReceivedTransferMail;
use App\Mail\Orders\AffiliateOrderRejectedMail;
use App\Mail\Orders\BankTransferApprovedMail;
use App\Mail\Orders\BankTransferReceiptSubmittedMail;
use App\Mail\Orders\BankTransferRejectedMail;
use App\Mail\Orders\OrderCancelledMail;
use App\Mail\Orders\PaymentCompleteMail;
use App\Mail\Orders\PaymentFailedMail;
use App\Mail\Orders\PaymentReminderMail;
use App\Mail\Orders\TransferReminderMail;
use App\Models\Order;
use App\Services\OrderEmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
});

// --- Order Placed Emails ---

it('sends affiliate order received email with gateway payment method', function () {
    $order = Order::factory()->withAffiliateProducts()->create([
        'email' => 'customer@example.com',
        'payment_method' => PaymentMethod::BmlGateway,
    ]);

    app(OrderEmailService::class)->sendOrderPlacedEmail($order);

    Mail::assertQueued(AffiliateOrderReceivedGatewayMail::class, function ($mail) {
        return $mail->hasTo('customer@example.com');
    });
});

it('sends affiliate order received email with transfer payment method', function () {
    $order = Order::factory()->withAffiliateProducts()->create([
        'email' => 'customer@example.com',
        'payment_method' => PaymentMethod::BankTransfer,
    ]);

    app(OrderEmailService::class)->sendOrderPlacedEmail($order);

    Mail::assertQueued(AffiliateOrderReceivedTransferMail::class, function ($mail) {
        return $mail->hasTo('customer@example.com');
    });
});

it('does not send order placed email for non-affiliate orders', function () {
    $order = Order::factory()->create([
        'email' => 'customer@example.com',
        'has_affiliate_products' => false,
    ]);

    app(OrderEmailService::class)->sendOrderPlacedEmail($order);

    Mail::assertNothingQueued();
});

// --- Payment Completed ---

it('sends payment complete email', function () {
    $order = Order::factory()->create([
        'email' => 'customer@example.com',
        'payment_method' => PaymentMethod::BmlGateway,
    ]);

    app(OrderEmailService::class)->sendPaymentCompletedEmail($order);

    Mail::assertQueued(PaymentCompleteMail::class, function ($mail) {
        return $mail->hasTo('customer@example.com');
    });
});

// --- Bank Transfer Receipt Submitted ---

it('sends bank transfer receipt submitted email', function () {
    $order = Order::factory()->create([
        'email' => 'customer@example.com',
        'payment_method' => PaymentMethod::BankTransfer,
    ]);

    app(OrderEmailService::class)->sendBankTransferReceiptSubmittedEmail($order);

    Mail::assertQueued(BankTransferReceiptSubmittedMail::class, function ($mail) {
        return $mail->hasTo('customer@example.com');
    });
});

// --- Payment Failed ---

it('sends payment failed email', function () {
    $order = Order::factory()->create([
        'email' => 'customer@example.com',
        'payment_method' => PaymentMethod::BmlGateway,
    ]);

    app(OrderEmailService::class)->sendPaymentFailedEmail($order);

    Mail::assertQueued(PaymentFailedMail::class, function ($mail) {
        return $mail->hasTo('customer@example.com');
    });
});

// --- Bank Transfer Rejected ---

it('sends bank transfer rejected email', function () {
    $order = Order::factory()->create([
        'email' => 'customer@example.com',
        'payment_method' => PaymentMethod::BankTransfer,
    ]);

    app(OrderEmailService::class)->sendBankTransferRejectedEmail($order);

    Mail::assertQueued(BankTransferRejectedMail::class, function ($mail) {
        return $mail->hasTo('customer@example.com');
    });
});

// --- Bank Transfer Approved ---

it('sends bank transfer approved email', function () {
    $order = Order::factory()->create([
        'email' => 'customer@example.com',
        'payment_method' => PaymentMethod::BankTransfer,
    ]);

    app(OrderEmailService::class)->sendBankTransferApprovedEmail($order);

    Mail::assertQueued(BankTransferApprovedMail::class, function ($mail) {
        return $mail->hasTo('customer@example.com');
    });
});

// --- Order Accepted ---

it('sends affiliate order accepted email with gateway payment', function () {
    $order = Order::factory()->withAffiliateProducts()->accepted()->create([
        'email' => 'customer@example.com',
        'payment_method' => PaymentMethod::BmlGateway,
    ]);

    app(OrderEmailService::class)->sendOrderAcceptedEmail($order);

    Mail::assertQueued(AffiliateOrderAcceptedGatewayMail::class, function ($mail) {
        return $mail->hasTo('customer@example.com');
    });
});

it('sends affiliate order accepted email with transfer payment', function () {
    $order = Order::factory()->withAffiliateProducts()->accepted()->create([
        'email' => 'customer@example.com',
        'payment_method' => PaymentMethod::BankTransfer,
    ]);

    app(OrderEmailService::class)->sendOrderAcceptedEmail($order);

    Mail::assertQueued(AffiliateOrderAcceptedTransferMail::class, function ($mail) {
        return $mail->hasTo('customer@example.com');
    });
});

// --- Order Rejected ---

it('sends affiliate order rejected email', function () {
    $order = Order::factory()->withAffiliateProducts()->create([
        'email' => 'customer@example.com',
    ]);

    app(OrderEmailService::class)->sendOrderRejectedEmail($order, 'full');

    Mail::assertQueued(AffiliateOrderRejectedMail::class, function ($mail) {
        return $mail->hasTo('customer@example.com') && $mail->rejectionType === 'full';
    });
});

it('sends partial rejection email', function () {
    $order = Order::factory()->withAffiliateProducts()->create([
        'email' => 'customer@example.com',
    ]);

    app(OrderEmailService::class)->sendOrderRejectedEmail($order, 'partial');

    Mail::assertQueued(AffiliateOrderRejectedMail::class, function ($mail) {
        return $mail->rejectionType === 'partial';
    });
});

// --- Order Cancelled ---

it('sends order cancelled email', function () {
    $order = Order::factory()->cancelled()->create([
        'email' => 'customer@example.com',
    ]);

    app(OrderEmailService::class)->sendOrderCancelledEmail($order);

    Mail::assertQueued(OrderCancelledMail::class, function ($mail) {
        return $mail->hasTo('customer@example.com');
    });
});

// --- No Email When Missing ---

it('does not send email when order has no email address', function () {
    $order = Order::factory()->create(['email' => null]);

    $service = app(OrderEmailService::class);
    $service->sendPaymentCompletedEmail($order);
    $service->sendOrderCancelledEmail($order);
    $service->sendPaymentFailedEmail($order);

    Mail::assertNothingQueued();
});

// --- Payment Reminder Job ---

it('sends payment reminder for non-affiliate orders after 10 minutes', function () {
    $order = Order::factory()->accepted()->create([
        'email' => 'customer@example.com',
        'has_affiliate_products' => false,
        'payment_status' => PaymentStatus::Unpaid,
        'payment_method' => PaymentMethod::BmlGateway,
        'created_at' => now()->subMinutes(15),
    ]);

    (new SendPaymentReminderEmails)->handle(app(OrderEmailService::class));

    Mail::assertQueued(PaymentReminderMail::class, function ($mail) {
        return $mail->hasTo('customer@example.com');
    });
});

it('sends transfer reminder for non-affiliate orders after 10 minutes', function () {
    $order = Order::factory()->accepted()->create([
        'email' => 'customer@example.com',
        'has_affiliate_products' => false,
        'payment_status' => PaymentStatus::Unpaid,
        'payment_method' => PaymentMethod::BankTransfer,
        'created_at' => now()->subMinutes(15),
    ]);

    (new SendPaymentReminderEmails)->handle(app(OrderEmailService::class));

    Mail::assertQueued(TransferReminderMail::class, function ($mail) {
        return $mail->hasTo('customer@example.com');
    });
});

it('does not send reminder before 10 minutes for non-affiliate orders', function () {
    Order::factory()->accepted()->create([
        'email' => 'customer@example.com',
        'has_affiliate_products' => false,
        'payment_status' => PaymentStatus::Unpaid,
        'created_at' => now()->subMinutes(5),
    ]);

    (new SendPaymentReminderEmails)->handle(app(OrderEmailService::class));

    Mail::assertNothingQueued();
});

it('does not send duplicate reminders', function () {
    $order = Order::factory()->accepted()->create([
        'email' => 'customer@example.com',
        'has_affiliate_products' => false,
        'payment_status' => PaymentStatus::Unpaid,
        'payment_method' => PaymentMethod::BmlGateway,
        'created_at' => now()->subMinutes(15),
        'metadata' => ['payment_reminder_sent_at' => now()->subMinutes(5)->toISOString()],
    ]);

    (new SendPaymentReminderEmails)->handle(app(OrderEmailService::class));

    Mail::assertNothingQueued();
});

it('sends payment reminder for affiliate orders after 24 hours', function () {
    $order = Order::factory()->withAffiliateProducts()->accepted()->create([
        'email' => 'customer@example.com',
        'payment_status' => PaymentStatus::Unpaid,
        'payment_method' => PaymentMethod::BmlGateway,
        'accepted_at' => now()->subHours(25),
    ]);

    (new SendPaymentReminderEmails)->handle(app(OrderEmailService::class));

    Mail::assertQueued(PaymentReminderMail::class, function ($mail) {
        return $mail->hasTo('customer@example.com');
    });
});
