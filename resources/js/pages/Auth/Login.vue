<script setup>
import { onMounted, onUnmounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import ThemeToggle from '../../Components/ThemeToggle.vue';

// Login page, ported from the former auth/login Blade view: same AdminLTE
// markup, form state and validation errors handled by Inertia's useForm.
const page = usePage();

defineProps({
    demoAccounts: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post(route('login'));
}

function quickLogin(account) {
    form.email = account.email;
    form.password = account.password;
    submit();
}

onMounted(() => {
    document.body.classList.add('login-page', 'bg-body-secondary');
});

onUnmounted(() => {
    document.body.classList.remove('login-page', 'bg-body-secondary');
});
</script>

<template>
    <Head title="Login" />

    <!-- Floating Theme Toggle -->
    <ThemeToggle variant="floating" />

    <div class="login-box">
        <div class="login-logo mb-4">
            <a :href="route('home')" class="text-decoration-none d-flex align-items-center justify-content-center">
                <i class="bi bi-cpu-fill text-primary me-2 fs-2"></i>
                <span class="fw-bold text-body">Laravel</span> <span class="text-primary ms-1 fw-semibold">Admin</span>
            </a>
        </div>

        <div class="card card-outline card-primary shadow-lg border-0">
            <div class="card-body login-card-body p-4">
                <p class="login-box-msg text-muted mb-4 small">Sign in to start your session</p>

                <div v-if="page.props.flash.success" class="alert alert-success bg-success bg-opacity-10 text-success border-0 rounded-3 mb-4 small" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> {{ page.props.flash.success }}
                </div>

                <form @submit.prevent="submit">
                    <!-- Email Input -->
                    <div class="input-group mb-3">
                        <div class="form-floating">
                            <input type="email"
                                   class="form-control"
                                   :class="{ 'is-invalid': form.errors.email }"
                                   id="email"
                                   v-model="form.email"
                                   placeholder="name@example.com"
                                   required
                                   autocomplete="email"
                                   autofocus>
                            <label for="email">Email Address</label>
                        </div>
                        <div class="input-group-text bg-body-tertiary border-start-0 text-muted">
                            <span class="bi bi-envelope"></span>
                        </div>
                        <div v-if="form.errors.email" class="invalid-feedback d-block w-100 mt-1">
                            {{ form.errors.email }}
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="input-group mb-4">
                        <div class="form-floating">
                            <input type="password"
                                   class="form-control"
                                   :class="{ 'is-invalid': form.errors.password }"
                                   id="password"
                                   v-model="form.password"
                                   placeholder="Password"
                                   required
                                   autocomplete="current-password">
                            <label for="password">Password</label>
                        </div>
                        <div class="input-group-text bg-body-tertiary border-start-0 text-muted">
                            <span class="bi bi-lock-fill"></span>
                        </div>
                        <div v-if="form.errors.password" class="invalid-feedback d-block w-100 mt-1">
                            {{ form.errors.password }}
                        </div>
                    </div>

                    <!-- Remember Me & Submit Row -->
                    <div class="row align-items-center mb-3">
                        <div class="col-8">
                            <div class="form-check mb-0">
                                <input class="form-check-input" type="checkbox" id="remember" v-model="form.remember" style="cursor: pointer;">
                                <label class="form-check-label text-muted small" for="remember" style="cursor: pointer; user-select: none;">
                                    Remember Me
                                </label>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" :disabled="form.processing">Sign In</button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Quick testing accounts option panel -->
                <div v-if="demoAccounts.length" class="mt-4 pt-3 border-top">
                    <p class="text-uppercase text-center text-muted fw-bold mb-3" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                        Quick Testing Accounts
                    </p>
                    <div class="d-flex flex-column gap-2">
                        <button v-for="account in demoAccounts" :key="account.email" type="button" class="btn btn-outline-secondary btn-sm text-start d-flex justify-content-between align-items-center px-3 py-2" @click="quickLogin(account)">
                            <span><strong :class="`text-${account.color}`">{{ account.label }}</strong> <span class="text-muted small ms-1">({{ account.note }})</span></span>
                            <i class="bi bi-arrow-right-short text-muted"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
