<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit SIP Trunk: {{ sipTrunk.name }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
           

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <form @submit.prevent="submit" class="space-y-8">
                            <!-- Basic Information -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel for="name" value="SIP Trunk Name" />
                                        <TextInput
                                            id="name"
                                            v-model="form.name"
                                            type="text"
                                            class="mt-1 block w-full"
                                        />
                                        <InputError :message="form.errors.name" class="mt-2" />
                                    </div>

                                    <div class="md:col-span-2">
                                        <InputLabel for="webhook_url" value="Webhook URL" />
                                        <TextInput
                                            id="webhook_url"
                                            v-model="form.webhook_url"
                                            type="url"
                                            class="mt-1 block w-full"
                                            placeholder="https://your-domain.com/webhook"
                                        />
                                        <InputError :message="form.errors.webhook_url" class="mt-2" />
                                    </div>

                                    <div class="md:col-span-2">
                                        <InputLabel for="notes" value="Notes" />
                                        <textarea
                                            id="notes"
                                            v-model="form.notes"
                                            rows="3"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Optional notes about this SIP trunk"
                                        ></textarea>
                                        <InputError :message="form.errors.notes" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <!-- Credentials -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Credentials</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel for="user_name" value="Username" />
                                        <TextInput
                                            id="user_name"
                                            v-model="form.user_name"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="myusername123"
                                        />
                                        <InputError :message="form.errors.user_name" class="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel for="password" value="Password" />
                                        <TextInput
                                            id="password"
                                            v-model="form.password"
                                            type="password"
                                            class="mt-1 block w-full"
                                            placeholder="my123secure456password789"
                                        />
                                        <InputError :message="form.errors.password" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <!-- Advanced Settings -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Advanced Settings</h3>
                                
                                <!-- General Settings -->
                                <div class="mb-6">
                                    <h4 class="text-md font-medium text-gray-700 mb-3">General</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <InputLabel for="anchorsite_override" value="Anchor Site Override" />
                                            <select
                                                id="anchorsite_override"
                                                v-model="form.anchorsite_override"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default</option>
                                                <option v-for="(label, value) in configurationOptions.anchorsite_override" :key="value" :value="value">
                                                    {{ label }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <InputLabel for="sip_uri_calling_preference" value="SIP URI Calling" />
                                            <select
                                                id="sip_uri_calling_preference"
                                                v-model="form.sip_uri_calling_preference"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default</option>
                                                <option v-for="(label, value) in configurationOptions.sip_uri_calling_preference" :key="value" :value="value">
                                                    {{ label }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <InputLabel for="dtmf_type" value="DTMF Type" />
                                            <select
                                                id="dtmf_type"
                                                v-model="form.dtmf_type"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default</option>
                                                <option v-for="(label, value) in configurationOptions.dtmf_type" :key="value" :value="value">
                                                    {{ label }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Security Settings -->
                                <div class="mb-6">
                                    <h4 class="text-md font-medium text-gray-700 mb-3">Security</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel for="encrypted_media" value="Encrypted Media" />
                                            <select
                                                id="encrypted_media"
                                                v-model="form.encrypted_media"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default</option>
                                                <option v-for="(label, value) in configurationOptions.encrypted_media" :key="value" :value="value">
                                                    {{ label }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="onnet_t38_passthrough_enabled"
                                                v-model="form.onnet_t38_passthrough_enabled"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <InputLabel for="onnet_t38_passthrough_enabled" value="T38 Passthrough" class="ml-2" />
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="default_on_hold_comfort_noise_enabled"
                                                v-model="form.default_on_hold_comfort_noise_enabled"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <InputLabel for="default_on_hold_comfort_noise_enabled" value="Comfort Noise on Hold" class="ml-2" />
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="encode_contact_header_enabled"
                                                v-model="form.encode_contact_header_enabled"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <InputLabel for="encode_contact_header_enabled" value="Encode Contact Header" class="ml-2" />
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="third_party_control_enabled"
                                                v-model="form.third_party_control_enabled"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <InputLabel for="third_party_control_enabled" value="Third Party Control" class="ml-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="noise_suppression" value="Noise Suppression" />
                                            <select
                                                id="noise_suppression"
                                                v-model="form.noise_suppression"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default</option>
                                                <option v-for="(label, value) in configurationOptions.noise_suppression" :key="value" :value="value">
                                                    {{ label }}
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Webhook Settings -->
                                <div class="mb-6">
                                    <h4 class="text-md font-medium text-gray-700 mb-3">Webhook Configuration</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <InputLabel for="webhook_failover_url" value="Failover URL" />
                                            <TextInput
                                                id="webhook_failover_url"
                                                v-model="form.webhook_failover_url"
                                                type="url"
                                                class="mt-1 block w-full"
                                                placeholder="https://failover.example.com"
                                            />
                                            <InputError :message="form.errors.webhook_failover_url" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="webhook_api_version" value="API Version" />
                                            <select
                                                id="webhook_api_version"
                                                v-model="form.webhook_api_version"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default (v1)</option>
                                                <option value="1">v1</option>
                                                <option value="2">v2</option>
                                            </select>
                                            <InputError :message="form.errors.webhook_api_version" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="webhook_timeout_secs" value="Timeout (seconds)" />
                                            <TextInput
                                                id="webhook_timeout_secs"
                                                v-model="form.webhook_timeout_secs"
                                                type="number"
                                                min="1"
                                                max="300"
                                                class="mt-1 block w-full"
                                                placeholder="25"
                                            />
                                            <InputError :message="form.errors.webhook_timeout_secs" class="mt-2" />
                                        </div>
                                    </div>
                                </div>

                                <!-- RTCP Settings -->
                                <div class="mb-6">
                                    <h4 class="text-md font-medium text-gray-700 mb-3">RTCP Settings</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <InputLabel for="rtcp_port" value="RTCP Port" />
                                            <select
                                                id="rtcp_port"
                                                v-model="form.rtcp_port"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default</option>
                                                <option v-for="(label, value) in configurationOptions.rtcp_port" :key="value" :value="value">
                                                    {{ label }}
                                                </option>
                                            </select>
                                            <InputError :message="form.errors.rtcp_port" class="mt-2" />
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="rtcp_capture_enabled"
                                                v-model="form.rtcp_capture_enabled"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <InputLabel for="rtcp_capture_enabled" value="RTCP Capture" class="ml-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="rtcp_report_frequency" value="Report Frequency (seconds)" />
                                            <TextInput
                                                id="rtcp_report_frequency"
                                                v-model="form.rtcp_report_frequency"
                                                type="number"
                                                min="1"
                                                max="60"
                                                class="mt-1 block w-full"
                                                placeholder="5"
                                            />
                                            <InputError :message="form.errors.rtcp_report_frequency" class="mt-2" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Inbound Settings -->
                                <div class="mb-6">
                                    <h4 class="text-md font-medium text-gray-700 mb-3">Inbound Settings</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel for="inbound_ani_format" value="ANI Format" />
                                            <select
                                                id="inbound_ani_format"
                                                v-model="form.inbound_ani_format"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default</option>
                                                <option v-for="(label, value) in configurationOptions.inbound_ani_format" :key="value" :value="value">
                                                    {{ label }}
                                                </option>
                                            </select>
                                            <InputError :message="form.errors.inbound_ani_format" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="inbound_dnis_format" value="DNIS Format" />
                                            <select
                                                id="inbound_dnis_format"
                                                v-model="form.inbound_dnis_format"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default</option>
                                                <option v-for="(label, value) in configurationOptions.inbound_dnis_format" :key="value" :value="value">
                                                    {{ label }}
                                                </option>
                                            </select>
                                            <InputError :message="form.errors.inbound_dnis_format" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel value="Inbound Codecs" />
                                            <div class="mt-2 space-y-2">
                                                <div v-for="(label, value) in configurationOptions.inbound_codecs" :key="value" class="flex items-center">
                                                    <input
                                                        :id="'inbound_codec_' + value"
                                                        v-model="form.inbound_codecs"
                                                        :value="value"
                                                        type="checkbox"
                                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    />
                                                    <InputLabel :for="'inbound_codec_' + value" :value="label" class="ml-2 text-sm" />
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.inbound_codecs" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="inbound_routing_method" value="Routing Method" />
                                            <select
                                                id="inbound_routing_method"
                                                v-model="form.inbound_routing_method"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default</option>
                                                <option v-for="(label, value) in configurationOptions.inbound_routing_method" :key="value" :value="value">
                                                    {{ label }}
                                                </option>
                                            </select>
                                            <InputError :message="form.errors.inbound_routing_method" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="inbound_channel_limit" value="Channel Limit" />
                                            <TextInput
                                                id="inbound_channel_limit"
                                                v-model="form.inbound_channel_limit"
                                                type="number"
                                                min="1"
                                                max="100"
                                                class="mt-1 block w-full"
                                                placeholder="10"
                                            />
                                            <InputError :message="form.errors.inbound_channel_limit" class="mt-2" />
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="inbound_ringback_tone"
                                                v-model="form.inbound_ringback_tone"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <InputLabel for="inbound_ringback_tone" value="Generate Ringback Tone" class="ml-2" />
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="inbound_instant_ringback"
                                                v-model="form.inbound_instant_ringback"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <InputLabel for="inbound_instant_ringback" value="Instant Ringback" class="ml-2" />
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="inbound_isup_headers"
                                                v-model="form.inbound_isup_headers"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <InputLabel for="inbound_isup_headers" value="ISUP Headers" class="ml-2" />
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="inbound_prack"
                                                v-model="form.inbound_prack"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <InputLabel for="inbound_prack" value="PRACK Support" class="ml-2" />
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="inbound_sip_compact_headers"
                                                v-model="form.inbound_sip_compact_headers"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <InputLabel for="inbound_sip_compact_headers" value="SIP Compact Headers" class="ml-2" />
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="inbound_shaken_stir"
                                                v-model="form.inbound_shaken_stir"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <InputLabel for="inbound_shaken_stir" value="SHAKEN/STIR" class="ml-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="inbound_simultaneous_ringing" value="Simultaneous Ringing" />
                                            <select
                                                id="inbound_simultaneous_ringing"
                                                v-model="form.inbound_simultaneous_ringing"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default</option>
                                                <option v-for="(label, value) in configurationOptions.inbound_simultaneous_ringing" :key="value" :value="value">
                                                    {{ label }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <InputLabel for="inbound_timeout_1xx" value="1xx Timeout (seconds)" />
                                            <TextInput
                                                id="inbound_timeout_1xx"
                                                v-model="form.inbound_timeout_1xx"
                                                type="number"
                                                min="1"
                                                max="60"
                                                class="mt-1 block w-full"
                                                placeholder="10"
                                            />
                                            <InputError :message="form.errors.inbound_timeout_1xx" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="inbound_timeout_2xx" value="2xx Timeout (seconds)" />
                                            <TextInput
                                                id="inbound_timeout_2xx"
                                                v-model="form.inbound_timeout_2xx"
                                                type="number"
                                                min="1"
                                                max="60"
                                                class="mt-1 block w-full"
                                                placeholder="20"
                                            />
                                            <InputError :message="form.errors.inbound_timeout_2xx" class="mt-2" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Outbound Settings -->
                                <div class="mb-6">
                                    <h4 class="text-md font-medium text-gray-700 mb-3">Outbound Settings</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel for="outbound_ani_override" value="ANI Override" />
                                            <select
                                                id="outbound_ani_override"
                                                v-model="form.outbound_ani_override"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default</option>
                                                <option v-for="(label, value) in configurationOptions.outbound_ani_override" :key="value" :value="value">
                                                    {{ label }}
                                                </option>
                                            </select>
                                            <InputError :message="form.errors.outbound_ani_override" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="outbound_localization" value="Localization" />
                                            <select
                                                id="outbound_localization"
                                                v-model="form.outbound_localization"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default</option>
                                                <option v-for="(label, value) in configurationOptions.outbound_localization" :key="value" :value="value">
                                                    {{ label }}
                                                </option>
                                            </select>
                                            <InputError :message="form.errors.outbound_localization" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel value="Outbound Codecs" />
                                            <div class="mt-2 space-y-2">
                                                <div v-for="(label, value) in configurationOptions.outbound_codecs" :key="value" class="flex items-center">
                                                    <input
                                                        :id="'outbound_codec_' + value"
                                                        v-model="form.outbound_codecs"
                                                        :value="value"
                                                        type="checkbox"
                                                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    />
                                                    <InputLabel :for="'outbound_codec_' + value" :value="label" class="ml-2 text-sm" />
                                                </div>
                                            </div>
                                            <InputError :message="form.errors.outbound_codecs" class="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="outbound_channel_limit" value="Channel Limit" />
                                            <TextInput
                                                id="outbound_channel_limit"
                                                v-model="form.outbound_channel_limit"
                                                type="number"
                                                min="1"
                                                max="100"
                                                class="mt-1 block w-full"
                                                placeholder="10"
                                            />
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="outbound_call_parking"
                                                v-model="form.outbound_call_parking"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <InputLabel for="outbound_call_parking" value="Call Parking" class="ml-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="outbound_ani_override_type" value="ANI Override Type" />
                                            <select
                                                id="outbound_ani_override_type"
                                                v-model="form.outbound_ani_override_type"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default</option>
                                                <option v-for="(label, value) in configurationOptions.outbound_ani_override_type" :key="value" :value="value">
                                                    {{ label }}
                                                </option>
                                            </select>
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="outbound_instant_ringback"
                                                v-model="form.outbound_instant_ringback"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <InputLabel for="outbound_instant_ringback" value="Instant Ringback" class="ml-2" />
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="outbound_ringback_tone"
                                                v-model="form.outbound_ringback_tone"
                                                type="checkbox"
                                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <InputLabel for="outbound_ringback_tone" value="Generate Ringback Tone" class="ml-2" />
                                        </div>

                                        <div>
                                            <InputLabel for="outbound_t38_reinvite_source" value="T38 Reinvite Source" />
                                            <select
                                                id="outbound_t38_reinvite_source"
                                                v-model="form.outbound_t38_reinvite_source"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Default</option>
                                                <option v-for="(label, value) in configurationOptions.outbound_t38_reinvite_source" :key="value" :value="value">
                                                    {{ label }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <InputLabel for="outbound_voice_profile_id" value="Outbound Voice Profile" />
                                            <select
                                                id="outbound_voice_profile_id"
                                                v-model="form.outbound_voice_profile_id"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">No Voice Profile (Default)</option>
                                                <option v-for="profile in voiceProfiles" :key="profile.id" :value="profile.id">
                                                    {{ profile.name }} ({{ profile.traffic_type }} - {{ profile.service_plan }})
                                                </option>
                                            </select>
                                            <p class="mt-1 text-sm text-gray-500">
                                                Select a voice profile to configure outbound call routing and quality settings.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Optional Credentials -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Optional Credentials</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel for="ios_push_credential_id" value="iOS Push Credential ID" />
                                        <TextInput
                                            id="ios_push_credential_id"
                                            v-model="form.ios_push_credential_id"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="Optional iOS push credential UUID"
                                        />
                                        <InputError :message="form.errors.ios_push_credential_id" class="mt-2" />
                                    </div>

                                    <div>
                                        <InputLabel for="android_push_credential_id" value="Android Push Credential ID" />
                                        <TextInput
                                            id="android_push_credential_id"
                                            v-model="form.android_push_credential_id"
                                            type="text"
                                            class="mt-1 block w-full"
                                            placeholder="Optional Android push credential UUID"
                                        />
                                        <InputError :message="form.errors.android_push_credential_id" class="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <!-- Phone Number Assignment -->
                            <div class="bg-gray-50 p-6 rounded-lg">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Phone Number Assignment</h3>
                                <div v-if="filteredAvailablePhoneNumbers.length > 0 || form.phone_numbers.length > 0">
                                    <div class="space-y-4">
                                        <div v-for="(phoneNumber, index) in form.phone_numbers" :key="index" class="flex items-center space-x-4">
                                            <select
                                                v-model="phoneNumber.phone_number_id"
                                                class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Select phone number</option>
                                                <option v-for="pn in filteredAvailablePhoneNumbers" :key="pn.id" :value="pn.id">
                                                    {{ pn.phone_number }} ({{ pn.number_type || 'local' }})
                                                </option>
                                            </select>
                                            <select
                                                v-model="phoneNumber.assignment_type"
                                                class="w-32 border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="primary">Primary</option>
                                                <option value="secondary">Secondary</option>
                                                <option value="backup">Backup</option>
                                            </select>
                                            <button
                                                type="button"
                                                @click="removePhoneNumber(index)"
                                                class="text-red-600 hover:text-red-800"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addPhoneNumber"
                                        class="mt-4 px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
                                    >
                                        Add Phone Number
                                    </button>
                                </div>
                                <div v-else class="text-gray-500">
                                    No phone numbers available for assignment.
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end space-x-4">
                                <Link
                                    :href="route('sip-trunks.show', sipTrunk.id)"
                                    class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton :disabled="form.processing">
                                    Update SIP Trunk
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
import { useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    sipTrunk: Object,
    availablePhoneNumbers: Array,
    configurationOptions: Object,
    voiceProfiles: Array,
})

const form = useForm({
    name: props.sipTrunk.name || '',
    webhook_url: props.sipTrunk.webhook_url || '',
    notes: props.sipTrunk.notes || '',
    password: props.sipTrunk.credentials?.password || '',
    user_name: props.sipTrunk.credentials?.user_name || '',
    anchorsite_override: props.sipTrunk.settings?.anchorsite_override || '',
    sip_uri_calling_preference: props.sipTrunk.settings?.sip_uri_calling_preference || '',
    default_on_hold_comfort_noise_enabled: props.sipTrunk.settings?.default_on_hold_comfort_noise_enabled ?? false,
    dtmf_type: props.sipTrunk.settings?.dtmf_type || '',
    encode_contact_header_enabled: props.sipTrunk.settings?.encode_contact_header_enabled ?? true,
    encrypted_media: props.sipTrunk.settings?.encrypted_media || '',
    onnet_t38_passthrough_enabled: props.sipTrunk.settings?.onnet_t38_passthrough_enabled ?? true,
    third_party_control_enabled: props.sipTrunk.settings?.third_party_control_enabled ?? false,
    noise_suppression: props.sipTrunk.settings?.noise_suppression || '',
    webhook_failover_url: props.sipTrunk.settings?.webhook_failover_url || '',
    webhook_api_version: props.sipTrunk.settings?.webhook_api_version || '',
    webhook_timeout_secs: props.sipTrunk.settings?.webhook_timeout_secs || '',
    rtcp_port: props.sipTrunk.settings?.rtcp_port || '',
    rtcp_capture_enabled: props.sipTrunk.settings?.rtcp_capture_enabled ?? true,
    rtcp_report_frequency: props.sipTrunk.settings?.rtcp_report_frequency || '',
    inbound_ani_format: props.sipTrunk.settings?.inbound_ani_format || '',
    inbound_dnis_format: props.sipTrunk.settings?.inbound_dnis_format || '',
    inbound_codecs: Array.isArray(props.sipTrunk.settings?.inbound_codecs) ? props.sipTrunk.settings.inbound_codecs : (props.sipTrunk.settings?.inbound_codecs ? [props.sipTrunk.settings.inbound_codecs] : []),
    inbound_routing_method: props.sipTrunk.settings?.inbound_routing_method || '',
    inbound_channel_limit: props.sipTrunk.settings?.inbound_channel_limit || '',
    inbound_instant_ringback: props.sipTrunk.settings?.inbound_instant_ringback ?? false,
    inbound_ringback_tone: props.sipTrunk.settings?.inbound_ringback_tone ?? true,
    inbound_isup_headers: props.sipTrunk.settings?.inbound_isup_headers ?? true,
    inbound_prack: props.sipTrunk.settings?.inbound_prack ?? true,
    inbound_sip_compact_headers: props.sipTrunk.settings?.inbound_sip_compact_headers ?? true,
    inbound_simultaneous_ringing: props.sipTrunk.settings?.inbound_simultaneous_ringing || '',
    inbound_timeout_1xx: props.sipTrunk.settings?.inbound_timeout_1xx || '',
    inbound_timeout_2xx: props.sipTrunk.settings?.inbound_timeout_2xx || '',
    inbound_shaken_stir: props.sipTrunk.settings?.inbound_shaken_stir ?? true,
    outbound_call_parking: props.sipTrunk.settings?.outbound_call_parking ?? true,
    outbound_ani_override: props.sipTrunk.settings?.outbound_ani_override || '',
    outbound_ani_override_type: props.sipTrunk.settings?.outbound_ani_override_type || '',
    outbound_channel_limit: props.sipTrunk.settings?.outbound_channel_limit || '',
    outbound_instant_ringback: props.sipTrunk.settings?.outbound_instant_ringback ?? false,
    outbound_ringback_tone: props.sipTrunk.settings?.outbound_ringback_tone ?? true,
    outbound_localization: props.sipTrunk.settings?.outbound_localization || '',
    outbound_codecs: Array.isArray(props.sipTrunk.settings?.outbound_codecs) ? props.sipTrunk.settings.outbound_codecs : (props.sipTrunk.settings?.outbound_codecs ? [props.sipTrunk.settings.outbound_codecs] : []),
    outbound_t38_reinvite_source: props.sipTrunk.settings?.outbound_t38_reinvite_source || '',
    ios_push_credential_id: props.sipTrunk.settings?.ios_push_credential_id || '',
    android_push_credential_id: props.sipTrunk.settings?.android_push_credential_id || '',
    outbound_voice_profile_id: props.sipTrunk.settings?.outbound_voice_profile_id || '',
    phone_numbers: props.sipTrunk.phone_numbers?.map(pn => ({
        phone_number_id: pn.id,
        assignment_type: pn.pivot?.assignment_type || 'primary'
    })) || [],
})

// Computed property to filter available phone numbers
const filteredAvailablePhoneNumbers = computed(() => {
    return props.availablePhoneNumbers.filter(pn => 
        !form.phone_numbers.some(assigned => assigned.phone_number_id === pn.id)
    )
})

const addPhoneNumber = () => {
    form.phone_numbers.push({
        phone_number_id: '',
        assignment_type: 'primary'
    })
}

const removePhoneNumber = (index) => {
    form.phone_numbers.splice(index, 1)
}

const submit = () => {
    form.put(route('sip-trunks.update', props.sipTrunk.id), {
        onError: (errors) => {
            // Show validation errors in toastr
            if (errors.telnyx) {
                window.toastr.error(errors.telnyx);
            } else if (errors.general) {
                window.toastr.error(errors.general);
            } else {
                // Show first validation error
                const firstError = Object.values(errors)[0];
                if (firstError) {
                    window.toastr.error(firstError[0]);
                }
            }
        },
        onSuccess: () => {
            window.toastr.success('SIP trunk updated successfully!');
        }
    })
}
</script>
