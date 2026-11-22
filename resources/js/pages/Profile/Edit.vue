<script setup>
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import FlashAlerts from '../../Components/FlashAlerts.vue';

// Own profile settings, ported from the former admin/profile/edit Blade
// view: avatar upload with live preview, account information, and an
// optional password change in one form.
const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    // The protected seed Super Admin account cannot change its email.
    emailLocked: {
        type: Boolean,
        default: false,
    },
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    avatar: null,
});

// Local preview of a newly chosen photo; falls back to the stored avatar,
// then to the initials disc.
const chosenPreview = ref(null);

const initials = computed(() =>
    props.user.name
        .split(' ')
        .map((segment) => segment.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);

function onAvatarChange(event) {
    const file = event.target.files?.[0] ?? null;
    form.avatar = file;

    if (chosenPreview.value) {
        URL.revokeObjectURL(chosenPreview.value);
        chosenPreview.value = null;
    }

    if (file) {
        chosenPreview.value = URL.createObjectURL(file);
    }
}

function submit() {
    form.put(route('admin.profile.update'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
        },
    });
}
</script>

<template>
    <Head title="My Profile" />

    <AdminLayout title="My Profile Settings" :breadcrumb="[{ label: 'My Profile' }]">
        <div class="row">
            <div class="col-12 col-xl-10 mx-auto">
                <FlashAlerts />

                <form @submit.prevent="submit">
                    <div class="row g-4">
                        <!-- Left Column: Avatar Settings -->
                        <div class="col-12 col-lg-4">
                            <div class="card border-0 shadow-sm text-center p-4">
                                <div class="card-body">
                                    <h5 class="fw-bold mb-3 text-body">Profile Picture</h5>

                                    <div class="mb-4 position-relative d-inline-block">
                                        <img v-if="chosenPreview || props.user.avatar_url"
                                             :src="chosenPreview || props.user.avatar_url"
                                             class="rounded-circle shadow-sm border"
                                             style="width: 140px; height: 140px; object-fit: cover;"
                                             alt="Avatar">
                                        <div v-else
                                             class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold mx-auto border"
                                             style="width: 140px; height: 140px; font-size: 3rem;">
                                            {{ initials }}
                                        </div>
                                    </div>

                                    <p class="text-muted small mb-4">Upload a high-quality JPG, PNG, or WEBP image. Max size: 2MB.</p>

                                    <div>
                                        <label for="avatar" class="btn btn-outline-primary btn-sm px-3 py-2 fw-medium" style="border-radius: 8px; cursor: pointer;">
                                            <i class="bi bi-camera me-1"></i> Choose New Photo
                                        </label>
                                        <input type="file"
                                               class="d-none"
                                               id="avatar"
                                               accept="image/*"
                                               @change="onAvatarChange">
                                        <div v-if="form.errors.avatar" class="text-danger small mt-2">{{ form.errors.avatar }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column: Profile & Credentials -->
                        <div class="col-12 col-lg-8">
                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-transparent border-0 pt-4 px-4">
                                    <h5 class="mb-0 fw-bold text-body">Account Information</h5>
                                    <p class="text-muted small mb-0">Update your core profile information</p>
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
                                                   placeholder="Enter name"
                                                   required>
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
                                                   placeholder="Enter email"
                                                   :readonly="props.emailLocked"
                                                   required>
                                            <div v-if="form.errors.email" class="invalid-feedback">{{ form.errors.email }}</div>
                                            <div v-if="props.emailLocked" class="form-text text-warning small">
                                                <i class="bi bi-info-circle-fill me-1"></i> This is the main seed Super Admin account. Email modifications are disabled.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-sm mb-4">
                                <div class="card-header bg-transparent border-0 pt-4 px-4">
                                    <h5 class="mb-0 fw-bold text-body">Change Password</h5>
                                    <p class="text-muted small mb-0">Leave password fields blank to keep current credentials unchanged</p>
                                </div>
                                <div class="card-body px-4 pb-4">
                                    <div class="row g-3">
                                        <!-- Password Field -->
                                        <div class="col-12 col-md-6">
                                            <label for="password" class="form-label fw-semibold text-secondary">New Password</label>
                                            <input type="password"
                                                   class="form-control"
                                                   :class="{ 'is-invalid': form.errors.password }"
                                                   id="password"
                                                   v-model="form.password"
                                                   placeholder="At least 8 characters">
                                            <div v-if="form.errors.password" class="invalid-feedback">{{ form.errors.password }}</div>
                                        </div>

                                        <!-- Password Confirmation -->
                                        <div class="col-12 col-md-6">
                                            <label for="password_confirmation" class="form-label fw-semibold text-secondary">Confirm New Password</label>
                                            <input type="password"
                                                   class="form-control"
                                                   id="password_confirmation"
                                                   v-model="form.password_confirmation"
                                                   placeholder="Repeat new password">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex align-items-center justify-content-end gap-3 mb-5">
                                <Link :href="route('admin.dashboard')" class="btn btn-light border py-2 px-4 fw-medium" style="border-radius: 8px;">
                                    Cancel
                                </Link>
                                <button type="submit" class="btn btn-primary py-2 px-4 fw-medium" style="border-radius: 8px;" :disabled="form.processing">
                                    Save Settings <i class="bi bi-check-lg ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
