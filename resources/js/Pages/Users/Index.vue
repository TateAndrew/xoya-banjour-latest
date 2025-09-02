<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        User Management
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <!-- Header with Create Button -->
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-medium text-gray-900">Users</h3>
              <Link
                :href="route('users.create')"
                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
              >
                <PlusIcon class="w-4 h-4 mr-2" />
                Add User
              </Link>
            </div>

            <!-- Search and Filters -->
            <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <input
                  v-model="filters.search"
                  type="text"
                  placeholder="Search users..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  @input="debounceSearch"
                />
              </div>
              <div>
                <select
                  v-model="filters.status"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  @change="applyFilters"
                >
                  <option value="all">All Statuses</option>
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="suspended">Suspended</option>
                </select>
              </div>
              <div>
                <select
                  v-model="filters.role"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  @change="applyFilters"
                >
                  <option value="all">All Roles</option>
                  <option v-for="role in roles" :key="role.id" :value="role.name">
                    {{ role.name }}
                  </option>
                </select>
              </div>
              <div>
                <button
                  @click="clearFilters"
                  class="w-full px-3 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                >
                  Clear Filters
                </button>
              </div>
            </div>

            <!-- Users Table -->
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      User
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Contact
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Roles
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Created
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Actions
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                          <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                            <span class="text-white font-medium text-sm">
                              {{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                            </span>
                          </div>
                        </div>
                        <div class="ml-4">
                          <div class="text-sm font-medium text-gray-900">
                            {{ user.name || 'No Name' }}
                          </div>
                          <div class="text-sm text-gray-500">
                            ID: {{ user.id }}
                          </div>
                        </div>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="text-sm text-gray-900">{{ user.email }}</div>
                      <div class="text-sm text-gray-500">{{ user.phone || 'No Phone' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <div class="flex flex-wrap gap-1">
                        <span
                          v-for="role in user.roles"
                          :key="role.id"
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                        >
                          {{ role.name }}
                        </span>
                        <span v-if="user.roles.length === 0" class="text-gray-400 text-sm">
                          No roles
                        </span>
                      </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span
                        :class="{
                          'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium': true,
                          'bg-green-100 text-green-800': user.status === 'active',
                          'bg-red-100 text-red-800': user.status === 'inactive',
                          'bg-yellow-100 text-yellow-800': user.status === 'suspended'
                        }"
                      >
                        {{ user.status }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(user.created_at) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <div class="flex space-x-2">
                        <Link
                          :href="route('users.show', user.id)"
                          class="text-blue-600 hover:text-blue-900"
                        >
                          View
                        </Link>
                        <Link
                          :href="route('users.edit', user.id)"
                          class="text-indigo-600 hover:text-indigo-900"
                        >
                          Edit
                        </Link>
                        <button
                          @click="toggleUserStatus(user)"
                          :class="{
                            'text-green-600 hover:text-green-900': user.status === 'inactive',
                            'text-red-600 hover:text-red-900': user.status === 'active'
                          }"
                          class="hover:underline"
                        >
                          {{ user.status === 'active' ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button
                          v-if="user.id !== $page.props.auth.user.id"
                          @click="deleteUser(user)"
                          class="text-red-600 hover:text-red-900 hover:underline"
                        >
                          Delete
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="users.links && users.links.length > 3" class="mt-6">
              <nav class="flex justify-center">
                <div class="flex space-x-1">
                  <Link
                    v-for="(link, key) in users.links"
                    :key="key"
                    :href="link.url"
                    :class="{
                      'px-3 py-2 text-sm font-medium rounded-md': true,
                      'bg-blue-600 text-white': link.active,
                      'bg-white text-gray-700 hover:bg-gray-50': !link.active,
                      'opacity-50 cursor-not-allowed': !link.url
                    }"
                    v-html="link.label"
                  />
                </div>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  users: Object,
  roles: Array,
  statuses: Array,
  filters: Object
})

const filters = ref({
  search: props.filters?.search || '',
  status: props.filters?.status || 'all',
  role: props.filters?.role || 'all'
})

let searchTimeout = null

const debounceSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    applyFilters()
  }, 300)
}

const applyFilters = () => {
  router.get(route('users.index'), filters.value, {
    preserveState: true,
    preserveScroll: true
  })
}

const clearFilters = () => {
  filters.value = {
    search: '',
    status: 'all',
    role: 'all'
  }
  applyFilters()
}

const toggleUserStatus = (user) => {
  router.post(route('users.toggle-status', user.id), {}, {
    preserveScroll: true,
    onSuccess: () => {
      window.toastr.success(`User status updated successfully!`);
    },
    onError: (errors) => {
      const firstError = Object.values(errors)[0];
      if (firstError) {
        window.toastr.error(firstError[0]);
      }
    }
  })
}

const deleteUser = (user) => {
  if (confirm(`Are you sure you want to delete ${user.name || user.email}?`)) {
    router.delete(route('users.destroy', user.id), {
      onSuccess: () => {
        window.toastr.success('User deleted successfully!');
      },
      onError: (errors) => {
        const firstError = Object.values(errors)[0];
        if (firstError) {
          window.toastr.error(firstError[0]);
        }
      }
    })
  }
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

// Watch for filter changes
watch(filters, () => {
  applyFilters()
}, { deep: true })
</script>
