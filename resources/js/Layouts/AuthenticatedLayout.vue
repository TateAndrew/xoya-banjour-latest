<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav
                class="border-b border-gray-100 bg-white"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-16 justify-between">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationLogo
                                        class="block h-9 w-auto fill-current text-gray-800"
                                    />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex"
                            >
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                >
                                    Dashboard
                                </NavLink>
                                <NavLink
                                    :href="route('dialer')"
                                    :active="route().current('dialer')"
                                >
                                    Dialer
                                </NavLink>
                                
                                <!-- Call Management Dropdown -->
                                <div class="hidden sm:flex sm:items-center sm:ms-6 relative">
                                    <Dropdown align="left" width="48">
                                        <template #trigger>
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none"
                                                :class="route().current('recordings.*') || route().current('transcriptions.*') 
                                                    ? 'border-indigo-400 text-gray-900 focus:border-indigo-700' 
                                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:text-gray-700 focus:border-gray-300'"
                                            >
                                                🎙️ Call Management
                                                <svg
                                                    class="ms-2 -me-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </template>

                                        <template #content>
                                            <DropdownLink :href="route('recordings.index')">
                                                <div class="flex items-center space-x-2">
                                                    <span>📹</span>
                                                    <span>Recordings</span>
                                                </div>
                                            </DropdownLink>
                                            <DropdownLink :href="route('transcriptions.index')">
                                                <div class="flex items-center space-x-2">
                                                    <span>📝</span>
                                                    <span>Transcriptions</span>
                                                </div>
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                                
                                <NavLink
                                    :href="route('phone-numbers.index')"
                                    :active="route().current('phone-numbers.*')"
                                >
                                    Phone Numbers
                                </NavLink>
                                <NavLink
                                    :href="route('sip-trunks.index')"
                                    :active="route().current('sip-trunks.*')"
                                >
                                    SIP Trunks
                                </NavLink>
                                <NavLink
                                    :href="route('messaging-profiles.index')"
                                    :active="route().current('messaging-profiles.*')"
                                >
                                    Messaging Profiles
                                </NavLink>
                                <NavLink
                                    :href="route('outbound-voice-profiles.index')"
                                    :active="route().current('outbound-voice-profiles.*')"
                                >
                                    Voice Profiles
                                </NavLink>
                                <NavLink
                                    :href="route('messenger.index')"
                                    :active="route().current('messenger.*')"
                                >
                                    SMS Messenger
                                </NavLink>
                               
                                <NavLink
                                    :href="route('users.index')"
                                    :active="route().current('users.*')"
                                >
                                    Users
                                </NavLink>
                                
                                <!-- Administration Dropdown -->
                                <div class="hidden sm:flex sm:items-center sm:ms-6 relative">
                                    <Dropdown align="left" width="48">
                                        <template #trigger>
                                            <button
                                                type="button"
                                                class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none"
                                                :class="route().current('roles.*') || route().current('permissions.*') 
                                                    ? 'border-indigo-400 text-gray-900 focus:border-indigo-700' 
                                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:text-gray-700 focus:border-gray-300'"
                                            >
                                                🔐 Admin
                                                <svg
                                                    class="ms-2 -me-0.5 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </template>

                                        <template #content>
                                            <DropdownLink :href="route('roles.index')">
                                                <div class="flex items-center space-x-2">
                                                    <span>👥</span>
                                                    <span>Roles</span>
                                                </div>
                                            </DropdownLink>
                                            <DropdownLink :href="route('permissions.index')">
                                                <div class="flex items-center space-x-2">
                                                    <span>🔑</span>
                                                    <span>Permissions</span>
                                                </div>
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </div>

                        <div class="hidden sm:ms-6 sm:flex sm:items-center">
                            <!-- Settings Dropdown -->
                            <div class="relative ms-3">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                            >
                                                {{ $page.props.auth.user.name }}

                                                <svg
                                                    class="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <DropdownLink
                                            :href="route('profile.edit')"
                                        >
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink
                                            :href="route('logout')"
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button
                                @click="
                                    showingNavigationDropdown =
                                        !showingNavigationDropdown
                                "
                                class="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                            >
                                <svg
                                    class="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            hidden: showingNavigationDropdown,
                                            'inline-flex':
                                                !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{
                                            hidden: !showingNavigationDropdown,
                                            'inline-flex':
                                                showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div
                    :class="{
                        block: showingNavigationDropdown,
                        hidden: !showingNavigationDropdown,
                    }"
                    class="sm:hidden"
                >
                    <div class="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                        >
                            Dashboard
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('dialer')"
                            :active="route().current('dialer')"
                        >
                            Dialer
                        </ResponsiveNavLink>
                        
                        <!-- Call Management Section -->
                        <div class="px-4 py-2">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                🎙️ Call Management
                            </div>
                        </div>
                        <ResponsiveNavLink
                            :href="route('recordings.index')"
                            :active="route().current('recordings.*')"
                            class="pl-8"
                        >
                            📹 Recordings
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('transcriptions.index')"
                            :active="route().current('transcriptions.*')"
                            class="pl-8"
                        >
                            📝 Transcriptions
                        </ResponsiveNavLink>
                        
                        <ResponsiveNavLink
                            :href="route('phone-numbers.index')"
                            :active="route().current('phone-numbers.*')"
                        >
                            Phone Numbers
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('sip-trunks.index')"
                            :active="route().current('sip-trunks.*')"
                        >
                            SIP Trunks
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('messaging-profiles.index')"
                            :active="route().current('messaging-profiles.*')"
                        >
                            Messaging Profiles
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('outbound-voice-profiles.index')"
                            :active="route().current('outbound-voice-profiles.*')"
                        >
                            Voice Profiles
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('messenger.index')"
                            :active="route().current('messenger.*')"
                        >
                            SMS Messenger
                        </ResponsiveNavLink>
                      
                        <ResponsiveNavLink
                            :href="route('users.index')"
                            :active="route().current('users.*')"
                        >
                            Users
                        </ResponsiveNavLink>
                        
                        <!-- Administration Section -->
                        <div class="px-4 py-2">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                🔐 Administration
                            </div>
                        </div>
                        <ResponsiveNavLink
                            :href="route('roles.index')"
                            :active="route().current('roles.*')"
                            class="pl-8"
                        >
                            👥 Roles
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            :href="route('permissions.index')"
                            :active="route().current('permissions.*')"
                            class="pl-8"
                        >
                            🔑 Permissions
                        </ResponsiveNavLink>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div
                        class="border-t border-gray-200 pb-1 pt-4"
                    >
                        <div class="px-4">
                            <div
                                class="text-base font-medium text-gray-800"
                            >
                                {{ $page.props.auth.user.name }}
                            </div>
                            <div class="text-sm font-medium text-gray-500">
                                {{ $page.props.auth.user.email }}
                            </div>
                        </div>

                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.edit')">
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                :href="route('logout')"
                                method="post"
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header
                class="bg-white shadow"
                v-if="$slots.header"
            >
                <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
