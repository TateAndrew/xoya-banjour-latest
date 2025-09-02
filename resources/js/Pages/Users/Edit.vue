<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Edit User
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <form @submit.prevent="submit" class="space-y-6">
              <!-- Name Field -->
              <div>
                <InputLabel for="name" value="Name" />
                <TextInput
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': errors.name }"
                />
                <InputError :message="errors.name" class="mt-2" />
              </div>

              <!-- Email Field -->
              <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                  id="email"
                  v-model="form.email"
                  type="email"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': errors.email }"
                  required
                />
                <InputError :message="errors.email" class="mt-2" />
              </div>

              <!-- Phone Field -->
              <div>
                <InputLabel for="phone" value="Phone" />
                <TextInput
                  id="phone"
                  v-model="form.phone"
                  type="tel"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': errors.phone }"
                />
                <InputError :message="errors.phone" class="mt-2" />
              </div>

              <!-- Password Field -->
              <div>
                <InputLabel for="password" value="Password (leave blank to keep current)" />
                <TextInput
                  id="password"
                  v-model="form.password"
                  type="password"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': errors.password }"
                />
                <InputError :message="errors.password" class="mt-2" />
              </div>

              <!-- Confirm Password Field -->
              <div>
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  type="password"
                  class="mt-1 block w-full"
                  :class="{ 'border-red-500': errors.password_confirmation }"
                />
                <InputError :message="errors.password_confirmation" class="mt-2" />
              </div>

              <!-- Status Field -->
              <div>
                <InputLabel for="status" value="Status" />
                <select
                  id="status"
                  v-model="form.status"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                  :class="{ 'border-red-500': errors.status }"
                >
                  <option value="active">Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="suspended">Suspended</option>
                </select>
                <InputError :message="errors.status" class="mt-2" />
              </div>

              <!-- Roles Field -->
              <div>
                <InputLabel for="roles" value="Roles" />
                <div class="mt-2 space-y-2">
                  <label
                    v-for="role in roles"
                    :key="role.id"
                    class="flex items-center"
                  >
                    <Checkbox
                      :value="role.id"
                      v-model:checked="form.roles"
                      :name="`role_${role.id}`"
                    />
                    <span class="ml-2 text-sm text-gray-700">{{ role.name }}</span>
                  </label>
                </div>
                <InputError :message="errors.roles" class="mt-2" />
              </div>

              <!-- Permissions Field -->
              <div>
                <InputLabel for="permissions" value="Permissions" />
                <div class="mt-2 space-y-2 max-h-40 overflow-y-auto border border-gray-200 rounded-md p-3">
                  <label
                    v-for="permission in permissions"
                    :key="permission.id"
                    class="flex items-center"
                  >
                    <Checkbox
                      :value="permission.id"
                      v-model:checked="form.permissions"
                      :name="`permission_${permission.id}`"
                    />
                    <span class="ml-2 text-sm text-gray-700">{{ permission.name }}</span>
                  </label>
                </div>
                <InputError :message="errors.permissions" class="mt-2" />
              </div>

              <!-- Action Buttons -->
              <div class="flex items-center justify-end space-x-3">
                <Link
                  :href="route('users.index')"
                  class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                  Cancel
                </Link>
                <PrimaryButton
                  :class="{ 'opacity-25': form.processing }"
                  :disabled="form.processing"
                  class="inline-flex items-center"
                >
                  <svg
                    v-if="form.processing"
                    class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                  >
                    <circle
                      class="opacity-25"
                      cx="12"
                      cy="12"
                      r="10"
                      stroke="currentColor"
                      stroke-width="4"
                    ></circle>
                    <path
                      class="opacity-75"
                      fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                    ></path>
                  </svg>
                  {{ form.processing ? 'Updating...' : 'Update User' }}
                </PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { useForm, usePage } from '@inertiajs/vue3'
import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import Checkbox from '@/Components/Checkbox.vue'

const page = usePage()

// Access errors and flash messages
const errors = page.props.errors || {}
const flash = page.props.flash || {}

const props = defineProps({
  user: Object,
  roles: Array,
  permissions: Array
})

const form = useForm({
  name: props.user.name || '',
  email: props.user.email || '',
  phone: props.user.phone || '',
  password: '',
  password_confirmation: '',
  status: props.user.status || 'active',
  roles: props.user.roles ? props.user.roles.map(role => role.id) : [],
  permissions: props.user.permissions ? props.user.permissions.map(permission => permission.id) : []
})

const submit = () => {
  form.put(route('users.update', props.user.id), {
    onError: (errors) => {
      // Show validation errors in toastr
      const firstError = Object.values(errors)[0];
      if (firstError) {
        window.toastr.error(firstError[0]);
      }
    },
    onSuccess: () => {
      window.toastr.success('User updated successfully!');
    }
  })
}
</script>
