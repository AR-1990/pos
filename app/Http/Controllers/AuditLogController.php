<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AuditLog::with(['user:id,name', 'store:id,name'])
                ->when($request->action, fn ($q, $a) => $q->where('action', 'like', $a . '%'))
                ->when($request->start_date, fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                ->when($request->end_date, fn ($q, $d) => $q->whereDate('created_at', '<=', $d))
                ->latest('id');

            return DataTables::of($query)
                ->editColumn('created_at', fn ($row) => $row->created_at?->format('d M Y, h:i A'))
                ->addColumn('user_name', fn ($row) => e($row->user->name ?? 'System'))
                ->addColumn('store_name', fn ($row) => e($row->store->name ?? '—'))
                ->editColumn('action', function ($row) {
                    $color = str_contains($row->action, 'void') || str_contains($row->action, 'delete') ? 'danger'
                        : (str_contains($row->action, 'return') ? 'warning' : 'primary');
                    return '<span class="badge bg-' . $color . '">' . e($row->action) . '</span>';
                })
                ->editColumn('description', fn ($row) => e($row->description))
                ->rawColumns(['action'])
                ->make(true);
        }

        $actions = AuditLog::query()->select('action')->distinct()->orderBy('action')->pluck('action');

        return view('audit-logs', ['pageTitle' => 'Audit Log', 'actions' => $actions]);
    }
}
