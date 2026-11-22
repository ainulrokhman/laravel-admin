<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

// Light/dark/auto theme switcher, ported from the script that used to live in
// the Blade layouts. The effective theme is applied pre-paint by an inline
// script in the root template; this component owns the toggle afterwards.
defineProps({
    variant: {
        type: String,
        default: 'nav', // 'nav' in the admin topbar, 'floating' on the login page
    },
});

const themes = [
    { value: 'light', label: 'Light', icon: 'bi bi-sun-fill' },
    { value: 'dark', label: 'Dark', icon: 'bi bi-moon-stars-fill' },
    { value: 'auto', label: 'Auto', icon: 'bi bi-circle-half' },
];

const activeTheme = ref('auto');

const activeIcon = computed(() => {
    const theme = themes.find((t) => t.value === activeTheme.value);
    return theme ? theme.icon : 'bi bi-circle-half';
});

const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');

function applyTheme(theme) {
    const effective = theme === 'auto' ? (darkQuery.matches ? 'dark' : 'light') : theme;
    document.documentElement.setAttribute('data-bs-theme', effective);
}

function setTheme(theme) {
    activeTheme.value = theme;
    localStorage.setItem('theme', theme);
    applyTheme(theme);
}

function onSystemThemeChange() {
    if (activeTheme.value === 'auto') {
        applyTheme('auto');
    }
}

onMounted(() => {
    activeTheme.value = localStorage.getItem('theme') || 'auto';
    applyTheme(activeTheme.value);
    darkQuery.addEventListener('change', onSystemThemeChange);
});

onUnmounted(() => {
    darkQuery.removeEventListener('change', onSystemThemeChange);
});
</script>

<template>
    <div v-if="variant === 'floating'" class="position-fixed top-0 end-0 p-3 z-3">
        <div class="dropdown">
            <button class="btn btn-secondary btn-sm dropdown-toggle d-flex align-items-center gap-2 border-0 shadow-sm" type="button" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                <i :class="activeIcon"></i>
                <span class="d-none d-sm-inline">Theme</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                <li v-for="theme in themes" :key="theme.value">
                    <button type="button" class="dropdown-item d-flex align-items-center gap-2" :class="{ active: activeTheme === theme.value }" :aria-pressed="activeTheme === theme.value" @click="setTheme(theme.value)">
                        <i :class="`${theme.icon} opacity-50`"></i>
                        {{ theme.label }}
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <li v-else class="nav-item dropdown me-2">
        <button class="btn btn-link nav-link px-0 text-decoration-none dropdown-toggle d-flex align-items-center" type="button" aria-expanded="false" data-bs-toggle="dropdown" data-bs-display="static">
            <i :class="`${activeIcon} my-1`"></i>
            <span class="d-lg-none ms-2">Toggle theme</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li v-for="theme in themes" :key="theme.value">
                <button type="button" class="dropdown-item d-flex align-items-center" :class="{ active: activeTheme === theme.value }" :aria-pressed="activeTheme === theme.value" @click="setTheme(theme.value)">
                    <i :class="`${theme.icon} me-2 opacity-50`"></i>
                    {{ theme.label }}
                </button>
            </li>
        </ul>
    </li>
</template>
