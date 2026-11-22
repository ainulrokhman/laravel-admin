<script setup>
import { Link } from '@inertiajs/vue3';

// Shared create/edit user form, ported from the former admin/users Blade
// views (which differed only in labels, password requiredness, and the
// Super Admin account protections). The pages own the useForm state and the
// submit target; this component renders the fields and role checkboxes.
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        default: () => [],
    },
    passwordRequired: {
        type: Boolean,
        default: true,
    },
    emailReadonly: {
        type: Boolean,
        default: false,
    },
    // True when editing the protected Super Admin seed account: its email is
    // locked and its SuperAdmin role checkbox cannot be unticked.
    protectedAccount: {
        type: Boolean,
        default: false,
    },
    lockedRole: {
        type: String,
        default: null,
    },
    profileHint: {
        type: String,
        required: true,
    },
    credentialsTitle: {
        type: String,
        required: true,
    },
    credentialsHint: {
        type: String,
        required: true,
    },
    rolesHint: {
        type: String,
        required: true,
    },
    emptyRolesText: {
        type: String,
        required: true,
    },
    submitText: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['submit']);

function roleDescription(roleName) {
    switch (roleName) {
        case 'SuperAdmin':
            return 'Full unrestricted system admin access (bypasses all checks).';
        case 'Admin':
            return 'Management access to view dashboard, edit users, and manage roles.';
        case 'User':
            return 'Standard user access, limited only to view dashboard details.';
        default:
            return 'Custom role with specific access privileges.';
    }
}
</script>

<template>
    <form @submit.prevent="emit('submit')">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="mb-0 fw-bold text-body">User Profile Information</h5>
                <p class="text-muted small mb-0">{{ props.profileHint }}</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <!-- Name Field -->
                    <div class="col-12">
                        <label for="name" class="form-label fw-semibold text-secondary">Full Name</label>
                        <input type="text"
                               class="form-control"
                               :class="{ 'is-invalid': form.errors.name }"
                               id="name"
                               v-model="form.name"
                               placeholder="John Doe"
                               required
                               autofocus>
                        <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
                    </div>

                    <!-- Email Field -->
                    <div class="col-12">
                        <label for="email" class="form-label fw-semibold text-secondary">Email Address</label>
                        <input type="email"
                               class="form-control"
                               :class="{ 'is-invalid': form.errors.email }"
                               id="email"
                               v-model="form.email"
                               placeholder="johndoe@example.com"
                               required
                               :readonly="props.emailReadonly">
                        <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
                        <div v-if="props.protectedAccount" class="form-text text-warning small">
                            <i class="bi bi-info-circle-fill me-1"></i> This is the main system Super Admin account. The email cannot be modified.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="mb-0 fw-bold text-body">{{ props.credentialsTitle }}</h5>
                <p class="text-muted small mb-0">{{ props.credentialsHint }}</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="row g-3">
                    <!-- Password Field -->
                    <div class="col-12 col-md-6">
                        <label for="password" class="form-label fw-semibold text-secondary">{{ props.passwordRequired ? 'Password' : 'New Password' }}</label>
                        <input type="password"
                               class="form-control"
                               :class="{ 'is-invalid': form.errors.password }"
                               id="password"
                               v-model="form.password"
                               :placeholder="props.passwordRequired ? 'Minimum 8 characters' : 'Leave empty to keep current'"
                               :required="props.passwordRequired">
                        <div v-if="form.errors.password" class="invalid-feedback">{{ form.errors.password }}</div>
                    </div>

                    <!-- Password Confirmation -->
                    <div class="col-12 col-md-6">
                        <label for="password_confirmation" class="form-label fw-semibold text-secondary">{{ props.passwordRequired ? 'Confirm Password' : 'Confirm New Password' }}</label>
                        <input type="password"
                               class="form-control"
                               id="password_confirmation"
                               v-model="form.password_confirmation"
                               :placeholder="props.passwordRequired ? 'Repeat password' : 'Repeat new password'"
                               :required="props.passwordRequired">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="mb-0 fw-bold text-body">Assign System Roles</h5>
                <p class="text-muted small mb-0">{{ props.rolesHint }}</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div v-if="props.roles.length" class="d-flex flex-column gap-3">
                    <div v-for="role in props.roles" :key="role.id" class="card border p-3 rounded-3 bg-light bg-opacity-20">
                        <div class="form-check d-flex align-items-center mb-0">
                            <input class="form-check-input"
                                   type="checkbox"
                                   :value="role.name"
                                   :id="`role-${role.id}`"
                                   v-model="form.roles"
                                   :disabled="props.lockedRole === role.name">
                            <div class="ms-3">
                                <label class="form-check-label fw-bold text-body mb-0" :for="`role-${role.id}`" style="cursor: pointer;">
                                    {{ role.name }}
                                </label>
                                <span v-if="props.lockedRole === role.name" class="badge bg-secondary ms-2 small">System Locked</span>
                                <span class="text-muted d-block small mt-0.5">
                                    {{ roleDescription(role.name) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-3">
                    <i class="bi bi-shield-slash fs-3 text-muted mb-2"></i>
                    <p class="text-muted small mb-0">{{ props.emptyRolesText }}</p>
                </div>
                <div v-if="form.errors.roles" class="text-danger small mt-2">{{ form.errors.roles }}</div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex align-items-center justify-content-end gap-3 mb-5">
            <Link href="/admin/users" class="btn btn-light border py-2 px-4 fw-medium" style="border-radius: 8px;">
                Cancel
            </Link>
            <button type="submit" class="btn btn-primary py-2 px-4 fw-medium" style="border-radius: 8px;" :disabled="form.processing">
                {{ props.submitText }} <i class="bi bi-check-lg ms-1"></i>
            </button>
        </div>
    </form>
</template>
