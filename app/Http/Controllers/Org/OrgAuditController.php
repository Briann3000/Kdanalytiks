<?php

namespace App\Http\Controllers\Org;

use App\Http\Controllers\Controller;
use App\Models\OrgAuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrgAuditController extends Controller
{
    public function index(Request $request): View
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        $query = OrgAuditLog::where('organization_id', $org->id)->with('user')->latest();

        if ($request->filled('category')) {
            $cat = $request->category;
            if ($cat === 'team') {
                $query->where(function ($q) {
                    $q->where('action', 'like', 'member.%')->orWhere('action', 'like', 'invitation.%');
                });
            } elseif ($cat === 'surveys') {
                $query->where('action', 'like', 'survey.%');
            } elseif ($cat === 'fieldwork') {
                $query->where(function ($q) {
                    $q->where('action', 'like', 'fieldwork.%')->orWhere('action', 'like', 'assignment.%');
                });
            } elseif ($cat === 'settings') {
                $query->where('action', 'like', 'settings.%');
            }
        } elseif ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        $logs = $query->paginate(25);

        return view('organization.audit.index', compact('org', 'logs'));
    }

    public function export(Request $request): StreamedResponse
    {
        $org = $request->attributes->get('active_org') ?? auth()->user()->activeOrganization();

        $logs = OrgAuditLog::where('organization_id', $org->id)->with('user')->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="org_audit_logs_' . date('Y_m_d') . '.csv"',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Timestamp', 'User Name', 'User Email', 'Action', 'Target Type', 'Target ID', 'IP Address', 'Metadata']);

            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at ? $log->created_at->toDateTimeString() : '',
                    $log->user->name ?? 'System',
                    $log->user->email ?? 'N/A',
                    $log->action,
                    $log->target_type ?: 'N/A',
                    $log->target_id ?: 'N/A',
                    $log->ip_address ?: 'N/A',
                    is_array($log->metadata) ? json_encode($log->metadata) : $log->metadata,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
