<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Role Details: {{ role.name }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <!-- Back Button -->
            <div class="mb-6 flex justify-between items-center">
              <Link
                :href="route('roles.index')"
                class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Roles
              </Link>
              
              <Link
                :href="route('roles.edit', role.id)"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
              >
                Edit Role
              </Link>
            </div>

            <!-- Role Information -->
            <div class="space-y-6">
              <!-- Basic Info -->
              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 rounded-lg">
                <dt class="text-sm font-medium text-gray-500">Role Name</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ role.name }}</dd>
              </div>

              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 rounded-lg">
                <dt class="text-sm font-medium text-gray-500">Guard Name</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                  <span class="px-2 py-1 text-xs font-semibold text-gray-600 bg-gray-200 rounded-full">
                    {{ role.guard_name }}
                  </span>
                </dd>
              </div>

              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 rounded-lg">
                <dt class="text-sm font-medium text-gray-500">Created At</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ role.created_at }}</dd>
              </div>

              <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6 rounded-lg">
                <dt class="text-sm font-medium text-gray-500">Updated At</dt>
                <dd class="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">{{ role.updated_at }}</dd>
              </div>

              <!-- Permissions -->
              <div class="bg-gray-50 px-4 py-5 rounded-lg">
                <h3 class="text-sm font-medium text-gray-500 mb-4">Assigned Permissions</h3>
                <div v-if="role.permissions && role.permissions.length > 0">
                  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                    <div
                      v-for="permission in role.permissions"
                      :key="permission"
                      class="px-3 py-2 bg-blue-100 text-blue-800 rounded-md text-sm"
                    >
                      {{ permission }}
                    </div>
                  </div>
                  <p class="mt-4 text-sm text-gray-600">
                    Total: {{ role.permissions.length }} permission(s)
                  </p>
                </div>
                <div v-else class="text-sm text-gray-500 italic">
                  No permissions assigned to this role.
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
  role: Object
});
</script>

