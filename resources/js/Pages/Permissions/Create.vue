<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Create Permission
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <div class="mb-6">
              <Link
                :href="route('permissions.index')"
                class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Permissions
              </Link>
            </div>

            <form @submit.prevent="submit">
              <!-- Permission Name -->
              <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                  Permission Name <span class="text-red-500">*</span>
                </label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  :class="{ 'border-red-500': form.errors.name }"
                  placeholder="Enter permission name (e.g., edit posts, delete users)"
                  required
                />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                  {{ form.errors.name }}
                </p>
                <p class="mt-1 text-sm text-gray-500">
                  Use lowercase with spaces (e.g., "edit users", "delete posts")
                </p>
              </div>

              <!-- Common Permission Examples -->
              <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Common Permission Examples
                </label>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                  <p class="text-sm text-gray-600 mb-2">Click to use:</p>
                  <div class="flex flex-wrap gap-2">
                    <button
                      v-for="example in permissionExamples"
                      :key="example"
                      type="button"
                      @click="form.name = example"
                      class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200"
                    >
                      {{ example }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- Form Actions -->
              <div class="flex justify-end space-x-3">
                <Link
                  :href="route('permissions.index')"
                  class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                >
                  Cancel
                </Link>
                <button
                  type="submit"
                  :disabled="form.processing"
                  class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ form.processing ? 'Creating...' : 'Create Permission' }}
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
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const form = useForm({
  name: ''
});

const permissionExamples = [
  'view users',
  'create users',
  'edit users',
  'delete users',
  'view posts',
  'create posts',
  'edit posts',
  'delete posts',
  'manage settings',
  'view reports',
  'export data'
];

const submit = () => {
  form.post(route('permissions.store'));
};
</script>

