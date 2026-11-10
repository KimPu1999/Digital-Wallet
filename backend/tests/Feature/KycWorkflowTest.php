<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\KycCase;
use App\Models\KycDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class KycWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
        DB::table('countries')->insert(['code' => 'MM', 'name' => 'Myanmar', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('country_capabilities')->insert(['country_code' => 'MM', 'registration_enabled' => true, 'kyc_enabled' => true, 'wallet_enabled' => false, 'deposits_enabled' => false, 'withdrawals_enabled' => false, 'transfers_enabled' => false, 'fx_enabled' => false, 'created_at' => now(), 'updated_at' => now()]);
        foreach ([['legal_first_name', 'Legal first name'], ['legal_last_name', 'Legal last name'], ['date_of_birth', 'Date of birth'], ['nationality', 'Nationality'], ['residence_country_code', 'Country of residence'], ['address_line1', 'Residential address'], ['document:passport', 'Passport']] as [$key, $label]) {
            DB::table('kyc_requirements')->insert(['id' => (string) Str::uuid(), 'country_code' => 'MM', 'subject_type' => 'customer', 'requirement_key' => $key, 'label' => $label, 'is_required' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
        Storage::fake('local');
    }

    public function test_customer_can_save_draft_upload_document_and_submit_for_review(): void
    {
        $user = $this->customer();
        $this->actingAs($user, 'web');

        $this->getJson('/api/v1/me/kyc')->assertOk()->assertJsonPath('data.policy.available', true);
        $this->patchJson('/api/v1/me/kyc/draft', [
            'legal_first_name' => 'Kai', 'legal_last_name' => 'Lin', 'date_of_birth' => '1999-01-01',
            'nationality' => 'MM', 'residence_country_code' => 'MM', 'address_line1' => 'Yangon',
        ])->assertOk()->assertJsonPath('data.status', 'draft');

        $document = $this->post('/api/v1/me/kyc/documents', ['document_type' => 'passport', 'document' => UploadedFile::fake()->image('passport.jpg')]);
        $document->assertCreated()->assertJsonPath('data.document.document_type', 'passport');
        $id = $document->json('data.document.id');

        $this->postJson('/api/v1/me/kyc/submit', ['consent' => true])->assertOk()->assertJsonPath('data.status', 'submitted');
        $this->assertDatabaseHas('kyc_status_events', ['to_status' => 'draft']);
        $this->assertDatabaseHas('kyc_status_events', ['to_status' => 'submitted']);
        $this->assertNotFalse($id);
    }

    public function test_customer_cannot_access_another_customers_document(): void
    {
        $owner = $this->customer();
        $case = KycCase::create(['user_id' => $owner->id, 'country_code' => 'MM', 'status' => 'draft']);
        $document = KycDocument::create(['kyc_case_id' => $case->id, 'document_type' => 'passport', 'storage_key' => 'kyc/'.$case->id.'/passport.jpg', 'mime_type' => 'image/jpeg', 'size_bytes' => 100, 'scan_status' => 'pending', 'review_status' => 'pending', 'uploaded_at' => now()]);

        $other = $this->customer('other@example.com', '+95912345679');
        $this->actingAs($other, 'web');
        $otherResponse = $this->deleteJson('/api/v1/me/kyc/documents/'.$document->id);
        $otherResponse->assertNotFound();
    }

    public function test_kyc_is_blocked_when_country_policy_is_disabled(): void
    {
        DB::table('country_capabilities')->where('country_code', 'MM')->update(['kyc_enabled' => false]);
        $this->actingAs($this->customer(), 'web');
        $this->post('/api/v1/me/kyc/documents', ['document_type' => 'passport', 'document' => UploadedFile::fake()->image('passport.jpg')])->assertForbidden();
    }

    public function test_invalid_document_type_and_file_are_rejected(): void
    {
        $this->actingAs($this->customer(), 'web');
        $this->post('/api/v1/me/kyc/documents', ['document_type' => 'driver_license', 'document' => UploadedFile::fake()->image('license.jpg')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post('/api/v1/me/kyc/documents', ['document_type' => 'passport', 'document' => UploadedFile::fake()->create('script.php', 10, 'application/x-php')], ['Accept' => 'application/json'])->assertUnprocessable();
    }

    private function customer(string $email = 'customer@example.com', string $phone = '+95912345678'): User
    {
        $user = User::create(['name' => 'Customer', 'email' => $email, 'password' => 'password-password', 'country_code' => 'MM', 'phone_e164' => $phone, 'account_status' => 'active', 'user_type' => 'customer']);
        $user->forceFill(['email_verified_at' => now()])->save();
        return $user;
    }
}
