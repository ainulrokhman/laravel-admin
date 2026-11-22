<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';

// Shared create/edit role form, ported from the former admin/roles Blade
// views. Permissions are grouped into category cards with a switch that
// toggles the whole group; the pages own the useForm state and submit target.
const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    // { Category: [{ id, name }] } as grouped by RoleService::groupPermissions
    groupedPermissions: {
        type: Object,
        default: () => ({}),
    },
    nameReadonly: {
        type: Boolean,
        default: false,
    },
    detailsHint: {
        type: String,
        required: true,
    },
    permissionsTitle: {
        type: String,
        required: true,
    },
    permissionsHint: {
        type: String,
        required: true,
    },
    showDeselectAll: {
        type: Boolean,
        default: false,
    },
    emptyPermissionsText: {
        type: String,
        required: true,
    },
    submitText: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['submit']);

const categories = computed(() => Object.entries(props.groupedPermissions));
const hasPermissions = computed(() => categories.value.length > 0);
const allIds = computed(() => categories.value.flatMap(([, perms]) => perms.map((perm) => perm.id)));

function groupAllChecked(perms) {
    return perms.length > 0 && perms.every((perm) => props.form.permissions.includes(perm.id));
}

function toggleGroup(perms, checked) {
    const ids = perms.map((perm) => perm.id);
    props.form.permissions = checked
        ? [...new Set([...props.form.permissions, ...ids])]
        : props.form.permissions.filter((id) => !ids.includes(id));
}

function toggleAll(checked) {
    props.form.permissions = checked ? [...allIds.value] : [];
}

function permissionLabel(name) {
    return name.replaceAll('-', ' ');
}
</script>

<template>
    <form @submit.prevent="emit('submit')">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4">
                <h5 class="mb-0 fw-bold text-body">Role Details</h5>
                <p class="text-muted small mb-0">{{ props.detailsHint }}</p>
            </div>
            <div class="card-body px-4 pb-4">
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold text-secondary">Role Name</label>
                    <input type="text"
                           class="form-control"
                           :class="{ 'is-invalid': form.errors.name }"
                           id="name"
                           v-model="form.name"
                           placeholder="e.g. Editor, Moderator"
                           required
                           autofocus
                           :readonly="props.nameReadonly">
                    <div v-if="form.errors.name" class="invalid-feedback">{{ form.errors.name }}</div>
                    <div v-if="props.nameReadonly" class="form-text text-warning small">
                        <i class="bi bi-info-circle-fill me-1"></i> This is a system-defined role. The name cannot be modified to prevent system breaking.
                    </div>
                    <div v-else class="form-text text-muted small">The name must be unique and descriptive. Use CamelCase or spaces (e.g., "Content Manager").</div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="mb-0 fw-bold text-body">{{ props.permissionsTitle }}</h5>
                    <p class="text-muted small mb-0">{{ props.permissionsHint }}</p>
                </div>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" :class="{ 'me-2': props.showDeselectAll }" @click="toggleAll(true)">
                        {{ props.showDeselectAll ? 'Select All' : 'Select All Permissions' }}
                    </button>
                    <button v-if="props.showDeselectAll" type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" @click="toggleAll(false)">
                        Deselect All
                    </button>
                </div>
            </div>
            <div class="card-body px-4 pb-4">
                <div v-if="hasPermissions" class="row g-4">
                    <div v-for="[category, perms] in categories" :key="category" class="col-12 col-md-6 col-lg-4">
                        <div class="card border border-light h-100 bg-light bg-opacity-30 rounded-3">
                            <div class="card-header bg-light border-0 py-3 px-3 d-flex align-items-center justify-content-between">
                                <span class="fw-bold text-body">{{ category }} Permissions</span>
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" :id="`toggle-${category}`" :checked="groupAllChecked(perms)" @change="toggleGroup(perms, $event.target.checked)">
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <div class="d-flex flex-column gap-2">
                                    <div v-for="perm in perms" :key="perm.id" class="form-check d-flex align-items-center">
                                        <input class="form-check-input" type="checkbox" :value="perm.id" :id="`perm-${perm.id}`" v-model="form.permissions">
                                        <label class="form-check-label ms-2 small text-secondary" :for="`perm-${perm.id}`">
                                            {{ permissionLabel(perm.name) }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center py-4">
                    <i class="bi bi-shield-slash fs-2 text-muted mb-2"></i>
                    <p class="text-muted small mb-0">{{ props.emptyPermissionsText }}</p>
                </div>
                <div v-if="form.errors.permissions" class="text-danger small mt-2">{{ form.errors.permissions }}</div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="d-flex align-items-center justify-content-end gap-3 mb-5">
            <Link :href="route('admin.roles.index')" class="btn btn-light border py-2 px-4 fw-medium" style="border-radius: 8px;">
                Cancel
            </Link>
            <button type="submit" class="btn btn-primary py-2 px-4 fw-medium" style="border-radius: 8px;" :disabled="form.processing">
                {{ props.submitText }} <i class="bi bi-check-lg ms-1"></i>
            </button>
        </div>
    </form>
</template>
