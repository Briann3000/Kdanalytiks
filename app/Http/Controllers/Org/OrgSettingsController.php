<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Traits\LogsOrgAudit;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OrgSettingsController extends Controller
{
    use LogsOrgAudit;

    public function index(Request $request): View
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        return view('organization.settings.index', compact('org'));
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        $request->validate([
            'logo_file' => 'nullable|image|mimes:jpeg,png,jpg,svg,webp,gif|max:2048',
            'logo_url' => 'nullable|string|max:500',
            'brand_color' => 'nullable|string|max:10',
            'enforce_branding' => 'nullable|boolean',
        ]);

        $logoUrl = $org->logo_url;
        if ($request->hasFile('logo_file')) {
            $path = $request->file('logo_file')->store('org_logos', 'public');
            $logoUrl = asset('storage/' . $path);
        } elseif ($request->filled('logo_url') && !str_starts_with($request->logo_url, 'blob:')) {
            $logoUrl = $request->logo_url;
        }

        $org->update([
            'logo_url' => $logoUrl,
            'brand_color' => $request->brand_color ?: '#4f46e5',
            'enforce_branding' => $request->boolean('enforce_branding'),
        ]);

        $this->orgLog('settings.branding_updated', 'Organization', $org->id, [
            'enforce_branding' => $org->enforce_branding,
            'brand_color' => $org->brand_color,
            'logo_url' => $org->logo_url,
        ]);

        return back()->with('success', 'Organization branding settings saved.');
    }

    public function updateApproval(Request $request): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        $org->update([
            'survey_approval_required' => $request->boolean('survey_approval_required'),
        ]);

        $this->orgLog('settings.approval_updated', 'Organization', $org->id, [
            'survey_approval_required' => $org->survey_approval_required,
        ]);

        return back()->with('success', 'Survey approval policy updated.');
    }

    public function updatePii(Request $request): RedirectResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        $org->update([
            'pii_mask_by_default' => $request->boolean('pii_mask_by_default'),
        ]);

        $this->orgLog('settings.pii_updated', 'Organization', $org->id, [
            'pii_mask_by_default' => $org->pii_mask_by_default,
        ]);

        return back()->with('success', 'PII privacy defaults updated.');
    }
}
