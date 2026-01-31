<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendKycStatusNotification;
use App\Models\VendorKyc;
use App\Services\StatelessAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class KycController extends Controller
{
    public function __construct(
        protected StatelessAuthService $authService
    ) {}

    public function index(Request $request)
    {
        $query = VendorKyc::with('vendor:id,business_name,email,status');

        // Default to pending/under_review
        $status = $request->get('status', 'pending');
        if ($status !== 'all') {
            if ($status === 'action_required') {
                $query->whereIn('status', ['pending', 'under_review']);
            } else {
                $query->where('status', $status);
            }
        }

        if ($request->has('search')) {
            $query->whereHas('vendor', function ($q) use ($request) {
                $q->where('business_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $kycSubmissions = $query->orderBy('submitted_at', 'desc')
            ->paginate(20)
            ->through(fn ($kyc) => [
                'id' => $kyc->id,
                'vendor_id' => $kyc->vendor_id,
                'vendor_name' => $kyc->vendor->business_name,
                'vendor_email' => $kyc->vendor->email,
                'legal_name' => $kyc->legal_name,
                'id_type' => $kyc->id_type,
                'status' => $kyc->status,
                'is_resubmission' => $kyc->is_resubmission,
                'submission_count' => $kyc->submission_count,
                'submitted_at' => $kyc->submitted_at?->toISOString(),
            ]);

        // Stats
        $stats = [
            'pending' => VendorKyc::where('status', 'pending')->count(),
            'under_review' => VendorKyc::where('status', 'under_review')->count(),
            'approved' => VendorKyc::where('status', 'approved')->count(),
            'rejected' => VendorKyc::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Admin/Kyc/Index', [
            'submissions' => $kycSubmissions,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show(VendorKyc $kyc)
    {
        $kyc->load('vendor');

        return Inertia::render('Admin/Kyc/Show', [
            'kyc' => [
                'id' => $kyc->id,
                'vendor_id' => $kyc->vendor_id,
                'vendor' => [
                    'id' => $kyc->vendor->id,
                    'business_name' => $kyc->vendor->business_name,
                    'email' => $kyc->vendor->email,
                    'business_type' => $kyc->vendor->business_type,
                ],
                'legal_name' => $kyc->legal_name,
                'tax_id' => $kyc->tax_id ? '***' . substr($kyc->tax_id, -4) : null,
                'date_of_birth' => $kyc->date_of_birth?->toDateString(),
                'nationality' => $kyc->nationality,
                'id_type' => $kyc->id_type,
                'bank_name' => $kyc->bank_name,
                'bank_account_name' => $kyc->bank_account_name,
                'status' => $kyc->status,
                'submitted_at' => $kyc->submitted_at?->toISOString(),
                'reviewed_at' => $kyc->reviewed_at?->toISOString(),
                'rejection_reason' => $kyc->rejection_reason,
                'admin_notes' => $kyc->admin_notes,
                'is_resubmission' => $kyc->is_resubmission,
                'submission_count' => $kyc->submission_count,
                // Document URLs (signed for security)
                'documents' => [
                    'id_front' => $kyc->id_document_front ? Storage::disk('private')->temporaryUrl($kyc->id_document_front, now()->addMinutes(30)) : null,
                    'id_back' => $kyc->id_document_back ? Storage::disk('private')->temporaryUrl($kyc->id_document_back, now()->addMinutes(30)) : null,
                    'proof_of_address' => $kyc->proof_of_address ? Storage::disk('private')->temporaryUrl($kyc->proof_of_address, now()->addMinutes(30)) : null,
                    'business_registration' => $kyc->business_registration ? Storage::disk('private')->temporaryUrl($kyc->business_registration, now()->addMinutes(30)) : null,
                ],
            ],
        ]);
    }

    public function startReview(Request $request, VendorKyc $kyc)
    {
        if ($kyc->status !== 'pending') {
            return back()->with('error', 'KYC is not pending.');
        }

        $kyc->startReview();

        // Queue notification
        SendKycStatusNotification::dispatch($kyc, 'under_review');

        return back()->with('success', 'KYC review started.');
    }

    public function approve(Request $request, VendorKyc $kyc)
    {
        $userId = $this->authService->getUserId($request);
        $notes = $request->input('notes');

        $kyc->approve($userId, $notes);

        // Queue notification
        SendKycStatusNotification::dispatch($kyc, 'approved');

        return redirect()->route('admin.kyc.index')
            ->with('success', 'KYC approved successfully.');
    }

    public function reject(Request $request, VendorKyc $kyc)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $userId = $this->authService->getUserId($request);

        $kyc->reject($userId, $validated['reason'], $validated['notes'] ?? null);

        // Queue notification
        SendKycStatusNotification::dispatch($kyc, 'rejected', $validated['reason']);

        return redirect()->route('admin.kyc.index')
            ->with('success', 'KYC rejected.');
    }
}
