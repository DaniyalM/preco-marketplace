<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\MarketplaceKyc;
use App\Services\StatelessAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MarketplaceController extends Controller
{
    public function __construct(
        protected StatelessAuthService $authService
    ) {}

    public function index(Request $request)
    {
        $query = Marketplace::on('platform')->with('kyc')->orderByDesc('created_at');

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('slug', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $marketplaces = $query->paginate(15)->through(fn (Marketplace $m) => [
            'id' => $m->id,
            'name' => $m->name,
            'slug' => $m->slug,
            'domain' => $m->domain,
            'email' => $m->email,
            'status' => $m->status,
            'has_tenant_database' => $m->hasTenantDatabase(),
            'kyc_status' => $m->kyc?->status,
            'created_at' => $m->created_at->toISOString(),
        ]);

        $stats = [
            'pending_kyc' => Marketplace::on('platform')->where('status', 'pending_kyc')->count(),
            'kyc_under_review' => Marketplace::on('platform')->where('status', 'kyc_under_review')->count(),
            'approved' => Marketplace::on('platform')->where('status', 'approved')->count(),
            'rejected' => Marketplace::on('platform')->where('status', 'rejected')->count(),
        ];

        return Inertia::render('SuperAdmin/Marketplaces/Index', [
            'marketplaces' => $marketplaces,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('SuperAdmin/Marketplaces/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('marketplaces', 'slug')->connection('platform')],
            'email' => 'nullable|email',
            'support_email' => 'nullable|email',
            'domain' => 'nullable|string|max:255',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        if (Marketplace::on('platform')->where('slug', $slug)->exists()) {
            $slug = $slug . '-' . substr(uniqid(), -4);
        }

        $marketplace = Marketplace::on('platform')->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'domain' => $validated['domain'] ?? $slug,
            'email' => $validated['email'] ?? null,
            'support_email' => $validated['support_email'] ?? null,
            'status' => 'pending_kyc',
        ]);

        MarketplaceKyc::on('platform')->create([
            'marketplace_id' => $marketplace->id,
            'legal_name' => $validated['name'],
            'id_type' => 'business_license',
            'id_document_front' => null,
            'status' => 'draft',
        ]);

        return redirect()->route('super-admin.marketplaces.show', $marketplace)
            ->with('success', 'Marketplace created. Complete KYC to approve.');
    }

    public function show(Marketplace $marketplace)
    {
        $marketplace->setConnection('platform');
        $marketplace->load('kyc');

        return Inertia::render('SuperAdmin/Marketplaces/Show', [
            'marketplace' => [
                'id' => $marketplace->id,
                'name' => $marketplace->name,
                'slug' => $marketplace->slug,
                'domain' => $marketplace->domain,
                'email' => $marketplace->email,
                'support_email' => $marketplace->support_email,
                'status' => $marketplace->status,
                'has_tenant_database' => $marketplace->hasTenantDatabase(),
                'approved_at' => $marketplace->approved_at?->toISOString(),
                'rejected_at' => $marketplace->rejected_at?->toISOString(),
                'rejection_reason' => $marketplace->rejection_reason,
                'created_at' => $marketplace->created_at->toISOString(),
                'kyc' => $marketplace->kyc ? [
                    'id' => $marketplace->kyc->id,
                    'legal_name' => $marketplace->kyc->legal_name,
                    'status' => $marketplace->kyc->status,
                    'submitted_at' => $marketplace->kyc->submitted_at?->toISOString(),
                    'reviewed_at' => $marketplace->kyc->reviewed_at?->toISOString(),
                    'rejection_reason' => $marketplace->kyc->rejection_reason,
                ] : null,
            ],
        ]);
    }
}
