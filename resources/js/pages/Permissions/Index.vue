<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import FlashAlerts from '../../Components/FlashAlerts.vue';
import ServerTable from '../../Components/ServerTable.vue';
import ActionGroup from '../../Components/ActionGroup.vue';
import ActionButton from '../../Components/ActionButton.vue';

// Permission list with the inline create form, ported from the former
// admin/permissions/index Blade view. Creating a permission redirects back
// here; the form's onSuccess bumps the table's refresh key so the new row
// appears without a manual reload.
const page = usePage();
const permissions = computed(() => page.props.auth.permissions ?? []);
const can = (name) => permissions.value.includes(name);

const searchTerm = ref('');
const tableRefreshKey = ref(0);

const columns = [
    { key: 'permission_name', label: 'Permission Name', name: 'name' },
    { key: 'roles_using_this', label: 'Roles Using This', orderable: false, searchable: false },
    { key: 'actions', label: 'Actions', orderable: false, searchable: false, align: 'end' },
];

const createForm = useForm({
    name: '',
});

function submitCreate() {
    createForm.post(route('admin.permissions.store'), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            tableRefreshKey.value++;
        },
    });
}

function deletePermission(row) {
    if (window.confirm('Are you sure you want to delete this permission? This action will remove it from all assigned roles.')) {
        router.delete(row.actions.delete_url, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Permissions" />

    <AdminLayout title="Permission Management" :breadcrumb="[{ label: 'Permissions' }]">
        <div class="row g-4">
            <!-- Permissions Table List -->
            <div :class="can('permission-create') ? 'col-12 col-lg-8' : 'col-12'">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between pt-4 px-4 gap-3">
                        <div>
                            <h5 class="mb-0 fw-bold text-body">Available Permissions</h5>
                            <p class="text-muted small mb-0">System capabilities that can be assigned to roles</p>
                        </div>

                        <!-- Search Form: sized by flex basis, shrinks instead
                             of overflowing when space gets tight -->
                        <form class="position-relative" style="flex: 0 1 220px;" @submit.prevent>
                            <input type="text"
                                   v-model="searchTerm"
                                   class="form-control bg-light bg-opacity-75 border-secondary border-opacity-20 py-2 ps-5 rounded-pill w-100"
                                   placeholder="Search permissions...">
                            <i class="bi bi-search position-absolute text-muted" style="left: 18px; top: 50%; transform: translateY(-50%);"></i>
                            <a v-if="searchTerm" href="#" class="position-absolute text-muted" style="right: 18px; top: 50%; transform: translateY(-50%);" @click.prevent="searchTerm = ''">
                                <i class="bi bi-x-circle-fill"></i>
                            </a>
                        </form>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <FlashAlerts />

                        <ServerTable :url="route('admin.permissions.index')" :columns="columns" :search="searchTerm" :refresh-key="tableRefreshKey">
                            <template #cell-permission_name="{ row }">
                                <div class="d-flex align-items-center">
                                    <div class="bg-info bg-opacity-10 text-info rounded-3 p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="bi bi-key fs-5"></i>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-body d-block">{{ row.permission_name.name }}</span>
                                        <span class="text-muted small">Guard: {{ row.permission_name.guard_name }}</span>
                                    </div>
                                </div>
                            </template>

                            <template #cell-roles_using_this="{ row }">
                                <div class="d-flex flex-wrap gap-1" style="max-width: 250px;">
                                    <span v-for="roleName in row.roles_using_this" :key="roleName" class="badge bg-light text-secondary border rounded-pill py-1 px-2 text-xs">{{ roleName }}</span>
                                    <span v-if="row.roles_using_this.length === 0" class="text-muted small italic">Not assigned</span>
                                </div>
                            </template>

                            <template #cell-actions="{ row }">
                                <ActionGroup>
                                    <ActionButton v-if="row.actions.protected" icon="bi-lock-fill" color="muted" disabled-reason="Core permission is protected" />
                                    <template v-else>
                                        <ActionButton v-if="row.actions.edit_url" :href="row.actions.edit_url" icon="bi-pencil-square" color="primary" label="Edit" />
                                        <ActionButton v-if="row.actions.delete_url" icon="bi-trash" color="danger" label="Delete" @click="deletePermission(row)" />
                                    </template>
                                </ActionGroup>
                            </template>
                        </ServerTable>
                    </div>
                </div>
            </div>

            <!-- Inline Permission Create Form -->
            <div v-if="can('permission-create')" class="col-12 col-lg-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-transparent border-0 pt-4 px-4">
                        <h5 class="mb-0 fw-bold text-body">Create Permission</h5>
                        <p class="text-muted small mb-0">Register a new system action tag</p>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <form @submit.prevent="submitCreate">
                            <div class="mb-3">
                                <label for="name" class="form-label fw-semibold text-secondary">Permission Name</label>
                                <input type="text"
                                       class="form-control"
                                       :class="{ 'is-invalid': createForm.errors.name }"
                                       id="name"
                                       v-model="createForm.name"
                                       placeholder="e.g. publish-article, export-report"
                                       required>
                                <div v-if="createForm.errors.name" class="invalid-feedback">{{ createForm.errors.name }}</div>
                                <div class="form-text text-muted small">
                                    Use lowercase, dashes, and standard action naming conventions (e.g., <code>create-posts</code>, <code>approve-payments</code>).
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2.5 fw-medium d-flex align-items-center justify-content-center gap-2" style="border-radius: 8px;" :disabled="createForm.processing">
                                <i class="bi bi-plus-circle"></i> Add Permission
                            </button>
                        </form>

                        <div class="mt-4 p-3 bg-light bg-opacity-50 rounded-3 border">
                            <h6 class="fw-bold small text-body mb-2"><i class="bi bi-info-circle-fill text-primary me-1"></i> RBAC Guidelines</h6>
                            <p class="text-muted mb-0 small" style="line-height: 1.4;">
                                Permissions represent fine-grained access rules (e.g. <code>manage-users</code>). In Laravel, you protect controllers and routes by validating these keys. Create them here, then assign them inside Role editing layouts.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
