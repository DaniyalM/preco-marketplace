<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\MarketplaceKyc;
use App\Services\MarketplaceProvisioningService;
use App\Services\StatelessAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class MarketplaceKycController extends Controller
{
    public function __construct(
        protected StatelessAuthService $authService,
        protected MarketplaceProvisioningService $provisioning
    ) {}

    public function show(Marketplace $marketplace)
    {
        $marketplace->setConnection('platform');
        $kyc = $marketplace->kyc;
        if (! $kyc) {
            return redirect()->route('super-admin.marketplaces.show', $marketplace)
                ->with('error', 'No KYC record found.');
        }

        $kyc->setConnection('platform');

        return Inertia::render('SuperAdmin/MarketplaceKyc/Show', [
            'marketplace' => [
                'id' => $marketplace->id,
                'name' => $marketplace->name,
                'slug' => $marketplace->slug,
                'status' => $marketplace->status,
            ],
            'kyc' => [
                'id' => $kyc->id,
                'legal_name' => $kyc->legal_name,
                'tax_id' => $kyc->tax_id ? '***' . substr($kyc->tax_id, -4) : null,
                'business_type' => $kyc->business_type,
                'id_type' => $kyc->id_type,
                'status' => $kyc->status,
                'submitted_at' => $kyc->submitted_at?->toISOString(),
                'reviewed_at' => $kyc->reviewed_at?->toISOString(),
                'rejection_reason' => $kyc->rejection_reason,
                'admin_notes' => $kyc->admin_notes,
                'is_resubmission' => $kyc->is_resubmission,
                'submission_count' => $kyc->submission_count,
                'documents' => [
                    'id_front' => $kyc->id_document_front ? $this->temporaryDocumentUrl($kyc->id_document_front) : null,
                    'id_back' => $kyc->id_document_back ? $this->temporaryDocumentUrl($kyc->id_document_back) : null,
                    'proof_of_address' => $kyc->proof_of_address ? $this->temporaryDocumentUrl($kyc->proof_of_address) : null,
                    'business_registration' => $kyc->business_registration ? $this->temporaryDocumentUrl($kyc->business_registration) : null,
                ],
            ],
        ]);
    }

    public function startReview(Marketplace $marketplace)
    {
        $marketplace->setConnection('platform');
        $kyc = $marketplace->kyc;
        if (! $kyc) {
            return back()->with('error', 'No KYC record.');
        }
        if ($kyc->status !== 'pending') {
            return back()->with('error', 'KYC is not pending.');
        }
        $kyc->startReview();

        return back()->with('success', 'KYC is now under review.');
    }

    public function approve(Request $request, Marketplace $marketplace)
    {
        $marketplace->setConnection('platform');
        $kyc = $marketplace->kyc;
        if (! $kyc) {
            return back()->with('error', 'No KYC record.');
        }
        if ($kyc->status !== 'pending' && $kyc->status !== 'under_review') {
            return back()->with('error', 'Only pending or under-review KYC can be approved.');
        }

        $userId = $this->authService->getUserId($request);
        $notes = $request->input('notes');

        $kyc->approve($userId ?? 'super_admin', $notes);

        try {
            $this->provisioning->provisionTenantDatabase($marketplace);
        } catch (\Throwable $e) {
            $marketplace->update([
                'status' => 'kyc_under_review',
                'approved_at' => null,
            ]);
            $kyc->update(['status' => 'under_review']);

            return back()->with('error', 'KYC approved but tenant database provisioning failed: ' . $e->getMessage());
        }

        return redirect()->route('super-admin.marketplaces.index')
            ->with('success', 'Marketplace approved and tenant database provisioned.');
    }

    public function reject(Request $request, Marketplace $marketplace)
    {
        $marketplace->setConnection('platform');
        $kyc = $marketplace->kyc;
        if (! $kyc) {
            return back()->with('error', 'No KYC record.');
        }
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $userId = $this->authService->getUserId($request);
        $kyc->reject($userId ?? 'super_admin', $validated['reason'], $validated['notes'] ?? null);

        return redirect()->route('super-admin.marketplaces.index')
            ->with('success', 'Marketplace KYC rejected.');
    }

    protected function temporaryDocumentUrl(string $path): ?string
    {
        try {
            return Storage::disk('private')->temporaryUrl($path, now()->addMinutes(30));
        } catch (\Throwable) {
            return null;
        }
    }
}
