<script setup>
import { usePage } from '@inertiajs/vue3';

// Success/error banners fed by the shared flash props, with the same markup
// the Blade pages rendered from the session.
const page = usePage();
</script>

<template>
    <div>
        <div v-if="page.props.flash.success" class="alert alert-success bg-success bg-opacity-10 text-success border-0 rounded-3 mb-4 p-3 d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div>{{ page.props.flash.success }}</div>
        </div>

        <div v-if="page.props.flash.error" class="alert alert-danger bg-danger bg-opacity-10 text-danger border-0 rounded-3 mb-4 p-3 d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div>{{ page.props.flash.error }}</div>
        </div>
    </div>
</template>
