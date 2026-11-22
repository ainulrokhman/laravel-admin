<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import RoleForm from '../../Components/RoleForm.vue';

// Create role page; the form posts to the unchanged store endpoint.
defineProps({
    groupedPermissions: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    name: '',
    permissions: [],
});

function submit() {
    form.post('/admin/roles');
}
</script>

<template>
    <Head title="Create Role" />

    <AdminLayout title="Create New Role" :breadcrumb="[{ label: 'Roles', href: '/admin/roles' }, { label: 'Create' }]">
        <div class="row">
            <div class="col-12 col-xl-10 mx-auto">
                <RoleForm
                    :form="form"
                    :grouped-permissions="groupedPermissions"
                    details-hint="Set basic properties of the new role"
                    permissions-title="Assign Permissions"
                    permissions-hint="Grant permissions by toggling specific groups"
                    empty-permissions-text="No permissions exist in the database. Please create permissions first."
                    submit-text="Save Role"
                    @submit="submit" />
            </div>
        </div>
    </AdminLayout>
</template>
