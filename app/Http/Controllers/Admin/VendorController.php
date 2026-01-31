<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendVendorApprovalNotification;
use App\Jobs\SendKycStatusNotification;
use App\Models\Vendor;
use App\Models\VendorKyc;
use App\Services\StatelessAuthService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VendorController extends Controller
{
    public function __construct(
        protected StatelessAuthService $authService
    ) {}

    public function index(Request $request)
    {
        $query = Vendor::with('kyc:id,vendor_id,status');

        // Filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('business_name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        $vendors = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->through(fn ($vendor) => [
                'id' => $vendor->id,
                'business_name' => $vendor->business_name,
                'email' => $vendor->email,
                'business_type' => $vendor->business_type,
                'status' => $vendor->status,
                'kyc_status' => $vendor->kyc?->status,
                'is_featured' => $vendor->is_featured,
                'created_at' => $vendor->created_at->toISOString(),
            ]);

        // Stats
        $stats = [
            'total' => Vendor::count(),
            'pending' => Vendor::where('status', 'pending')->count(),
            'under_review' => Vendor::where('status', 'under_review')->count(),
            'approved' => Vendor::where('status', 'approved')->count(),
        ];

        return Inertia::render('Admin/Vendors/Index', [
            'vendors' => $vendors,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show(Vendor $vendor)
    {
        $vendor->load(['kyc', 'products' => fn($q) => $q->latest()->take(5)]);

        return Inertia::render('Admin/Vendors/Show', [
            'vendor' => [
                'id' => $vendor->id,
                'keycloak_user_id' => $vendor->keycloak_user_id,
                'business_name' => $vendor->business_name,
                'email' => $vendor->email,
                'business_type' => $vendor->business_type,
                'phone' => $vendor->phone,
                'description' => $vendor->description,
                'website' => $vendor->website,
                'logo' => $vendor->logo,
                'banner' => $vendor->banner,
                'address' => $vendor->full_address,
                'status' => $vendor->status,
                'rejection_reason' => $vendor->rejection_reason,
                'commission_rate' => $vendor->commission_rate,
                'is_featured' => $vendor->is_featured,
                'approved_at' => $vendor->approved_at?->toISOString(),
                'created_at' => $vendor->created_at->toISOString(),
            ],
            'kyc' => $vendor->kyc ? [
                'id' => $vendor->kyc->id,
                'legal_name' => $vendor->kyc->legal_name,
                'id_type' => $vendor->kyc->id_type,
                'status' => $vendor->kyc->status,
                'submitted_at' => $vendor->kyc->submitted_at?->toISOString(),
                'reviewed_at' => $vendor->kyc->reviewed_at?->toISOString(),
                'rejection_reason' => $vendor->kyc->rejection_reason,
                'admin_notes' => $vendor->kyc->admin_notes,
            ] : null,
            'recentProducts' => $vendor->products->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'status' => $p->status,
                'base_price' => $p->base_price,
            ]),
        ]);
    }

    public function approve(Request $request, Vendor $vendor)
    {
        $userId = $this->authService->getUserId($request);

        // Approve KYC if exists
        if ($vendor->kyc && !$vendor->kyc->isApproved()) {
            $vendor->kyc->approve($userId, $request->input('notes'));
            SendKycStatusNotification::dispatch($vendor->kyc, 'approved');
        } else {
            // Just approve the vendor
            $vendor->approve();
        }

        // Queue vendor approval notification
        SendVendorApprovalNotification::dispatch($vendor, 'approved');

        return back()->with('success', 'Vendor approved successfully.');
    }

    public function reject(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ]);

        $userId = $this->authService->getUserId($request);

        // Reject KYC if exists
        if ($vendor->kyc) {
            $vendor->kyc->reject($userId, $validated['reason'], $validated['notes'] ?? null);
            SendKycStatusNotification::dispatch($vendor->kyc, 'rejected', $validated['reason']);
        }

        // Reject vendor
        $vendor->reject($validated['reason']);

        // Queue vendor rejection notification
        SendVendorApprovalNotification::dispatch($vendor, 'rejected', $validated['reason']);

        return back()->with('success', 'Vendor rejected.');
    }

    public function suspend(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $vendor->suspend($validated['reason']);

        // Queue suspension notification
        SendVendorApprovalNotification::dispatch($vendor, 'suspended', $validated['reason']);

        return back()->with('success', 'Vendor suspended.');
    }

    public function updateCommission(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'commission_rate' => 'required|numeric|min:0|max:100',
        ]);

        $vendor->update(['commission_rate' => $validated['commission_rate']]);

        return back()->with('success', 'Commission rate updated.');
    }

    public function toggleFeatured(Vendor $vendor)
    {
        $vendor->update(['is_featured' => !$vendor->is_featured]);

        return back()->with('success', $vendor->is_featured ? 'Vendor featured.' : 'Vendor unfeatured.');
    }
}
