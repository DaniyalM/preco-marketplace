<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Services\StatelessAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function __construct(
        protected StatelessAuthService $authService
    ) {}

    public function index(Request $request)
    {
        $vendor = $this->authService->getVendor($request);

        if (!$vendor) {
            return redirect()->route('vendor.onboarding');
        }

        return Inertia::render('Vendor/Settings', [
            'vendor' => [
                'id' => $vendor->id,
                'business_name' => $vendor->business_name,
                'slug' => $vendor->slug,
                'business_type' => $vendor->business_type,
                'email' => $vendor->email,
                'phone' => $vendor->phone,
                'description' => $vendor->description,
                'logo' => $vendor->logo,
                'banner' => $vendor->banner,
                'website' => $vendor->website,
                'address' => [
                    'line1' => $vendor->address_line_1,
                    'line2' => $vendor->address_line_2,
                    'city' => $vendor->city,
                    'state' => $vendor->state,
                    'postal_code' => $vendor->postal_code,
                    'country' => $vendor->country,
                ],
                'settings' => $vendor->settings ?? [],
                'status' => $vendor->status,
                'commission_rate' => $vendor->commission_rate,
            ],
        ]);
    }

    public function updateProfile(Request $request)
    {
        $vendor = $this->authService->getVendor($request);

        $validated = $request->validate([
            'business_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:2000',
            'website' => 'nullable|url|max:255',
        ]);

        $vendor->update($validated);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updateAddress(Request $request)
    {
        $vendor = $this->authService->getVendor($request);

        $validated = $request->validate([
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
        ]);

        $vendor->update($validated);

        return back()->with('success', 'Address updated successfully.');
    }

    public function updateLogo(Request $request)
    {
        $vendor = $this->authService->getVendor($request);

        $validated = $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ]);

        // Delete old logo if exists
        if ($vendor->logo) {
            Storage::disk('public')->delete($vendor->logo);
        }

        $path = $request->file('logo')->store('vendors/logos', 'public');
        $vendor->update(['logo' => $path]);

        return back()->with('success', 'Logo updated successfully.');
    }

    public function updateBanner(Request $request)
    {
        $vendor = $this->authService->getVendor($request);

        $validated = $request->validate([
            'banner' => 'required|image|mimes:jpeg,png,webp|max:5120',
        ]);

        // Delete old banner if exists
        if ($vendor->banner) {
            Storage::disk('public')->delete($vendor->banner);
        }

        $path = $request->file('banner')->store('vendors/banners', 'public');
        $vendor->update(['banner' => $path]);

        return back()->with('success', 'Banner updated successfully.');
    }

    public function updateSettings(Request $request)
    {
        $vendor = $this->authService->getVendor($request);

        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.notifications' => 'nullable|array',
            'settings.notifications.email_orders' => 'nullable|boolean',
            'settings.notifications.email_reviews' => 'nullable|boolean',
            'settings.notifications.email_low_stock' => 'nullable|boolean',
            'settings.display' => 'nullable|array',
            'settings.display.show_email' => 'nullable|boolean',
            'settings.display.show_phone' => 'nullable|boolean',
        ]);

        $vendor->update(['settings' => $validated['settings']]);

        return back()->with('success', 'Settings updated successfully.');
    }
}
