<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import FlashAlerts from '../../Components/FlashAlerts.vue';
import RoleForm from '../../Components/RoleForm.vue';

// Edit role page; the form puts to the unchanged update endpoint. Service
// failures redirect back with an error flash, shown above the form.
const props = defineProps({
    role: {
        type: Object,
        required: true,
    },
    groupedPermissions: {
        type: Object,
        default: () => ({}),
    },
    rolePermissions: {
        type: Array,
        default: () => [],
    },
    nameLocked: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    name: props.role.name,
    permissions: [...props.rolePermissions],
});

function submit() {
    form.put(`/admin/roles/${props.role.id}`);
}
</script>

<template>
    <Head title="Edit Role" />

    <AdminLayout :title="`Edit Role: ${props.role.name}`" :breadcrumb="[{ label: 'Roles', href: '/admin/roles' }, { label: 'Edit' }]">
        <div class="row">
            <div class="col-12 col-xl-10 mx-auto">
                <FlashAlerts />

                <RoleForm
                    :form="form"
                    :grouped-permissions="groupedPermissions"
                    :name-readonly="props.nameLocked"
                    details-hint="Modify basic properties of the role"
                    permissions-title="Configure Permissions"
                    permissions-hint="Assign or revoke specific system abilities"
                    :show-deselect-all="true"
                    empty-permissions-text="No permissions exist in the database."
                    submit-text="Save Changes"
                    @submit="submit" />
            </div>
        </div>
    </AdminLayout>
</template>
