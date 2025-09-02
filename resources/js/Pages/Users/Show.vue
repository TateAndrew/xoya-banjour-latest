<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        User Details
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            <!-- User Header -->
            <div class="flex items-center justify-between mb-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 h-16 w-16">
                  <div class="h-16 w-16 rounded-full bg-blue-500 flex items-center justify-center">
                    <span class="text-white font-medium text-xl">
                      {{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                    </span>
                  </div>
                </div>
                <div class="ml-4">
                  <h3 class="text-2xl font-bold text-gray-900">
                    {{ user.name || 'No Name' }}
                  </h3>
                  <p class="text-gray-500">ID: {{ user.id }}</p>
                </div>
              </div>
              <div class="flex space-x-3">
                <Link
                  :href="route('users.edit', user.id)"
                  class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                  Edit User
                </Link>
                <Link
                  :href="route('users.index')"
                  class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 focus:bg-gray-400 active:bg-gray-500 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                  Back to Users
                </Link>
              </div>
            </div>

            <!-- User Information -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
              <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="text-lg font-medium text-gray-900 mb-3">Basic Information</h4>
                <dl class="space-y-2">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="text-sm text-gray-900">{{ user.name || 'Not provided' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="text-sm text-gray-900">{{ user.email }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                    <dd class="text-sm text-gray-900">{{ user.phone || 'Not provided' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="text-sm">
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
                    </dd>
                  </div>
                </dl>
              </div>

              <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="text-lg font-medium text-gray-900 mb-3">Account Details</h4>
                <dl class="space-y-2">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Email Verified</dt>
                    <dd class="text-sm text-gray-900">
                      <span v-if="user.email_verified_at" class="text-green-600">
                        ✓ Verified on {{ formatDate(user.email_verified_at) }}
                      </span>
                      <span v-else class="text-red-600">✗ Not verified</span>
                    </dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Member Since</dt>
                    <dd class="text-sm text-gray-900">{{ formatDate(user.created_at) }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                    <dd class="text-sm text-gray-900">{{ formatDate(user.updated_at) }}</dd>
                  </div>
                </dl>
              </div>
            </div>

            <!-- Roles and Permissions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
              <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="text-lg font-medium text-gray-900 mb-3">Roles</h4>
                <div v-if="user.roles && user.roles.length > 0" class="space-y-2">
                  <span
                    v-for="role in user.roles"
                    :key="role.id"
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 mr-2 mb-2"
                  >
                    {{ role.name }}
                  </span>
                </div>
                <p v-else class="text-gray-500 text-sm">No roles assigned</p>
              </div>

              <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="text-lg font-medium text-gray-900 mb-3">Permissions</h4>
                <div v-if="user.permissions && user.permissions.length > 0" class="space-y-2">
                  <span
                    v-for="permission in user.permissions"
                    :key="permission.id"
                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800 mr-2 mb-2"
                  >
                    {{ permission.name }}
                  </span>
                </div>
                <p v-else class="text-gray-500 text-sm">No direct permissions assigned</p>
              </div>
            </div>

            <!-- Assigned Phone Numbers -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
              <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-medium text-gray-900">Assigned Phone Numbers</h4>
                <button
                  @click="showAssignModal = true"
                  class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  <svg class="-ml-0.5 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                  </svg>
                  Assign Number
                </button>
              </div>
              
              <div v-if="user.phone_numbers && user.phone_numbers.length > 0" class="space-y-3">
                <div
                  v-for="phoneNumber in user.phone_numbers"
                  :key="phoneNumber.id"
                  class="bg-white rounded-lg border overflow-hidden"
                >
                  <!-- Main phone number row -->
                  <div class="flex items-center justify-between p-3">
                    <div class="flex-1">
                      <div class="font-medium text-gray-900">{{ phoneNumber.phone_number }}</div>
                      <div class="text-sm text-gray-500">
                        {{ phoneNumber.country_code }} • {{ phoneNumber.area_code || 'No area code' }}
                        <span v-if="phoneNumber.city"> • {{ phoneNumber.city }}</span>
                        <span v-if="phoneNumber.state">, {{ phoneNumber.state }}</span>
                      </div>
                      <div class="text-xs text-gray-400 space-y-1">
                        <div>Status: {{ phoneNumber.status }} • Assigned: {{ formatDate(phoneNumber.purchased_at) }}</div>
                        <div v-if="phoneNumber.monthly_rate" class="text-green-600 font-medium">
                          Monthly Rate: ${{ phoneNumber.monthly_rate }}
                        </div>
                        <div v-if="phoneNumber.expires_at" class="text-orange-600">
                          Expires: {{ formatDate(phoneNumber.expires_at) }}
                        </div>
                        <div v-if="phoneNumber.capabilities && phoneNumber.capabilities.length > 0" class="flex flex-wrap gap-1">
                          <span
                            v-for="capability in phoneNumber.capabilities"
                            :key="capability"
                            class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                          >
                            {{ capability }}
                          </span>
                        </div>
                      </div>
                    </div>
                    <div class="flex items-center space-x-2">
                      <!-- Details Button -->
                      <button
                        @click="togglePhoneNumberDetails(phoneNumber)"
                        class="inline-flex items-center px-2 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                      >
                        <svg class="mr-1 h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Details
                      </button>
                      <!-- Unassign Button -->
                      <button
                        @click="unassignPhoneNumber(phoneNumber.id)"
                        :disabled="unassigningNumbers.includes(phoneNumber.id)"
                        class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50"
                      >
                        <svg v-if="unassigningNumbers.includes(phoneNumber.id)" class="animate-spin -ml-1 mr-1 h-3 w-3 text-red-500" fill="none" viewBox="0 0 24 24">
                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        {{ unassigningNumbers.includes(phoneNumber.id) ? 'Unassigning...' : 'Unassign' }}
                      </button>
                    </div>
                  </div>
                  
                  <!-- Expandable details section -->
                  <div v-if="expandedNumbers.includes(phoneNumber.id)" class="border-t bg-gray-50 p-4">
                    <div v-if="loadingDetails[phoneNumber.id]" class="text-center py-4">
                      <svg class="animate-spin mx-auto h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      <p class="text-gray-600 mt-2 text-sm">Loading Telnyx details...</p>
                    </div>
                    
                    <div v-else-if="phoneNumberDetails[phoneNumber.id]" class="space-y-4">
                      <!-- Telnyx Details -->
                      <div v-if="phoneNumberDetails[phoneNumber.id].telnyx_data">
                        <h5 class="text-sm font-medium text-gray-900 mb-2">Telnyx Information</h5>
                        <div class="grid grid-cols-2 gap-3 text-xs">
                          <div>
                            <span class="text-gray-500">Telnyx ID:</span>
                            <span class="ml-2 font-mono">{{ phoneNumberDetails[phoneNumber.id].telnyx_data.id || phoneNumber.telynx_id }}</span>
                          </div>
                          <div v-if="phoneNumberDetails[phoneNumber.id].telnyx_data.status">
                            <span class="text-gray-500">Telnyx Status:</span>
                            <span class="ml-2">{{ phoneNumberDetails[phoneNumber.id].telnyx_data.status }}</span>
                          </div>
                          <div v-if="phoneNumberDetails[phoneNumber.id].telnyx_data.connection_id">
                            <span class="text-gray-500">Connection ID:</span>
                            <span class="ml-2 font-mono">{{ phoneNumberDetails[phoneNumber.id].telnyx_data.connection_id }}</span>
                          </div>
                          <div v-if="phoneNumberDetails[phoneNumber.id].telnyx_data.messaging_profile_id">
                            <span class="text-gray-500">Messaging Profile:</span>
                            <span class="ml-2 font-mono">{{ phoneNumberDetails[phoneNumber.id].telnyx_data.messaging_profile_id }}</span>
                          </div>
                          <div v-if="phoneNumberDetails[phoneNumber.id].telnyx_data.voice_profile_id">
                            <span class="text-gray-500">Voice Profile:</span>
                            <span class="ml-2 font-mono">{{ phoneNumberDetails[phoneNumber.id].telnyx_data.voice_profile_id }}</span>
                          </div>
                          <div v-if="phoneNumberDetails[phoneNumber.id].telnyx_data.external_pin">
                            <span class="text-gray-500">External PIN:</span>
                            <span class="ml-2">{{ phoneNumberDetails[phoneNumber.id].telnyx_data.external_pin }}</span>
                          </div>
                          <div v-if="phoneNumberDetails[phoneNumber.id].telnyx_data.monthly_cost">
                            <span class="text-gray-500">Monthly Cost:</span>
                            <span class="ml-2">${{ phoneNumberDetails[phoneNumber.id].telnyx_data.monthly_cost }}</span>
                          </div>
                          <div v-if="phoneNumberDetails[phoneNumber.id].telnyx_data.upfront_cost">
                            <span class="text-gray-500">Setup Cost:</span>
                            <span class="ml-2">${{ phoneNumberDetails[phoneNumber.id].telnyx_data.upfront_cost }}</span>
                          </div>
                        </div>
                        
                        <!-- Features -->
                        <div v-if="phoneNumberDetails[phoneNumber.id].telnyx_data.features" class="mt-3">
                          <span class="text-gray-500 text-xs">Features:</span>
                          <div class="flex flex-wrap gap-1 mt-1">
                            <span
                              v-for="feature in phoneNumberDetails[phoneNumber.id].telnyx_data.features"
                              :key="feature"
                              class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                            >
                              {{ feature }}
                            </span>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Usage Statistics -->
                      <div v-if="phoneNumberDetails[phoneNumber.id].statistics">
                        <h5 class="text-sm font-medium text-gray-900 mb-2">Usage Statistics</h5>
                        <div class="grid grid-cols-3 gap-3 text-xs">
                          <div class="text-center p-2 bg-white rounded">
                            <div class="font-medium text-gray-900">{{ phoneNumberDetails[phoneNumber.id].statistics.total_calls || 0 }}</div>
                            <div class="text-gray-500">Total Calls</div>
                          </div>
                          <div class="text-center p-2 bg-white rounded">
                            <div class="font-medium text-gray-900">{{ phoneNumberDetails[phoneNumber.id].statistics.total_messages || 0 }}</div>
                            <div class="text-gray-500">Messages</div>
                          </div>
                          <div class="text-center p-2 bg-white rounded">
                            <div class="font-medium text-gray-900">
                              {{ phoneNumberDetails[phoneNumber.id].statistics.last_used ? formatDate(phoneNumberDetails[phoneNumber.id].statistics.last_used) : 'Never' }}
                            </div>
                            <div class="text-gray-500">Last Used</div>
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <div v-else class="text-center py-4">
                      <p class="text-gray-600 text-sm">Unable to load details</p>
                    </div>
                  </div>
                </div>
              </div>
              <p v-else class="text-gray-500 text-sm">No phone numbers assigned</p>
            </div>

            <!-- All Telnyx Numbers -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
              <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-medium text-gray-900">All Telnyx Numbers</h4>
                <button
                  @click="refreshTelnyxNumbers"
                  :disabled="refreshing"
                  class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                >
                  <svg :class="['mr-2 h-4 w-4', refreshing ? 'animate-spin' : '']" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                  </svg>
                  {{ refreshing ? 'Refreshing...' : 'Refresh' }}
                </button>
              </div>
              
              <div v-if="telnyxError" class="mb-4 p-3 bg-red-100 border border-red-300 rounded-md">
                <p class="text-sm text-red-700">Error loading Telnyx numbers: {{ telnyxError }}</p>
              </div>
              
              <div v-if="telnyxNumbers && telnyxNumbers.length > 0" class="space-y-2">
                <div
                  v-for="number in telnyxNumbers"
                  :key="number.id"
                  class="bg-white rounded border overflow-hidden"
                >
                  <!-- Main number row -->
                  <div 
                    @click="toggleTelnyxNumberDetails(number)"
                    class="flex items-center justify-between p-2 text-sm cursor-pointer hover:bg-gray-50 transition-colors"
                  >
                    <div>
                      <span class="font-medium">{{ number.phone_number }}</span>
                      <span class="text-gray-500 ml-2">{{ number.country_code }}</span>
                      <span v-if="number.city" class="text-gray-500 ml-1">• {{ number.city }}</span>
                      <span v-if="number.state" class="text-gray-500">, {{ number.state }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                      <span class="text-xs text-gray-400">
                        {{ number.status }} • ${{ number.monthly_cost }}/mo
                      </span>
                      <svg 
                        :class="['h-4 w-4 text-gray-400 transition-transform', expandedTelnyxNumbers.includes(number.id) ? 'rotate-180' : '']" 
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                      </svg>
                    </div>
                  </div>
                  
                  <!-- Expandable details for Telnyx numbers -->
                  <div v-if="expandedTelnyxNumbers.includes(number.id)" class="border-t bg-gray-50 p-3">
                    <div class="grid grid-cols-2 gap-3 text-xs">
                      <div>
                        <span class="text-gray-500">Telnyx ID:</span>
                        <span class="ml-2 font-mono">{{ number.id }}</span>
                      </div>
                      <div v-if="number.connection_id">
                        <span class="text-gray-500">Connection ID:</span>
                        <span class="ml-2 font-mono">{{ number.connection_id }}</span>
                      </div>
                      <div v-if="number.messaging_profile_id">
                        <span class="text-gray-500">Messaging Profile:</span>
                        <span class="ml-2 font-mono">{{ number.messaging_profile_id }}</span>
                      </div>
                      <div v-if="number.voice_profile_id">
                        <span class="text-gray-500">Voice Profile:</span>
                        <span class="ml-2 font-mono">{{ number.voice_profile_id }}</span>
                      </div>
                      <div v-if="number.external_pin">
                        <span class="text-gray-500">External PIN:</span>
                        <span class="ml-2">{{ number.external_pin }}</span>
                      </div>
                      <div v-if="number.upfront_cost">
                        <span class="text-gray-500">Setup Cost:</span>
                        <span class="ml-2">${{ number.upfront_cost }}</span>
                      </div>
                    </div>
                    
                    <!-- Features for Telnyx numbers -->
                    <div v-if="number.features && number.features.length > 0" class="mt-3">
                      <span class="text-gray-500 text-xs">Features:</span>
                      <div class="flex flex-wrap gap-1 mt-1">
                        <span
                          v-for="feature in number.features"
                          :key="feature"
                          class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                        >
                          {{ feature }}
                        </span>
                      </div>
                    </div>
                    
                    <!-- Assignment status -->
                    <div class="mt-3 p-2 bg-white rounded border">
                      <div class="text-xs">
                        <span class="text-gray-500">Assignment Status:</span>
                        <span class="ml-2 font-medium" :class="isNumberAssigned(number.id) ? 'text-green-600' : 'text-gray-600'">
                          {{ isNumberAssigned(number.id) ? 'Assigned to User' : 'Available for Assignment' }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <p v-else-if="!telnyxError" class="text-gray-500 text-sm">No Telnyx numbers found</p>
            </div>

            <!-- Related Data -->
            <div class="bg-gray-50 p-4 rounded-lg">
              <h4 class="text-lg font-medium text-gray-900 mb-3">Related Data</h4>
              <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                  <dt class="text-sm font-medium text-gray-500">Phone Numbers</dt>
                  <dd class="text-sm text-gray-900">{{ user.phone_numbers?.length || 0 }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Calls</dt>
                  <dd class="text-sm text-gray-900">{{ user.calls_count || 0 }}</dd>
                </div>
                <div>
                  <dt class="text-sm font-medium text-gray-500">Conferences</dt>
                  <dd class="text-sm text-gray-900">{{ user.conferences_count || 0 }}</dd>
                </div>
              </dl>
            </div>

    <!-- Assignment Modal -->
    <div v-if="showAssignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Assign Phone Number</h3>
            <button
              @click="showAssignModal = false"
              class="text-gray-400 hover:text-gray-600"
            >
              <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <div v-if="loadingAvailableNumbers" class="text-center py-4">
            <svg class="animate-spin mx-auto h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-gray-600 mt-2">Loading available numbers...</p>
          </div>
          
          <div v-else-if="availableNumbers.length === 0" class="text-center py-4">
            <p class="text-gray-600">No available numbers to assign</p>
          </div>
          
          <div v-else class="space-y-2 max-h-64 overflow-y-auto">
            <div
              v-for="number in availableNumbers"
              :key="number.id"
              @click="selectNumber(number)"
              :class="[
                'p-3 border rounded-lg cursor-pointer transition-colors',
                selectedNumber?.id === number.id
                  ? 'border-indigo-500 bg-indigo-50'
                  : 'border-gray-200 hover:border-gray-300'
              ]"
            >
              <div class="font-medium text-gray-900">{{ number.phone_number }}</div>
              <div class="text-sm text-gray-500">
                {{ number.country_code }} • {{ number.area_code || 'No area code' }}
                <span v-if="number.city"> • {{ number.city }}</span>
                <span v-if="number.state">, {{ number.state }}</span>
              </div>
              <div class="text-xs text-gray-400 space-y-1">
                <div>${{ number.monthly_cost || 0 }}/month</div>
                <div v-if="number.capabilities && number.capabilities.length > 0" class="flex flex-wrap gap-1">
                  <span
                    v-for="capability in number.capabilities"
                    :key="capability"
                    class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800"
                  >
                    {{ capability }}
                  </span>
                </div>
                <div v-if="number.features && number.features.length > 0" class="flex flex-wrap gap-1">
                  <span
                    v-for="feature in number.features"
                    :key="feature"
                    class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800"
                  >
                    {{ feature }}
                  </span>
                </div>
                <div v-if="number.expires_at" class="text-orange-600">
                  Expires: {{ new Date(number.expires_at).toLocaleDateString() }}
                </div>
              </div>
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 mt-6">
            <button
              @click="showAssignModal = false"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
            >
              Cancel
            </button>
            <button
              @click="assignSelectedNumber"
              :disabled="!selectedNumber || assigning"
              class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
            >
              <svg v-if="assigning" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ assigning ? 'Assigning...' : 'Assign Number' }}
            </button>
          </div>
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
import { ref, reactive, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import axios from 'axios'

const props = defineProps({
  user: Object,
  telnyxNumbers: Array,
  unassignedTelnyxNumbers: Array,
  telnyxError: String
})

// Reactive state
const showAssignModal = ref(false)
const selectedNumber = ref(null)
const availableNumbers = ref(props.unassignedTelnyxNumbers || [])
const loadingAvailableNumbers = ref(false)
const assigning = ref(false)
const unassigningNumbers = ref([])
const refreshing = ref(false)

// Phone number details state
const expandedNumbers = ref([])
const phoneNumberDetails = ref({})
const loadingDetails = ref({})

// Telnyx numbers details state
const expandedTelnyxNumbers = ref([])

// Watch for modal open to load available numbers
watch(showAssignModal, async (isOpen) => {
  if (isOpen) {
    await loadAvailableNumbers()
  } else {
    selectedNumber.value = null
  }
})

const formatDate = (date) => {
  if (!date) return 'N/A'
  return new Date(date).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const loadAvailableNumbers = async () => {
  loadingAvailableNumbers.value = true
  try {
    const response = await axios.get('/api/available-telnyx-numbers')
    if (response.data.success) {
      availableNumbers.value = response.data.data || []
    } else {
      console.error('Failed to load available numbers:', response.data.error)
      availableNumbers.value = []
    }
  } catch (error) {
    console.error('Error loading available numbers:', error)
    availableNumbers.value = []
  } finally {
    loadingAvailableNumbers.value = false
  }
}

const selectNumber = (number) => {
  selectedNumber.value = selectedNumber.value?.id === number.id ? null : number
}

const assignSelectedNumber = async () => {
  if (!selectedNumber.value) return

  assigning.value = true
  try {
    // Prepare complete data including all capabilities, costs, and expiry information
    const assignmentData = {
      telnyx_phone_number_id: selectedNumber.value.id,
      phone_number: selectedNumber.value.phone_number,
      country_code: selectedNumber.value.country_code,
      area_code: selectedNumber.value.area_code,
      city: selectedNumber.value.city,
      state: selectedNumber.value.state,
      features: selectedNumber.value.features || [],
      monthly_cost: selectedNumber.value.monthly_cost || 0,
      upfront_cost: selectedNumber.value.upfront_cost || 0,
      expires_at: selectedNumber.value.expires_at || null,
      // Include additional Telnyx metadata
      connection_id: selectedNumber.value.connection_id || null,
      messaging_profile_id: selectedNumber.value.messaging_profile_id || null,
      voice_profile_id: selectedNumber.value.voice_profile_id || null,
      external_pin: selectedNumber.value.external_pin || null,
      carrier: selectedNumber.value.carrier || null,
      phone_number_type: selectedNumber.value.phone_number_type || 'local'
    }

    const response = await axios.post(`/users/${props.user.id}/assign-phone-number`, assignmentData)

    if (response.data.success) {
      // Refresh the page to show updated data
      router.reload({ only: ['user'] })
      showAssignModal.value = false
      selectedNumber.value = null
      
      // Show success message with additional details
      const phoneNumber = response.data.phone_number
      alert(`Phone number assigned successfully!\n\nNumber: ${phoneNumber.phone_number}\nCapabilities: ${phoneNumber.capabilities.join(', ')}\nMonthly Rate: $${phoneNumber.monthly_rate}\nExpires: ${phoneNumber.expires_at ? new Date(phoneNumber.expires_at).toLocaleDateString() : 'No expiration'}`)
    } else {
      alert('Error: ' + response.data.error)
    }
  } catch (error) {
    console.error('Error assigning phone number:', error)
    if (error.response && error.response.data && error.response.data.error) {
      alert('Error: ' + error.response.data.error)
    } else {
      alert('An error occurred while assigning the phone number.')
    }
  } finally {
    assigning.value = false
  }
}

const unassignPhoneNumber = async (phoneNumberId) => {
  if (!confirm('Are you sure you want to unassign this phone number?')) {
    return
  }

  unassigningNumbers.value.push(phoneNumberId)
  try {
    const response = await axios.post(`/users/${props.user.id}/unassign-phone-number`, {
      phone_number_id: phoneNumberId
    })

    if (response.data.success) {
      // Refresh the page to show updated data
      router.reload({ only: ['user'] })
      alert('Phone number unassigned successfully!')
    } else {
      alert('Error: ' + response.data.error)
    }
  } catch (error) {
    console.error('Error unassigning phone number:', error)
    alert('An error occurred while unassigning the phone number.')
  } finally {
    unassigningNumbers.value = unassigningNumbers.value.filter(id => id !== phoneNumberId)
  }
}

const refreshTelnyxNumbers = async () => {
  refreshing.value = true
  try {
    const response = await axios.get('/api/refresh-telnyx-numbers')
    
    if (response.data.success) {
      // Refresh the page to show updated data
      router.reload({ only: ['telnyxNumbers', 'telnyxError'] })
      alert('Telnyx numbers refreshed successfully!')
    } else {
      alert('Error refreshing numbers: ' + response.data.error)
    }
  } catch (error) {
    console.error('Error refreshing Telnyx numbers:', error)
    alert('An error occurred while refreshing Telnyx numbers.')
  } finally {
    refreshing.value = false
  }
}

const togglePhoneNumberDetails = async (phoneNumber) => {
  const isExpanded = expandedNumbers.value.includes(phoneNumber.id)
  
  if (isExpanded) {
    // Collapse the details
    expandedNumbers.value = expandedNumbers.value.filter(id => id !== phoneNumber.id)
  } else {
    // Expand and load details
    expandedNumbers.value.push(phoneNumber.id)
    await loadPhoneNumberDetails(phoneNumber)
  }
}

const loadPhoneNumberDetails = async (phoneNumber) => {
  if (phoneNumberDetails.value[phoneNumber.id]) {
    return // Already loaded
  }

  loadingDetails.value[phoneNumber.id] = true
  
  try {
    // Load Telnyx details
    const telnyxResponse = await axios.get('/api/telnyx-number-details', {
      params: { telnyx_id: phoneNumber.telynx_id }
    })

    // Load usage statistics
    const usageResponse = await axios.get(`/users/${props.user.id}/phone-number-usage`, {
      params: { phone_number_id: phoneNumber.id }
    })

    phoneNumberDetails.value[phoneNumber.id] = {
      telnyx_data: telnyxResponse.data.success ? telnyxResponse.data.telnyx_data : null,
      local_data: telnyxResponse.data.success ? telnyxResponse.data.local_data : null,
      statistics: usageResponse.data.success ? usageResponse.data.statistics : null,
      recent_calls: usageResponse.data.success ? usageResponse.data.recent_calls : []
    }

  } catch (error) {
    console.error('Error loading phone number details:', error)
    phoneNumberDetails.value[phoneNumber.id] = {
      error: 'Failed to load details'
    }
  } finally {
    loadingDetails.value[phoneNumber.id] = false
  }
}

const toggleTelnyxNumberDetails = (number) => {
  const isExpanded = expandedTelnyxNumbers.value.includes(number.id)
  
  if (isExpanded) {
    expandedTelnyxNumbers.value = expandedTelnyxNumbers.value.filter(id => id !== number.id)
  } else {
    expandedTelnyxNumbers.value.push(number.id)
  }
}

const isNumberAssigned = (telnyxId) => {
  // Check if this Telnyx number is assigned to any user in our local database
  return props.user.phone_numbers?.some(phoneNumber => phoneNumber.telynx_id === telnyxId) || false
}
</script>
