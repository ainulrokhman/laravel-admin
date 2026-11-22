<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import FlashAlerts from '../../Components/FlashAlerts.vue';
import ServerTable from '../../Components/ServerTable.vue';

// User list, ported from the former admin/users/index Blade view. The table
// is fed by the Yajra endpoint on this same URL; cells render structured
// data returned by UserDataTable.
const props = defineProps({
    search: {
        type: String,
        default: '',
    },
});

const page = usePage();
const permissions = computed(() => page.props.auth.permissions ?? []);
const can = (name) => permissions.value.includes(name);

const searchTerm = ref(props.search ?? '');

const columns = [
    { key: 'user_details', label: 'User Details' },
    { key: 'assigned_roles', label: 'Assigned Roles', orderable: false },
    { key: 'joined_date', label: 'Joined Date' },
    { key: 'actions', label: 'Actions', orderable: false, searchable: false, align: 'end', width: '160px' },
];

// Role badge colors, mirroring the server-rendered badges: [color, border opacity]
const roleBadgeStyles = {
    SuperAdmin: ['danger', 20],
    Admin: ['primary', 20],
    User: ['success', 20],
};

function badgeClass(roleName) {
    const [color, opacity] = roleBadgeStyles[roleName] ?? ['secondary', 10];
    return `bg-${color} bg-opacity-10 text-${color} border border-${color} border-opacity-${opacity}`;
}

function deleteUser(row) {
    if (window.confirm(`Are you sure you want to delete user ${row.user_details.name}?`)) {
        router.delete(row.actions.delete_url, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Users" />

    <AdminLayout title="User Management" :breadcrumb="[{ label: 'Users' }]">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 d-flex flex-column flex-md-row align-items-md-center justify-content-between pt-4 px-4 gap-3">
                <div>
                    <h5 class="mb-0 fw-bold text-body">Registered Users</h5>
                    <p class="text-muted small mb-0">Manage system users, assign roles, and audit access credentials</p>
                </div>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <!-- Search Form: fluid width, fills the row on small screens
                         and lets the Add button wrap below instead of clipping -->
                    <form class="position-relative" style="flex: 1 1 220px;" @submit.prevent>
                        <input type="text"
                               v-model="searchTerm"
                               class="form-control bg-light bg-opacity-75 border-secondary border-opacity-20 py-2 ps-5 rounded-pill w-100"
                               placeholder="Search users...">
                        <i class="bi bi-search position-absolute text-muted" style="left: 18px; top: 50%; transform: translateY(-50%);"></i>
                        <a v-if="searchTerm" href="#" class="position-absolute text-muted" style="right: 18px; top: 50%; transform: translateY(-50%);" @click.prevent="searchTerm = ''">
                            <i class="bi bi-x-circle-fill"></i>
                        </a>
                    </form>

                    <Link v-if="can('user-create')" href="/admin/users/create" class="btn btn-primary d-inline-flex align-items-center gap-2 py-2 px-3 fw-medium" style="border-radius: 8px;">
                        <i class="bi bi-person-plus fs-5"></i> Add User
                    </Link>
                </div>
            </div>

            <div class="card-body px-4 pb-4">
                <!-- Alert banners -->
                <FlashAlerts />

                <ServerTable url="/admin/users" :columns="columns" :search="searchTerm">
                    <template #cell-user_details="{ row }">
                        <div class="d-flex align-items-center">
                            <div :class="`bg-${row.user_details.color} text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-3 shadow-sm`" style="width: 44px; height: 44px; font-size: 0.95rem;">
                                {{ row.user_details.initials }}
                            </div>
                            <div>
                                <span class="fw-bold text-body d-block">{{ row.user_details.name }}</span>
                                <span class="text-muted small">{{ row.user_details.email }}</span>
                            </div>
                        </div>
                    </template>

                    <template #cell-assigned_roles="{ row }">
                        <div class="d-flex flex-wrap gap-1">
                            <span v-for="role in row.assigned_roles" :key="role.name" :class="`badge ${badgeClass(role.name)} rounded-pill py-1.5 px-2.5 small fw-semibold`">
                                {{ role.name }}
                            </span>
                            <span v-if="row.assigned_roles.length === 0" class="text-muted small italic">No roles assigned</span>
                        </div>
                    </template>

                    <template #cell-joined_date="{ value }">
                        <span class="text-secondary small">{{ value }}</span>
                    </template>

                    <template #cell-actions="{ row }">
                        <div class="d-flex justify-content-end gap-2">
                            <Link v-if="row.actions.edit_url" :href="row.actions.edit_url" class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" title="Edit User">
                                <i class="bi bi-pencil-square text-primary"></i> Edit
                            </Link>
                            <button v-if="row.actions.delete_url" type="button" class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" title="Delete User" @click="deleteUser(row)">
                                <i class="bi bi-trash text-danger"></i>
                            </button>
                            <button v-else-if="row.actions.delete_disabled_reason" type="button" class="btn btn-sm btn-light border py-1.5 px-2.5 rounded-2" disabled :title="row.actions.delete_disabled_reason">
                                <i class="bi bi-trash-fill text-muted"></i>
                            </button>
                        </div>
                    </template>
                </ServerTable>
            </div>
        </div>
    </AdminLayout>
</template>
