<script setup>
import { computed, onMounted, onUnmounted, reactive, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import ThemeToggle from '../Components/ThemeToggle.vue';

// AdminLTE 4 shell as a Vue layout. Same markup and classes as the former
// Blade layout; the treeview groups and the sidebar toggle are driven by Vue
// state and the body classes AdminLTE's CSS expects, without jQuery.
const props = defineProps({
    title: {
        type: String,
        default: '',
    },
    // Items after "Home": [{ label, href }] — the last item (or one without
    // an href) renders as the active breadcrumb.
    breadcrumb: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const permissions = computed(() => page.props.auth.permissions ?? []);

const can = (name) => permissions.value.includes(name);
const canAny = (names) => names.some((name) => can(name));

const path = computed(() => page.url.split('?')[0]);
const dashboardActive = computed(() => route().current('admin.dashboard'));
const usersActive = computed(() => route().current('admin.users.*'));
const rolesActive = computed(() => route().current('admin.roles.*'));
const permissionsActive = computed(() => route().current('admin.permissions.*'));
const logsActive = computed(() => route().current('admin.activity-logs.*'));
const aksesActive = computed(() => usersActive.value || rolesActive.value || permissionsActive.value);
const sistemActive = computed(() => logsActive.value);

// A group starts open when the current page lives inside it, and re-syncs on
// every Inertia visit, mirroring the server-rendered menu-open behavior.
const groups = reactive({ akses: false, sistem: false });

function syncGroupsFromUrl() {
    if (aksesActive.value) groups.akses = true;
    if (sistemActive.value) groups.sistem = true;
}

watch(path, syncGroupsFromUrl);

function toggleSidebar() {
    if (window.innerWidth >= 992) {
        document.body.classList.toggle('sidebar-collapse');
    } else {
        document.body.classList.toggle('sidebar-open');
    }
}

const initials = computed(() =>
    (user.value?.name ?? 'U')
        .split(' ')
        .map((segment) => segment.charAt(0))
        .slice(0, 2)
        .join('')
        .toUpperCase(),
);

const avatarUrl = computed(() => (user.value?.avatar ? `/storage/${user.value.avatar}` : null));

onMounted(() => {
    document.body.classList.add('layout-fixed', 'sidebar-expand-lg', 'bg-body-tertiary');
    syncGroupsFromUrl();
});

onUnmounted(() => {
    document.body.classList.remove('layout-fixed', 'sidebar-expand-lg', 'bg-body-tertiary', 'sidebar-collapse', 'sidebar-open');
});
</script>

<template>
    <div class="app-wrapper">
        <!-- Header Navbar -->
        <nav class="app-header navbar navbar-expand bg-body border-bottom">
            <div class="container-fluid">
                <!-- Left navbar links -->
                <ul class="navbar-nav align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#" role="button" @click.prevent="toggleSidebar">
                            <i class="bi bi-list fs-4"></i>
                        </a>
                    </li>
                    <li class="nav-item d-none d-md-inline-block">
                        <Link :href="route('admin.dashboard')" class="nav-link fs-6 fw-medium">Home</Link>
                    </li>
                </ul>

                <!-- Right navbar links -->
                <ul class="navbar-nav ms-auto align-items-center">
                    <!-- Color Mode Selector -->
                    <ThemeToggle variant="nav" />

                    <!-- Notifications Dropdown -->
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link px-2 position-relative" data-bs-toggle="dropdown" href="#">
                            <i class="bi bi-bell fs-5"></i>
                            <span class="position-absolute top-1 start-100 translate-middle badge rounded-pill bg-danger border border-light" style="font-size: 0.65rem;">
                                3
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow border-0">
                            <span class="dropdown-item dropdown-header fw-bold">3 Notifications</span>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item">
                                <i class="bi bi-envelope me-2 text-primary"></i> 1 new message
                                <span class="float-end text-muted text-sm">3 mins</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item">
                                <i class="bi bi-people me-2 text-success"></i> 2 user registrations
                                <span class="float-end text-muted text-sm">12 hours</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item dropdown-footer text-center">See All Notifications</a>
                        </div>
                    </li>

                    <!-- User Menu Dropdown -->
                    <li class="nav-item dropdown user-menu">
                        <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown">
                            <img v-if="avatarUrl" :src="avatarUrl" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;" alt="Avatar">
                            <div v-else class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold me-2" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                {{ initials }}
                            </div>
                            <span class="d-none d-md-inline fw-semibold text-body">{{ user?.name ?? 'User' }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end shadow border-0">
                            <!-- User image -->
                            <li class="user-header bg-primary text-white text-center py-4 rounded-top">
                                <img v-if="avatarUrl" :src="avatarUrl" class="rounded-circle shadow mb-2 border border-white border-2" style="width: 64px; height: 64px; object-fit: cover;" alt="Avatar">
                                <div v-else class="bg-white text-primary rounded-circle d-inline-flex align-items-center justify-content-center fw-bold mb-2 shadow" style="width: 64px; height: 64px; font-size: 1.5rem;">
                                    {{ initials }}
                                </div>
                                <p class="mb-0 fw-bold">
                                    {{ user?.name ?? 'User' }}
                                </p>
                                <small>Role: {{ user?.role ?? 'None' }}</small>
                            </li>
                            <!-- Menu Body -->
                            <li class="user-body p-3">
                                <div class="d-flex flex-column gap-2 text-center">
                                    <Link :href="route('admin.profile.edit')" class="btn btn-outline-primary btn-sm w-100">
                                        <i class="bi bi-person-gear me-1"></i> My Profile
                                    </Link>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <Link :href="route('admin.dashboard')" class="btn btn-light btn-sm w-100 border">Home</Link>
                                        </div>
                                        <div class="col-6">
                                            <Link :href="route('logout')" class="btn btn-danger btn-sm w-100">Logout</Link>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Sidebar -->
        <aside class="app-sidebar bg-body-secondary shadow border-end">
            <!-- Sidebar Brand -->
            <div class="sidebar-brand d-flex align-items-center px-4 py-3 justify-content-between border-bottom">
                <Link :href="route('admin.dashboard')" class="brand-link text-decoration-none d-flex align-items-center">
                    <i class="bi bi-cpu-fill fs-4 me-2"></i>
                    <span class="brand-text fw-bold">Laravel Admin</span>
                </Link>
            </div>

            <!-- Sidebar Menu -->
            <div class="sidebar-wrapper py-3">
                <nav class="mt-2">
                    <ul class="nav sidebar-menu flex-column" role="menu">
                        <!-- Menu utama -->
                        <li v-if="can('view-dashboard')" class="nav-item">
                            <Link :href="route('admin.dashboard')" class="nav-link" :class="dashboardActive ? 'active bg-primary text-white' : 'text-body'">
                                <i class="nav-icon bi bi-speedometer2 me-2"></i>
                                <p>Dashboard</p>
                            </Link>
                        </li>

                        <!-- Submenu: Users & Access -->
                        <li v-if="canAny(['user-list', 'role-list', 'permission-list'])" class="nav-item" :class="{ 'menu-open': groups.akses }">
                            <a href="#" class="nav-link" :class="aksesActive ? 'active bg-primary text-white' : 'text-body'" @click.prevent="groups.akses = !groups.akses">
                                <i class="nav-icon bi bi-people me-2"></i>
                                <p>Users &amp; Access <i class="nav-arrow bi bi-chevron-right"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li v-if="can('user-list')" class="nav-item">
                                    <Link :href="route('admin.users.index')" class="nav-link" :class="usersActive ? 'active bg-primary text-white' : 'text-body'">
                                        <i class="nav-icon bi bi-people me-2"></i>
                                        <p>Users</p>
                                    </Link>
                                </li>
                                <li v-if="can('role-list')" class="nav-item">
                                    <Link :href="route('admin.roles.index')" class="nav-link" :class="rolesActive ? 'active bg-primary text-white' : 'text-body'">
                                        <i class="nav-icon bi bi-shield-lock me-2"></i>
                                        <p>Roles</p>
                                    </Link>
                                </li>
                                <li v-if="can('permission-list')" class="nav-item">
                                    <Link :href="route('admin.permissions.index')" class="nav-link" :class="permissionsActive ? 'active bg-primary text-white' : 'text-body'">
                                        <i class="nav-icon bi bi-key me-2"></i>
                                        <p>Permissions</p>
                                    </Link>
                                </li>
                            </ul>
                        </li>

                        <!-- Submenu: System -->
                        <li v-if="canAny(['activity-log-list', 'manage-settings'])" class="nav-item" :class="{ 'menu-open': groups.sistem }">
                            <a href="#" class="nav-link" :class="sistemActive ? 'active bg-primary text-white' : 'text-body'" @click.prevent="groups.sistem = !groups.sistem">
                                <i class="nav-icon bi bi-gear me-2"></i>
                                <p>System <i class="nav-arrow bi bi-chevron-right"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li v-if="can('activity-log-list')" class="nav-item">
                                    <Link :href="route('admin.activity-logs.index')" class="nav-link" :class="logsActive ? 'active bg-primary text-white' : 'text-body'">
                                        <i class="nav-icon bi bi-journal-text me-2"></i>
                                        <p>Activity Logs</p>
                                    </Link>
                                </li>
                                <li v-if="can('manage-settings')" class="nav-item">
                                    <a href="#" class="nav-link text-body" @click.prevent>
                                        <i class="nav-icon bi bi-gear me-2"></i>
                                        <p>Settings</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="app-main py-4">
            <!-- Content Header -->
            <div class="app-content-header mb-4">
                <div class="container-fluid">
                    <div class="row align-items-center">
                        <div class="col-sm-6">
                            <h3 class="mb-0 fw-bold">{{ props.title }}</h3>
                        </div>
                        <div class="col-sm-6">
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb float-sm-end mb-0">
                                    <li class="breadcrumb-item"><Link :href="route('admin.dashboard')" class="text-decoration-none">Home</Link></li>
                                    <template v-for="(item, index) in props.breadcrumb" :key="index">
                                        <li v-if="item.href && index < props.breadcrumb.length - 1" class="breadcrumb-item">
                                            <Link :href="item.href" class="text-decoration-none">{{ item.label }}</Link>
                                        </li>
                                        <li v-else class="breadcrumb-item active" aria-current="page">{{ item.label }}</li>
                                    </template>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="app-content">
                <div class="container-fluid">
                    <slot />
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="app-footer border-top bg-body py-3 text-center text-sm-start">
            <div class="container-fluid">
                <div class="float-end d-none d-sm-inline">
                    AdminLTE v4
                </div>
                <strong>Copyright &copy; 2026 <Link :href="route('admin.dashboard')" class="text-decoration-none">Laravel Admin</Link>.</strong> All rights reserved.
            </div>
        </footer>
    </div>
</template>
