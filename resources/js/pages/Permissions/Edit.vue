<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '../../Layouts/AdminLayout.vue';

// Edit permission page, ported from the former admin/permissions/edit Blade
// view. Protected core permissions never reach this page (the controller
// redirects them away with an error).
const props = defineProps({
    permission: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.permission.name,
});

function submit() {
    form.put(route('admin.permissions.update', props.permission.id));
}
</script>

<template>
    <Head title="Edit Permission" />

    <AdminLayout :title="`Edit Permission: ${props.permission.name}`" :breadcrumb="[{ label: 'Permissions', href: route('admin.permissions.index') }, { label: 'Edit' }]">
        <div class="row">
            <div class="col-12 col-md-8 col-lg-6 mx-auto">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h5 class="mb-0 fw-bold text-body">Modify Permission Details</h5>
                        <p class="text-muted small mb-0">Update the name key of the system capability</p>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <form @submit.prevent="submit">
                            <div class="mb-4">
                                <label for="name" class="form-label fw-semibold text-secondary">Permission Name</label>
                                <input type="text"
                                       class="form-control"
                                       :class="{ 'is-invalid': form.errors.name }"
                                       id="name"
                                       v-model="form.name"
                                       placeholder="e.g. publish-article"
                                       required
                                       autofocus>
                                <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
                                <div class="form-text text-muted small mt-2">
                                    Warning: Changing permission names might break existing <code>@can</code> gates or middleware rules referencing the old name <code>"{{ props.permission.name }}"</code>.
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-end gap-3">
                                <Link :href="route('admin.permissions.index')" class="btn btn-light border py-2 px-4 fw-medium" style="border-radius: 8px;">
                                    Cancel
                                </Link>
                                <button type="submit" class="btn btn-primary py-2 px-4 fw-medium" style="border-radius: 8px;" :disabled="form.processing">
                                    Save Changes <i class="bi bi-check-lg ms-1"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
