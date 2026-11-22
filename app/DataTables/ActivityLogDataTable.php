<?php

namespace App\DataTables;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogDataTable
{
    /**
     * Build the DataTable response.
     *
     * Same Yajra protocol as before, but the added columns carry structured
     * data instead of pre-rendered HTML so the Vue table renders the cells.
     * The `actions` column carries the details payload the modal displays.
     *
     * @return JsonResponse
     */
    public function make()
    {
        // Select all columns from activity_log and eager load the causer (User)
        $query = Activity::with(['causer', 'subject'])->select('activity_log.*');

        return DataTables::of($query)
            ->addColumn('actor', $this->actorColumn())
            ->addColumn('action', $this->actionColumn())
            ->addColumn('subject', $this->subjectColumn())
            ->addColumn('created_at_formatted', $this->createdAtFormattedColumn())
            ->addColumn('actions', $this->actionsColumn())
            ->filterColumn('actor', function ($query, $keyword) {
                $query->whereHasMorph('causer', [User::class], function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->make(true);
    }

    /**
     * Build the `actor` column data: causer name/email, or null when the
     * activity was not caused by a user (rendered as System / Anonymous).
     */
    private function actorColumn(): \Closure
    {
        return function ($log) {
            if (! $log->causer) {
                return null;
            }

            return [
                'name' => $log->causer->name,
                'email' => $log->causer->email,
            ];
        };
    }

    /**
     * Build the `action` column data: event name plus its badge color
     * (created green, updated blue, deleted red, anything else gray).
     */
    private function actionColumn(): \Closure
    {
        return function ($log) {
            $event = $log->event ?? $log->description;

            $color = match ($event) {
                'created' => 'success',
                'updated' => 'primary',
                'deleted' => 'danger',
                default => 'secondary',
            };

            return [
                'event' => $event,
                'color' => $color,
            ];
        };
    }

    /**
     * Build the `subject` column data: subject class basename, a display
     * name when the subject still exists, and the subject id.
     */
    private function subjectColumn(): \Closure
    {
        return function ($log) {
            if (! $log->subject_type) {
                return null;
            }

            return [
                'class' => class_basename($log->subject_type),
                // Try to get a descriptive name from the subject model if it's not deleted
                'name' => $log->subject ? ($log->subject->name ?? $log->subject->title ?? '') : '',
                'id' => $log->subject_id,
            ];
        };
    }

    /**
     * Build the `created_at_formatted` column data: human-readable relative
     * time plus the exact timestamp for the tooltip.
     */
    private function createdAtFormattedColumn(): \Closure
    {
        return function ($log) {
            return [
                'human' => $log->created_at->diffForHumans(),
                'full' => $log->created_at->toDateTimeString(),
            ];
        };
    }

    /**
     * Build the `actions` column data: everything the details modal shows —
     * the recorded properties plus actor, event, subject, and timestamp.
     */
    private function actionsColumn(): \Closure
    {
        return function ($log) {
            return [
                'properties' => $log->properties ?? [],
                'causer_name' => $log->causer->name ?? 'System',
                'event' => $log->event ?? $log->description,
                'subject_class' => class_basename($log->subject_type ?? 'N/A'),
                'time' => $log->created_at->toDateTimeString(),
            ];
        };
    }
}
