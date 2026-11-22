<?php

namespace App\Support;

/**
 * Standard action buttons for DataTables action columns.
 *
 * One icon-only Bootstrap button group with built-in Bootstrap tooltips —
 * the same standard the SPA edition renders through its ActionGroup and
 * ActionButton Vue components. Every button carries btn-sm itself: the
 * group's small sizing only reaches direct children (.btn-group-sm > .btn),
 * and the delete form and the locked-state wrapper sit between the group
 * and their button.
 */
class TableActions
{
    public static function openGroup(): string
    {
        return '<div class="btn-group btn-group-sm" role="group" aria-label="Actions">';
    }

    public static function closeGroup(): string
    {
        return '</div>';
    }

    public static function link(string $url, string $icon, string $color, string $label): string
    {
        return '<a href="'.e($url).'" class="btn btn-sm btn-light border" data-bs-toggle="tooltip" data-bs-placement="top" title="'.e($label).'"><i class="bi '.$icon.' text-'.$color.'"></i></a>';
    }

    public static function button(string $icon, string $color, string $label, string $extraClasses = '', string $extraAttributes = ''): string
    {
        $classes = 'btn btn-sm btn-light border'.($extraClasses !== '' ? ' '.$extraClasses : '');

        return '<button type="button" class="'.$classes.'" data-bs-toggle="tooltip" data-bs-placement="top" title="'.e($label).'"'.$extraAttributes.'><i class="bi '.$icon.' text-'.$color.'"></i></button>';
    }

    public static function deleteForm(string $url, string $confirmMessage): string
    {
        $html = '<form action="'.e($url).'" method="POST" class="d-inline-flex" onsubmit="return confirm(\''.e($confirmMessage).'\')">';
        $html .= csrf_field().method_field('DELETE');
        $html .= '<button type="submit" class="btn btn-sm btn-light border" data-bs-toggle="tooltip" data-bs-placement="top" title="Delete"><i class="bi bi-trash text-danger"></i></button>';
        $html .= '</form>';

        return $html;
    }

    /**
     * A protected row: a disabled lock button whose wrapper carries the
     * tooltip — a disabled button cannot trigger a Bootstrap tooltip itself.
     */
    public static function locked(string $reason): string
    {
        return '<span class="d-inline-block" data-bs-toggle="tooltip" data-bs-placement="top" title="'.e($reason).'" tabindex="0">'
            .'<button type="button" class="btn btn-sm btn-light border" disabled><i class="bi bi-lock-fill text-muted"></i></button>'
            .'</span>';
    }
}
