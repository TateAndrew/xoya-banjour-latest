<template>
    <Head :title="`Call Recording Settings - ${phoneNumber.phone_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Call Recording Settings
                </h2>
                <Link 
                    :href="route('phone-numbers.manage')" 
                    class="text-sm text-gray-600 hover:text-gray-900"
                >
                    ← Back to Phone Numbers
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <!-- Phone Number Info -->
                        <div class="mb-6 pb-6 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Phone Number</h3>
                            <p class="text-2xl font-semibold text-indigo-600">
                                {{ formatPhoneNumber(phoneNumber.phone_number) }}
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                {{ phoneNumber.city }}{{ phoneNumber.city && phoneNumber.state ? ', ' : '' }}{{ phoneNumber.state }}
                            </p>
                        </div>

                        <!-- Success/Error Messages -->
                        <div v-if="successMessage" class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                {{ successMessage }}
                            </div>
                        </div>

                        <div v-if="errorMessage" class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                </svg>
                                {{ errorMessage }}
                            </div>
                        </div>

                        <!-- Recording Settings Form -->
                        <form @submit.prevent="submitForm">
                            <div class="space-y-6">
                                <!-- Section Header -->
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                        Inbound Call Recording Settings
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        Configure how inbound calls to this number are recorded
                                    </p>
                                </div>

                                <!-- Recording Enabled -->
                                <div>
                                    <label for="recording_enabled" class="block text-sm font-medium text-gray-700 mb-2">
                                        Call Recording Status
                                    </label>
                                    <select
                                        v-model="form.inbound_call_recording_enabled"
                                        id="recording_enabled"
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                                        :class="{ 'border-red-300': form.errors.inbound_call_recording_enabled }"
                                    >
                                        <option :value="true">Enabled</option>
                                        <option :value="false">Disabled</option>
                                    </select>
                                    <p v-if="form.errors.inbound_call_recording_enabled" class="mt-2 text-sm text-red-600">
                                        {{ form.errors.inbound_call_recording_enabled }}
                                    </p>
                                    <p class="mt-2 text-sm text-gray-500">
                                        When enabled, all inbound calls to this number will be recorded
                                    </p>
                                </div>

                                <!-- Recording Format -->
                                <div :class="{ 'opacity-50': !form.inbound_call_recording_enabled }">
                                    <label for="recording_format" class="block text-sm font-medium text-gray-700 mb-2">
                                        Recording Format
                                    </label>
                                    <select
                                        v-model="form.inbound_call_recording_format"
                                        id="recording_format"
                                        :disabled="!form.inbound_call_recording_enabled"
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        :class="{ 'border-red-300': form.errors.inbound_call_recording_format }"
                                    >
                                        <option value="wav">WAV (Uncompressed)</option>
                                        <option value="mp3">MP3 (Compressed)</option>
                                    </select>
                                    <p v-if="form.errors.inbound_call_recording_format" class="mt-2 text-sm text-red-600">
                                        {{ form.errors.inbound_call_recording_format }}
                                    </p>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Choose the audio format for your recordings. WAV offers higher quality but larger files, MP3 offers smaller files with good quality.
                                    </p>
                                </div>

                                <!-- Recording Channels -->
                                <div :class="{ 'opacity-50': !form.inbound_call_recording_enabled }">
                                    <label for="recording_channels" class="block text-sm font-medium text-gray-700 mb-2">
                                        Recording Channels
                                    </label>
                                    <select
                                        v-model="form.inbound_call_recording_channels"
                                        id="recording_channels"
                                        :disabled="!form.inbound_call_recording_enabled"
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        :class="{ 'border-red-300': form.errors.inbound_call_recording_channels }"
                                    >
                                        <option value="single">Single (Mixed)</option>
                                        <option value="dual">Dual (Separate Channels)</option>
                                    </select>
                                    <p v-if="form.errors.inbound_call_recording_channels" class="mt-2 text-sm text-red-600">
                                        {{ form.errors.inbound_call_recording_channels }}
                                    </p>
                                    <p class="mt-2 text-sm text-gray-500">
                                        Single channel mixes both parties into one audio track. Dual channel records each party on a separate channel for easier analysis.
                                    </p>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="mt-8 pt-6 border-t border-gray-200 flex items-center justify-between">
                                <Link 
                                    :href="route('phone-numbers.manage')" 
                                    class="text-sm text-gray-600 hover:text-gray-900"
                                >
                                    Cancel
                                </Link>
                                
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex justify-center items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ form.processing ? 'Saving...' : 'Save Settings' }}
                                </button>
                            </div>
                        </form>

                        <!-- Info Box -->
                        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">Important Information</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <ul class="list-disc pl-5 space-y-1">
                                            <li>Call recordings may be subject to legal requirements in your jurisdiction</li>
                                            <li>Ensure you have proper consent before recording calls</li>
                                            <li>Recording settings apply only to inbound calls to this number</li>
                                            <li>Changes take effect immediately for new calls</li>
                                        </ul>
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
import { ref, computed, watch } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    phoneNumber: {
        type: Object,
        required: true
    }
})

const page = usePage()
const successMessage = ref('')
const errorMessage = ref('')

const form = useForm({
    inbound_call_recording_enabled: props.phoneNumber.inbound_call_recording_enabled || false,
    inbound_call_recording_format: props.phoneNumber.inbound_call_recording_format || 'wav',
    inbound_call_recording_channels: props.phoneNumber.inbound_call_recording_channels || 'single',
})

// Watch for flash messages from Laravel
watch(() => page.props.flash, (flash) => {
    if (flash?.success) {
        successMessage.value = flash.success
        setTimeout(() => {
            successMessage.value = ''
        }, 5000)
    }
    if (flash?.error) {
        errorMessage.value = flash.error
        setTimeout(() => {
            errorMessage.value = ''
        }, 5000)
    }
}, { deep: true, immediate: true })

const submitForm = () => {
    successMessage.value = ''
    errorMessage.value = ''
    
    form.put(route('phone-numbers.update-recording-settings', props.phoneNumber.id), {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = 'Recording settings updated successfully!'
            setTimeout(() => {
                successMessage.value = ''
            }, 5000)
        },
        onError: () => {
            if (form.errors.error) {
                errorMessage.value = form.errors.error
            } else {
                errorMessage.value = 'Failed to update recording settings. Please check the form and try again.'
            }
            setTimeout(() => {
                errorMessage.value = ''
            }, 5000)
        }
    })
}

const formatPhoneNumber = (number) => {
    if (!number) return ''
    return number.replace(/(\d{1})(\d{3})(\d{3})(\d{4})/, '+$1 ($2) $3-$4')
}
</script>

