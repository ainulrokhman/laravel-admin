<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import FlashAlerts from '../../Components/FlashAlerts.vue';
import UserForm from '../../Components/UserForm.vue';

// Edit user page; the form puts to the unchanged update endpoint. Service
// failures redirect back with an error flash, shown above the form.
const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        default: () => [],
    },
    userRoles: {
        type: Array,
        default: () => [],
    },
    protectedAccount: {
        type: Boolean,
        default: false,
    },
    protectedRole: {
        type: String,
        default: null,
    },
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    roles: [...props.userRoles],
});

function submit() {
    form.put(`/admin/users/${props.user.id}`);
}
</script>

<template>
    <Head title="Edit User" />

    <AdminLayout :title="`Edit User: ${props.user.name}`" :breadcrumb="[{ label: 'Users', href: '/admin/users' }, { label: 'Edit' }]">
        <div class="row">
            <div class="col-12 col-xl-8 mx-auto">
                <FlashAlerts />

                <UserForm
                    :form="form"
                    :roles="props.roles"
                    :password-required="false"
                    :email-readonly="props.protectedAccount"
                    :protected-account="props.protectedAccount"
                    :locked-role="props.protectedAccount ? props.protectedRole : null"
                    profile-hint="Update the profile data for this account"
                    credentials-title="Update Credentials"
                    credentials-hint="Leave password fields blank to keep current credentials unchanged"
                    roles-hint="Select roles to control user permissions"
                    empty-roles-text="No roles are created yet."
                    submit-text="Save Changes"
                    @submit="submit" />
            </div>
        </div>
    </AdminLayout>
</template>
