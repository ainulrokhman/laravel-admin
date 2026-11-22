<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import UserForm from '../../Components/UserForm.vue';

// Create user page; the form posts to the unchanged store endpoint and its
// validation errors come back through useForm.
const props = defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
    defaultRole: {
        type: String,
        default: null,
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: props.defaultRole ? [props.defaultRole] : [],
});

function submit() {
    form.post('/admin/users');
}
</script>

<template>
    <Head title="Add User" />

    <AdminLayout title="Add New User" :breadcrumb="[{ label: 'Users', href: '/admin/users' }, { label: 'Create' }]">
        <div class="row">
            <div class="col-12 col-xl-8 mx-auto">
                <UserForm
                    :form="form"
                    :roles="props.roles"
                    :password-required="true"
                    profile-hint="Fill in the basic profile data for the new account"
                    credentials-title="Security Credentials"
                    credentials-hint="Configure user security password"
                    roles-hint="Attach roles to define the user access permissions"
                    empty-roles-text="No roles are created yet. Please create roles first."
                    submit-text="Save User"
                    @submit="submit" />
            </div>
        </div>
    </AdminLayout>
</template>
