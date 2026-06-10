<?php

namespace App\DataTables;

use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogDataTable
{
    /**
     * Build the DataTable response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function make()
    {
        // Select all columns from activity_log and eager load the causer (User)
        $query = Activity::with(['causer', 'subject'])->select('activity_log.*');

        return DataTables::of($query)
            ->addColumn('actor', function ($log) {
                if ($log->causer) {
                    return '<div><span class="fw-bold text-body d-block">' . e($log->causer->name) . '</span><span class="text-muted small">' . e($log->causer->email) . '</span></div>';
                }
                return '<span class="text-muted italic">System / Anonymous</span>';
            })
            ->addColumn('action', function ($log) {
                $event = $log->event ?? $log->description;
                $badgeClass = 'bg-secondary';
                
                if ($event === 'created') {
                    $badgeClass = 'bg-success';
                } elseif ($event === 'updated') {
                    $badgeClass = 'bg-primary';
                } elseif ($event === 'deleted') {
                    $badgeClass = 'bg-danger';
                }

                return '<span class="badge ' . $badgeClass . ' bg-opacity-10 text-' . str_replace('bg-', '', $badgeClass) . ' border border-' . str_replace('bg-', '', $badgeClass) . ' border-opacity-20 px-2.5 py-1.5 small fw-semibold text-uppercase">' . e($event) . '</span>';
            })
            ->addColumn('subject', function ($log) {
                if ($log->subject_type) {
                    $class = class_basename($log->subject_type);
                    $subjectName = '';

                    // Try to get a descriptive name from the subject model if it's not deleted
                    if ($log->subject) {
                        $subjectName = $log->subject->name ?? $log->subject->title ?? '';
                    }

                    $display = '<span class="fw-medium text-body">' . e($class) . '</span>';
                    if ($subjectName) {
                        $display .= ' <span class="text-muted small">(' . e($subjectName) . ')</span>';
                    }
                    $display .= ' <span class="badge bg-light text-secondary border ms-1 small">ID: ' . $log->subject_id . '</span>';
                    
                    return $display;
                }
                return '<span class="text-muted small">-</span>';
            })
            ->addColumn('created_at_formatted', function ($log) {
                return '<span class="text-secondary small" title="' . $log->created_at->toDateTimeString() . '">' . $log->created_at->diffForHumans() . '</span>';
            })
            ->addColumn('actions', function ($log) {
                // Encode properties to safely pass as JSON attribute
                $propertiesJson = json_encode($log->properties ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                
                return '<div class="d-flex justify-content-end">
                    <button class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2 view-details-btn" 
                            data-id="' . $log->id . '" 
                            data-properties=\'' . $propertiesJson . '\' 
                            data-causer="' . e($log->causer->name ?? 'System') . '" 
                            data-event="' . e($log->event ?? $log->description) . '" 
                            data-subject="' . e(class_basename($log->subject_type ?? 'N/A')) . '"
                            data-time="' . $log->created_at->toDateTimeString() . '"
                            title="View Properties">
                        <i class="bi bi-eye text-primary me-1"></i> Details
                    </button>
                </div>';
            })
            ->filterColumn('actor', function ($query, $keyword) {
                $query->whereHasMorph('causer', [\App\Models\User::class], function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                      ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->rawColumns(['actor', 'action', 'subject', 'created_at_formatted', 'actions'])
            ->make(true);
    }
}
