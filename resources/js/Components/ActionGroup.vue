<script setup>
/**
 * ActionGroup — the standard button group for table action columns.
 *
 * Renders a Bootstrap btn-group and owns the lifecycle of the built-in
 * Bootstrap tooltips inside it: rows are re-rendered on every page/search
 * change, so tooltips are (re)initialised after each render of the group
 * and disposed before the group is replaced or removed.
 */
import { ref, onMounted, onUpdated, onBeforeUnmount } from 'vue';
import { Tooltip } from 'bootstrap';

const root = ref(null);
let tooltips = [];

function disposeTooltips() {
    tooltips.forEach((tooltip) => tooltip.dispose());
    tooltips = [];
}

function initTooltips() {
    disposeTooltips();

    if (!root.value) {
        return;
    }

    root.value
        .querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach((element) => tooltips.push(new Tooltip(element)));
}

onMounted(initTooltips);
onUpdated(initTooltips);
onBeforeUnmount(disposeTooltips);
</script>

<template>
    <div ref="root" class="btn-group btn-group-sm" role="group" aria-label="Actions">
        <slot />
    </div>
</template>
