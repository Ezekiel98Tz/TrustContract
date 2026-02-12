<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Business;
use App\Models\BusinessVerification;
use App\Models\Contract;
use App\Models\Dispute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationsPhase3Test extends TestCase
{
    use RefreshDatabase;

    public function test_business_verification_review_sends_notification()
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'Admin', 'email_verified_at' => now()]);
        $seller = User::factory()->create(['role' => 'Seller', 'email' => 'seller@example.com']);
        $business = Business::create([
            'user_id' => $seller->id,
            'company_name' => 'Seller Co',
            'registration_number' => 'REG123',
            'jurisdiction' => 'KE',
            'address_line1' => 'HQ',
            'city' => 'Mombasa',
            'postal_code' => '80100',
            'verification_status' => 'unverified',
        ]);
        $verification = BusinessVerification::create([
            'business_id' => $business->id,
            'document_type' => 'certificate',
            'document_path' => '/tmp/doc.pdf',
            'status' => 'pending',
        ]);

        $this->actingAs($admin);
        $resp = $this->patch(route('admin.business-verifications.review', $verification->id), [
            'status' => 'approved',
            'notes' => 'Looks good',
        ]);
        $resp->assertRedirect();

        Notification::assertSentTo(
            [$seller],
            \App\Notifications\BusinessVerificationReviewedNotification::class
        );
    }

    public function test_dispute_notifications_on_create_and_status_change()
    {
        Notification::fake();
        $buyer = User::factory()->create(['role' => 'Buyer', 'email_verified_at' => now(), 'email' => 'buyer@example.com']);
        $seller = User::factory()->create(['role' => 'Seller', 'email' => 'seller@example.com']);
        $contract = Contract::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'title' => 'Agreement',
            'price_cents' => 1000,
            'currency' => 'USD',
            'status' => 'finalized',
        ]);

        // Buyer opens a dispute -> Seller should be notified
        $this->actingAs($buyer);
        $resp = $this->post(route('contracts.disputes.store', $contract->id), [
            'reason' => 'Item not delivered',
        ]);
        $resp->assertRedirect();
        $dispute = Dispute::latest()->first();

        Notification::assertSentTo(
            [$seller],
            \App\Notifications\DisputeCreatedNotification::class
        );

        // Admin changes status -> both parties notified
        $admin = User::factory()->create(['role' => 'Admin', 'email_verified_at' => now()]);
        $this->actingAs($admin);
        $resp = $this->patch(route('admin.disputes.review', $dispute->id), [
            'status' => 'resolved',
            'resolution' => 'won',
        ]);
        $resp->assertRedirect();

        Notification::assertSentToTimes($buyer, \App\Notifications\DisputeStatusChangedNotification::class, 1);
        Notification::assertSentToTimes($seller, \App\Notifications\DisputeStatusChangedNotification::class, 1);
    }
}
