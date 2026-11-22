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

        if ($request->ajax()) {
            return $dataTable->make();
        }

        return Inertia::render('ActivityLogs/Index');
    }
}
