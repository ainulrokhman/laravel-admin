<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\DataTables\ActivityLogDataTable;
use Inertia\Inertia;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the activity logs.
     */
    public function index(Request $request, ActivityLogDataTable $dataTable)
    {
        Gate::authorize('activity-log-list');

        // Inertia visits are XHR too (the client sends X-Requested-With),
        // so the table feed must only answer plain AJAX requests; otherwise
        // SPA navigation to this page would receive table JSON.
        if ($request->ajax() && ! $request->header('X-Inertia')) {
            return $dataTable->make();
        }

        return Inertia::render('ActivityLogs/Index');
    }
}
