<script setup>
/**
 * ActionButton — one icon-only button inside an ActionGroup.
 *
 * The label is shown as a built-in Bootstrap tooltip (initialised by the
 * parent ActionGroup). With `disabledReason` the button renders disabled
 * with a lock icon, wrapped in a span that carries the tooltip — a
 * disabled button cannot trigger a Bootstrap tooltip by itself.
 *
 * Every variant carries btn-sm itself: the group's small sizing only
 * reaches direct children (.btn-group-sm > .btn), and the disabled
 * variant sits inside its tooltip wrapper, so it must size itself.
 */
import { Link } from '@inertiajs/vue3';

defineProps({
    href: { type: String, default: null },
    icon: { type: String, required: true },
    color: { type: String, default: 'primary' },
    label: { type: String, default: '' },
    disabledReason: { type: String, default: null },
});

defineEmits(['click']);
</script>

<template>
    <span v-if="disabledReason"
          class="d-inline-block"
          data-bs-toggle="tooltip"
          data-bs-placement="top"
          :title="disabledReason"
          tabindex="0">
        <button type="button" class="btn btn-sm btn-light border" disabled>
            <i :class="`bi ${icon} text-${color}`"></i>
        </button>
    </span>

    <Link v-else-if="href"
          :href="href"
          class="btn btn-sm btn-light border"
          data-bs-toggle="tooltip"
          data-bs-placement="top"
          :title="label">
        <i :class="`bi ${icon} text-${color}`"></i>
    </Link>

    <button v-else
            type="button"
            class="btn btn-sm btn-light border"
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            :title="label"
            @click="$emit('click')">
        <i :class="`bi ${icon} text-${color}`"></i>
    </button>
</template>
