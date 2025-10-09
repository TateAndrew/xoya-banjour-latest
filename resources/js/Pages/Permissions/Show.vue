<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Permission Details: {{ permission.name }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <!-- Back Button -->
            <div class="mb-6 flex justify-between items-center">
              <Link
                :href="route('permissions.index')"
                class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Permissions
              </Link>
              
              <Link
                :href="route('permissions.edit', permission.id)"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              >
                Edit Permission
              </Link>
            </div>

            <!-- Permission Information -->
            <div class="space-y-6">
              <!-- Basic Info -->
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 rounded-lg">
                <dt class="text-sm font-medium text-gray-500">Permission Name</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ permission.name }}</dd>
              </div>

              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 rounded-lg">
                <dt class="text-sm font-medium text-gray-500">Guard Name</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                  <span class="px-2 py-1 text-xs font-semibold text-gray-600 bg-gray-200 rounded-full">
                    {{ permission.guard_name }}
                  </span>
                </dd>
              </div>

              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 rounded-lg">
                <dt class="text-sm font-medium text-gray-500">Created At</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ permission.created_at }}</dd>
              </div>

              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 rounded-lg">
                <dt class="text-sm font-medium text-gray-500">Updated At</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ permission.updated_at }}</dd>
              </div>

              <!-- Assigned Roles -->
              <div class="bg-gray-50 px-4 py-5 rounded-lg">
                <h3 class="text-sm font-medium text-gray-500 mb-4">Assigned to Roles</h3>
                <div v-if="permission.roles && permission.roles.length > 0">
                  <div class="flex flex-wrap gap-2">
                    <div
                      v-for="role in permission.roles"
                      :key="role"
                      class="px-3 py-2 bg-green-100 text-green-800 rounded-md text-sm"
                    >
                      {{ role }}
                    </div>
                  </div>
                  <p class="mt-4 text-sm text-gray-600">
                    Total: {{ permission.roles.length }} role(s)
                  </p>
                </div>
                <div v-else class="text-sm text-gray-500 italic">
                  This permission is not assigned to any roles.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

defineProps({
  permission: Object
});
</script>

