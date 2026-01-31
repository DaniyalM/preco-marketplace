<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessKycDocuments;
use App\Models\Vendor;
use App\Models\VendorKyc;
use App\Services\StatelessAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * Vendor onboarding controller.
 * 
 * Stateless - user info from JWT token.
 */
class OnboardingController extends Controller
{
    public function __construct(
        protected StatelessAuthService $authService
    ) {}

    /**
     * Show the onboarding start page
     */
    public function index(Request $request)
    {
        if (!$this->authService->isAuthenticated($request)) {
            return redirect('/login');
        }

        $user = $this->authService->getUser($request);
        $userId = $this->authService->getUserId($request);

        // Check if vendor already exists
        $vendor = Vendor::where('keycloak_user_id', $userId)->first();
        
        if ($vendor) {
            return redirect()->route('vendor.status');
        }

        return Inertia::render('Vendor/Onboarding/Start', [
            'user' => [
                'name' => $user->name ?? $user->preferred_username ?? null,
                'email' => $user->email ?? null,
            ],
        ]);
    }

    /**
     * Store vendor basic info (Step 1)
     */
    public function storeBasicInfo(Request $request)
    {
        $user = $this->authService->getUser($request);
        $userId = $this->authService->getUserId($request);
        
        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|in:individual,company,partnership',
            'phone' => 'required|string|max:20',
            'description' => 'nullable|string|max:1000',
            'website' => 'nullable|url|max:255',
        ]);

        $vendor = Vendor::create([
            'keycloak_user_id' => $userId,
            'email' => $user->email ?? '',
            'business_name' => $validated['business_name'],
            'slug' => Str::slug($validated['business_name']) . '-' . Str::random(6),
            'business_type' => $validated['business_type'],
            'phone' => $validated['phone'],
            'description' => $validated['description'] ?? null,
            'website' => $validated['website'] ?? null,
            'status' => 'pending',
        ]);

        return redirect()->route('vendor.onboarding.address');
    }

    /**
     * Show address form (Step 2)
     */
    public function showAddressForm(Request $request)
    {
        $userId = $this->authService->getUserId($request);
        $vendor = Vendor::where('keycloak_user_id', $userId)->first();
        
        if (!$vendor) {
            return redirect()->route('vendor.onboarding');
        }

        return Inertia::render('Vendor/Onboarding/Address', [
            'vendor' => $vendor->only(['id', 'business_name', 'address_line_1', 'city', 'state', 'postal_code', 'country']),
        ]);
    }

    /**
     * Store vendor address (Step 2)
     */
    public function storeAddress(Request $request)
    {
        $userId = $this->authService->getUserId($request);
        $vendor = Vendor::where('keycloak_user_id', $userId)->firstOrFail();

        $validated = $request->validate([
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
        ]);

        $vendor->update($validated);

        return redirect()->route('vendor.onboarding.kyc');
    }

    /**
     * Show KYC form (Step 3)
     */
    public function showKycForm(Request $request)
    {
        $userId = $this->authService->getUserId($request);
        $vendor = Vendor::where('keycloak_user_id', $userId)->first();
        
        if (!$vendor) {
            return redirect()->route('vendor.onboarding');
        }

        $existingKyc = $vendor->kyc;

        return Inertia::render('Vendor/Onboarding/Kyc', [
            'vendor' => $vendor->only(['id', 'business_name', 'business_type']),
            'existingKyc' => $existingKyc ? [
                'status' => $existingKyc->status,
                'rejection_reason' => $existingKyc->rejection_reason,
            ] : null,
        ]);
    }

    /**
     * Store KYC documents (Step 3)
     */
    public function storeKyc(Request $request)
    {
        $userId = $this->authService->getUserId($request);
        $vendor = Vendor::where('keycloak_user_id', $userId)->firstOrFail();

        $validated = $request->validate([
            'legal_name' => 'required|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date|before:today',
            'nationality' => 'nullable|string|max:2',
            'id_type' => 'required|in:passport,national_id,drivers_license,business_license',
            'id_document_front' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'id_document_back' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'proof_of_address' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'business_registration' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_routing_number' => 'nullable|string|max:50',
        ]);

        // Handle file uploads
        $files = [];
        foreach (['id_document_front', 'id_document_back', 'proof_of_address', 'business_registration'] as $field) {
            if ($request->hasFile($field)) {
                $files[$field] = $request->file($field)->store("vendors/{$vendor->id}/kyc", 'private');
            }
        }

        // Check if this is a resubmission
        $existingKyc = $vendor->kyc;
        $isResubmission = $existingKyc && $existingKyc->status === 'rejected';
        $submissionCount = $existingKyc ? $existingKyc->submission_count + 1 : 1;

        // Create new KYC record
        $kyc = VendorKyc::create([
            'vendor_id' => $vendor->id,
            'legal_name' => $validated['legal_name'],
            'tax_id' => $validated['tax_id'] ?? null,
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'nationality' => $validated['nationality'] ?? null,
            'id_type' => $validated['id_type'],
            'id_document_front' => $files['id_document_front'],
            'id_document_back' => $files['id_document_back'] ?? null,
            'proof_of_address' => $files['proof_of_address'] ?? null,
            'business_registration' => $files['business_registration'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_name' => $validated['bank_account_name'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
            'bank_routing_number' => $validated['bank_routing_number'] ?? null,
            'status' => 'pending',
            'submitted_at' => now(),
            'is_resubmission' => $isResubmission,
            'submission_count' => $submissionCount,
        ]);

        // Queue document processing (validation, scanning)
        ProcessKycDocuments::dispatch($kyc);

        // Update vendor status
        $vendor->update(['status' => 'under_review']);

        return redirect()->route('vendor.status');
    }

    /**
     * Show vendor status page
     */
    public function status(Request $request)
    {
        $userId = $this->authService->getUserId($request);
        $vendor = Vendor::where('keycloak_user_id', $userId)
            ->with('kyc')
            ->first();

        if (!$vendor) {
            return redirect()->route('vendor.onboarding');
        }

        // If approved, redirect to dashboard
        if ($vendor->isApproved()) {
            return redirect()->route('vendor.dashboard');
        }

        return Inertia::render('Vendor/Onboarding/Status', [
            'vendor' => [
                'id' => $vendor->id,
                'business_name' => $vendor->business_name,
                'status' => $vendor->status,
                'rejection_reason' => $vendor->rejection_reason,
                'created_at' => $vendor->created_at->toISOString(),
            ],
            'kyc' => $vendor->kyc ? [
                'status' => $vendor->kyc->status,
                'rejection_reason' => $vendor->kyc->rejection_reason,
                'submitted_at' => $vendor->kyc->submitted_at?->toISOString(),
                'reviewed_at' => $vendor->kyc->reviewed_at?->toISOString(),
            ] : null,
        ]);
    }
}
