<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Edit Role: {{ role.name }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <div class="mb-6">
              <Link
                :href="route('roles.index')"
                class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Roles
              </Link>
            </div>

            <form @submit.prevent="submit">
              <!-- Role Name -->
              <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                  Role Name <span class="text-red-500">*</span>
                </label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': form.errors.name }"
                  placeholder="Enter role name"
                  required
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                  {{ form.errors.name }}
                </p>
              </div>

              <!-- Permissions -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Assign Permissions
                </label>
                
                <!-- Search Permissions -->
                <div class="mb-4">
                  <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search permissions..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  />
                </div>

                <!-- Select All / Deselect All -->
                <div class="mb-4 flex gap-4">
                  <button
                    type="button"
                    @click="selectAll"
                    class="px-4 py-2 text-sm bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200"
                  >
                    Select All
                  </button>
                  <button
                    type="button"
                    @click="deselectAll"
                    class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200"
                  >
                    Deselect All
                  </button>
                </div>

                <!-- Permissions Grid -->
                <div class="border border-gray-200 rounded-lg p-4 max-h-96 overflow-y-auto">
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    <div
                      v-for="permission in filteredPermissions"
                      :key="permission.id"
                      class="flex items-center"
                    >
                      <input
                        :id="`permission-${permission.id}`"
                        v-model="form.permissions"
                        type="checkbox"
                        :value="permission.name"
                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                      />
                      <label
                        :for="`permission-${permission.id}`"
                        class="ml-2 text-sm text-gray-700 cursor-pointer"
                      >
                        {{ permission.name }}
                      </label>
                    </div>
                  </div>
                  
                  <!-- No permissions found -->
                  <div v-if="filteredPermissions.length === 0" class="text-center py-8 text-gray-500">
                    No permissions found matching "{{ searchQuery }}"
                  </div>
                </div>

                <!-- Selected Count -->
                <p class="mt-2 text-sm text-gray-600">
                  {{ form.permissions.length }} permission(s) selected
                </p>
              </div>

              <!-- Form Actions -->
              <div class="flex justify-end space-x-3">
                <Link
                  :href="route('roles.index')"
                  class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                >
                  Cancel
                </Link>
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ form.processing ? 'Updating...' : 'Update Role' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
  role: Object,
  permissions: Array
});

const searchQuery = ref('');

const form = useForm({
  name: props.role.name,
  permissions: props.role.permissions
});

const filteredPermissions = computed(() => {
  if (!searchQuery.value) {
    return props.permissions;
  }
  return props.permissions.filter(permission =>
    permission.name.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});

const selectAll = () => {
  form.permissions = filteredPermissions.value.map(p => p.name);
};

const deselectAll = () => {
  form.permissions = [];
};

const submit = () => {
  form.put(route('roles.update', props.role.id));
};
</script>

