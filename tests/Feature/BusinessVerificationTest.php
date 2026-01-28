<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Business;
use App\Models\BusinessVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_business_docs_and_admin_can_review()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $this->actingAs($user);

        $resp = $this->patch(route('account.business-information.update'), [
            'company_name' => 'Acme Ltd',
            'registration_number' => 'REG-123',
            'jurisdiction' => 'KE',
        ]);
        $resp->assertRedirect();

        $file = \Illuminate\Http\UploadedFile::fake()->image('reg.jpg');
        $resp = $this->post(route('account.business-information.submit-document'), [
            'document_type' => 'business_registration',
            'document' => $file,
        ]);
        $resp->assertRedirect();

        $business = Business::first();
        $this->assertNotNull($business);
        $this->assertEquals('pending', $business->verification_status);
        $ver = BusinessVerification::first();
        $this->assertNotNull($ver);
        $this->assertEquals('pending', $ver->status);

        $admin = User::factory()->create(['role' => 'Admin']);
        $this->actingAs($admin);

        $resp = $this->patch(route('admin.business-verifications.review', $ver), ['status' => 'approved']);
        $resp->assertRedirect();

        $business->refresh();
        $this->assertEquals('verified', $business->verification_status);
        $this->assertEquals('standard', $business->verification_level);
    }
}
