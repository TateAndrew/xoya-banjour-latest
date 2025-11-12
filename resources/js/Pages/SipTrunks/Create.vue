<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardDescription from '@/Components/ui/CardDescription.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Label from '@/Components/ui/Label.vue'
import Switch from '@/Components/ui/Switch.vue'
import Select from '@/Components/ui/Select.vue'
import SelectTrigger from '@/Components/ui/SelectTrigger.vue'
import SelectContent from '@/Components/ui/SelectContent.vue'
import SelectItem from '@/Components/ui/SelectItem.vue'
import SelectValue from '@/Components/ui/SelectValue.vue'
import Separator from '@/Components/ui/Separator.vue'
import { ArrowLeft, Info, Plus, Trash2 } from 'lucide-vue-next'

const props = defineProps({
  phoneNumbers: {
    type: Array,
    default: () => []
  },
  configurationOptions: {
    type: Object,
    default: () => ({})
  },
  voiceProfiles: {
    type: Array,
    default: () => []
  }
})

const form = useForm({
  name: '',
  webhook_url: `${window.location.origin}/webhook/call`,
  webhook_failover_url: '',
  webhook_api_version: '',
  webhook_timeout_secs: '',
  notes: '',
  user_name: '',
  password: '',
  anchorsite_override: '',
  sip_uri_calling_preference: '',
  dtmf_type: '',
  encrypted_media: '',
  noise_suppression: '',
  onnet_t38_passthrough_enabled: true,
  default_on_hold_comfort_noise_enabled: false,
  encode_contact_header_enabled: true,
  third_party_control_enabled: false,
  inbound_ani_format: '',
  inbound_dnis_format: '',
  inbound_routing_method: '',
  inbound_channel_limit: '',
  inbound_codecs: [],
  inbound_ringback_tone: true,
  inbound_instant_ringback: false,
  inbound_isup_headers: true,
  inbound_prack: true,
  inbound_sip_compact_headers: true,
  inbound_simultaneous_ringing: '',
  inbound_timeout_1xx: '',
  inbound_timeout_2xx: '',
  inbound_shaken_stir: true,
  outbound_call_parking: true,
  outbound_ani_override: '',
  outbound_ani_override_type: '',
  outbound_channel_limit: '',
  outbound_instant_ringback: true,
  outbound_ringback_tone: true,
  outbound_localization: '',
  outbound_codecs: [],
  outbound_t38_reinvite_source: '',
  rtcp_port: '',
  rtcp_capture_enabled: true,
  rtcp_report_frequency: '',
  ios_push_credential_id: '',
  android_push_credential_id: '',
  outbound_voice_profile_id: '',
  phone_numbers: []
})

const configuration = computed(() => props.configurationOptions || {})

const errorsPresent = computed(() => Object.keys(form.errors || {}).length > 0)

const toggleArrayValue = (field, value) => {
  const current = Array.isArray(form[field]) ? [...form[field]] : []
  if (current.includes(value)) {
    form[field] = current.filter((item) => item !== value)
  } else {
    form[field] = [...current, value]
  }
}

const selectedNumberIds = computed(() =>
  form.phone_numbers
    .map((entry) => entry.phone_number_id)
    .filter((value) => value !== null && value !== undefined && value !== '')
)

const isNumberDisabled = (id, currentIndex) => {
  const idString = String(id)
  return selectedNumberIds.value.some((value, index) => index !== currentIndex && value === idString)
}

const addPhoneNumber = () => {
  form.phone_numbers.push({
    phone_number_id: '',
    assignment_type: 'primary'
  })
}

const removePhoneNumber = (index) => {
  form.phone_numbers.splice(index, 1)
}

const updatePhoneNumberId = (index, value) => {
  form.phone_numbers[index].phone_number_id = value ?? ''
}

const submit = () => {
  form
    .transform((data) => ({
      ...data,
      phone_numbers: data.phone_numbers.filter(
        (entry) => entry.phone_number_id !== null && entry.phone_number_id !== ''
      )
    }))
    .post(route('sip-trunks.store'), {
      onError: (errors) => {
        if (errors.telnyx) {
          window.toastr.error(errors.telnyx)
        } else if (errors.general) {
          window.toastr.error(errors.general)
        } else {
          const firstError = Object.values(errors)[0]
          if (Array.isArray(firstError)) {
            window.toastr.error(firstError[0])
          }
        }
      },
      onSuccess: () => {
        window.toastr.success('SIP trunk created successfully!')
      },
      onFinish: () => {
        form.transform((data) => data)
      }
    })
}
</script>

<template>
  <DashboardLayout>
    <Head title="Create SIP Trunk" />

    <template #header>
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Create SIP Trunk</h1>
          <p class="text-sm text-muted-foreground">
            Configure carrier routing, media preferences, and failover automation for outbound and inbound calls.
          </p>
        </div>
        <Link
          :href="route('sip-trunks.index')"
          class="inline-flex items-center gap-2 text-sm text-muted-foreground transition hover:text-foreground"
        >
          <ArrowLeft class="h-4 w-4" />
          Back to SIP Trunks
        </Link>
      </div>
    </template>

    <form class="space-y-6 pb-12" @submit.prevent="submit">
      <div
        v-if="errorsPresent"
        class="rounded-lg border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive"
      >
        <p class="font-medium">Please resolve the highlighted issues.</p>
        <ul class="mt-2 list-disc pl-4">
          <li v-for="(error, key) in form.errors" :key="key">
            {{ Array.isArray(error) ? error[0] : error }}
          </li>
        </ul>
      </div>

      <div class="grid gap-6 xl:grid-cols-[2fr_1fr]">
        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Basic Configuration</CardTitle>
              <CardDescription>Identify the trunk and surface key routing metadata.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <div class="space-y-2">
                <Label for="name">SIP Trunk Name</Label>
                <Input
                  id="name"
                  v-model="form.name"
                  placeholder="Customer Support Trunk"
                  required
                />
                <p v-if="form.errors.name" class="text-xs text-destructive">
                  {{ form.errors.name }}
                </p>
              </div>

              <div class="space-y-2">
                <Label for="webhook_url">Webhook URL</Label>
                <Input
                  id="webhook_url"
                  v-model="form.webhook_url"
                  type="url"
                  placeholder="https://example.com/webhook/call"
                />
                <p class="text-xs text-muted-foreground">
                  Telnyx will notify this endpoint for inbound call events and status callbacks.
                </p>
                <p v-if="form.errors.webhook_url" class="text-xs text-destructive">
                  {{ form.errors.webhook_url }}
                </p>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <Label for="webhook_failover_url">Failover URL</Label>
                  <Input
                    id="webhook_failover_url"
                    v-model="form.webhook_failover_url"
                    type="url"
                    placeholder="https://failover.example.com/calls"
                  />
                  <p v-if="form.errors.webhook_failover_url" class="text-xs text-destructive">
                    {{ form.errors.webhook_failover_url }}
                  </p>
                </div>
                <div class="space-y-2">
                  <Label>API Version</Label>
                  <Select
                    :model-value="form.webhook_api_version || undefined"
                    @update:modelValue="(value) => (form.webhook_api_version = value ?? '')"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Default (v1)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="1">v1</SelectItem>
                      <SelectItem value="2">v2</SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="form.errors.webhook_api_version" class="text-xs text-destructive">
                    {{ form.errors.webhook_api_version }}
                  </p>
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <Label for="webhook_timeout_secs">Webhook Timeout (seconds)</Label>
                  <Input
                    id="webhook_timeout_secs"
                    v-model="form.webhook_timeout_secs"
                    type="number"
                    min="1"
                    max="300"
                    placeholder="25"
                  />
                  <p v-if="form.errors.webhook_timeout_secs" class="text-xs text-destructive">
                    {{ form.errors.webhook_timeout_secs }}
                  </p>
                </div>
                <div class="space-y-2">
                  <Label for="notes">Operational Notes</Label>
                  <textarea
                    id="notes"
                    v-model="form.notes"
                    rows="3"
                    class="min-h-[88px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    placeholder="Document owner teams, planned traffic types, or escalation runbooks."
                  ></textarea>
                  <p v-if="form.errors.notes" class="text-xs text-destructive">
                    {{ form.errors.notes }}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Authentication</CardTitle>
              <CardDescription>Credentials are provisioned to endpoints that will register with Telnyx.</CardDescription>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2">
              <div class="space-y-2">
                <Label for="user_name">Username</Label>
                <Input
                  id="user_name"
                  v-model="form.user_name"
                  placeholder="acme-support"
                />
                <p v-if="form.errors.user_name" class="text-xs text-destructive">
                  {{ form.errors.user_name }}
                </p>
              </div>
              <div class="space-y-2">
                <Label for="password">Password</Label>
                <Input
                  id="password"
                  v-model="form.password"
                  type="password"
                  placeholder="Generate a strong credential"
                />
                <p v-if="form.errors.password" class="text-xs text-destructive">
                  {{ form.errors.password }}
                </p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Media &amp; Signalling</CardTitle>
              <CardDescription>Adjust global SIP defaults for anchoring, codecs, and resiliency.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <div class="grid gap-4 md:grid-cols-3">
                <div class="space-y-2">
                  <Label>Anchor Site Override</Label>
                  <Select
                    :model-value="form.anchorsite_override || undefined"
                    @update:modelValue="(value) => (form.anchorsite_override = value ?? '')"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Auto-select" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="(label, value) in configuration.anchorsite_override || {}"
                        :key="value"
                        :value="value"
                      >
                        {{ label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-2">
                  <Label>SIP URI Calling</Label>
                  <Select
                    :model-value="form.sip_uri_calling_preference || undefined"
                    @update:modelValue="(value) => (form.sip_uri_calling_preference = value ?? '')"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Telnyx default" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="(label, value) in configuration.sip_uri_calling_preference || {}"
                        :key="value"
                        :value="value"
                      >
                        {{ label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-2">
                  <Label>DTMF Type</Label>
                  <Select
                    :model-value="form.dtmf_type || undefined"
                    @update:modelValue="(value) => (form.dtmf_type = value ?? '')"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Provider default" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="(label, value) in configuration.dtmf_type || {}"
                        :key="value"
                        :value="value"
                      >
                        {{ label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <Label>Encrypted Media</Label>
                  <Select
                    :model-value="form.encrypted_media || undefined"
                    @update:modelValue="(value) => (form.encrypted_media = value ?? '')"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Negotiated automatically" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="(label, value) in configuration.encrypted_media || {}"
                        :key="value"
                        :value="value"
                      >
                        {{ label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-2">
                  <Label>Noise Suppression</Label>
                  <Select
                    :model-value="form.noise_suppression || undefined"
                    @update:modelValue="(value) => (form.noise_suppression = value ?? '')"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Disabled" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="(label, value) in configuration.noise_suppression || {}"
                        :key="value"
                        :value="value"
                      >
                        {{ label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                  <div>
                    <p class="text-sm font-medium text-foreground">T.38 Passthrough</p>
                    <p class="text-xs text-muted-foreground">Allow Fax over IP negotiation on-net.</p>
                  </div>
                  <Switch v-model:checked="form.onnet_t38_passthrough_enabled" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                  <div>
                    <p class="text-sm font-medium text-foreground">Comfort Noise on Hold</p>
                    <p class="text-xs text-muted-foreground">Inject white noise to callers placed on hold.</p>
                  </div>
                  <Switch v-model:checked="form.default_on_hold_comfort_noise_enabled" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                  <div>
                    <p class="text-sm font-medium text-foreground">Encode Contact Header</p>
                    <p class="text-xs text-muted-foreground">Protect SIP contact header values from tampering.</p>
                  </div>
                  <Switch v-model:checked="form.encode_contact_header_enabled" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                  <div>
                    <p class="text-sm font-medium text-foreground">Third-Party Control</p>
                    <p class="text-xs text-muted-foreground">Enable middleware to orchestrate call control events.</p>
                  </div>
                  <Switch v-model:checked="form.third_party_control_enabled" />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Inbound Behaviour</CardTitle>
              <CardDescription>Tailor how Telnyx formats identities, rings endpoints, and enforces compliance.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <Label>ANI Format</Label>
                  <Select
                    :model-value="form.inbound_ani_format || undefined"
                    @update:modelValue="(value) => (form.inbound_ani_format = value ?? '')"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Default (+E164)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="(label, value) in configuration.inbound_ani_format || {}"
                        :key="value"
                        :value="value"
                      >
                        {{ label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="form.errors.inbound_ani_format" class="text-xs text-destructive">
                    {{ form.errors.inbound_ani_format }}
                  </p>
                </div>
                <div class="space-y-2">
                  <Label>DNIS Format</Label>
                  <Select
                    :model-value="form.inbound_dnis_format || undefined"
                    @update:modelValue="(value) => (form.inbound_dnis_format = value ?? '')"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Default (+E164)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="(label, value) in configuration.inbound_dnis_format || {}"
                        :key="value"
                        :value="value"
                      >
                        {{ label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="form.errors.inbound_dnis_format" class="text-xs text-destructive">
                    {{ form.errors.inbound_dnis_format }}
                  </p>
                </div>
              </div>

              <div class="space-y-3">
                <Label>Inbound Codecs</Label>
                <div class="flex flex-wrap gap-2">
                  <Button
                    v-for="(label, value) in configuration.inbound_codecs || {}"
                    :key="value"
                    type="button"
                    size="sm"
                    :variant="form.inbound_codecs.includes(value) ? 'secondary' : 'outline'"
                    @click="toggleArrayValue('inbound_codecs', value)"
                  >
                    {{ label }}
                  </Button>
                </div>
                <p v-if="form.errors.inbound_codecs" class="text-xs text-destructive">
                  {{ form.errors.inbound_codecs }}
                </p>
              </div>

              <div class="grid gap-4 md:grid-cols-3">
                <div class="space-y-2">
                  <Label>Routing Method</Label>
                  <Select
                    :model-value="form.inbound_routing_method || undefined"
                    @update:modelValue="(value) => (form.inbound_routing_method = value ?? '')"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Priority order" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="(label, value) in configuration.inbound_routing_method || {}"
                        :key="value"
                        :value="value"
                      >
                        {{ label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="form.errors.inbound_routing_method" class="text-xs text-destructive">
                    {{ form.errors.inbound_routing_method }}
                  </p>
                </div>
                <div class="space-y-2">
                  <Label for="inbound_channel_limit">Channel Limit</Label>
                  <Input
                    id="inbound_channel_limit"
                    v-model="form.inbound_channel_limit"
                    type="number"
                    min="1"
                    placeholder="Unlimited"
                  />
                  <p v-if="form.errors.inbound_channel_limit" class="text-xs text-destructive">
                    {{ form.errors.inbound_channel_limit }}
                  </p>
                </div>
                <div class="space-y-2">
                  <Label for="inbound_simultaneous_ringing">Simultaneous Ringing</Label>
                  <Input
                    id="inbound_simultaneous_ringing"
                    v-model="form.inbound_simultaneous_ringing"
                    type="number"
                    min="0"
                    placeholder="0"
                  />
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                  <div>
                    <p class="text-sm font-medium text-foreground">Generate Ringback Tone</p>
                    <p class="text-xs text-muted-foreground">Play a ringback tone while endpoints alert.</p>
                  </div>
                  <Switch v-model:checked="form.inbound_ringback_tone" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                  <div>
                    <p class="text-sm font-medium text-foreground">Instant Ringback</p>
                    <p class="text-xs text-muted-foreground">Return ringback tone immediately upon call setup.</p>
                  </div>
                  <Switch v-model:checked="form.inbound_instant_ringback" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                  <div>
                    <p class="text-sm font-medium text-foreground">Include ISUP Headers</p>
                    <p class="text-xs text-muted-foreground">Deliver SS7 signalling information for inbound calls.</p>
                  </div>
                  <Switch v-model:checked="form.inbound_isup_headers" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                  <div>
                    <p class="text-sm font-medium text-foreground">Require PRACK</p>
                    <p class="text-xs text-muted-foreground">Acknowledge provisional responses to ensure call progress.</p>
                  </div>
                  <Switch v-model:checked="form.inbound_prack" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                  <div>
                    <p class="text-sm font-medium text-foreground">Compact SIP Headers</p>
                    <p class="text-xs text-muted-foreground">Use abbreviated header names to reduce payload size.</p>
                  </div>
                  <Switch v-model:checked="form.inbound_sip_compact_headers" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                  <div>
                    <p class="text-sm font-medium text-foreground">SHAKEN/STIR</p>
                    <p class="text-xs text-muted-foreground">Maintain caller authentication attestations.</p>
                  </div>
                  <Switch v-model:checked="form.inbound_shaken_stir" />
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <Label for="inbound_timeout_1xx">Provisional Timeout (1xx)</Label>
                  <Input
                    id="inbound_timeout_1xx"
                    v-model="form.inbound_timeout_1xx"
                    type="number"
                    min="1"
                    placeholder="e.g. 60"
                  />
                </div>
                <div class="space-y-2">
                  <Label for="inbound_timeout_2xx">Answer Timeout (2xx)</Label>
                  <Input
                    id="inbound_timeout_2xx"
                    v-model="form.inbound_timeout_2xx"
                    type="number"
                    min="1"
                    placeholder="e.g. 120"
                  />
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Outbound Behaviour</CardTitle>
              <CardDescription>Set localisation, caller ID policies, and codec preferences for outbound traffic.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <div class="grid gap-4 md:grid-cols-3">
                <div class="space-y-2">
                  <Label for="outbound_ani_override">ANI Override</Label>
                  <Input
                    id="outbound_ani_override"
                    v-model="form.outbound_ani_override"
                    placeholder="+11234567890"
                  />
                  <p v-if="form.errors.outbound_ani_override" class="text-xs text-destructive">
                    {{ form.errors.outbound_ani_override }}
                  </p>
                </div>
                <div class="space-y-2">
                  <Label>Override Type</Label>
                  <Select
                    :model-value="form.outbound_ani_override_type || undefined"
                    @update:modelValue="(value) => (form.outbound_ani_override_type = value ?? '')"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Automatic" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="(label, value) in configuration.outbound_ani_override_type || {}"
                        :key="value"
                        :value="value"
                      >
                        {{ label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <div class="space-y-2">
                  <Label>Outbound Localisation</Label>
                  <Select
                    :model-value="form.outbound_localization || undefined"
                    @update:modelValue="(value) => (form.outbound_localization = value ?? '')"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="None" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="(label, value) in configuration.outbound_localization || {}"
                        :key="value"
                        :value="value"
                      >
                        {{ label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                  <p v-if="form.errors.outbound_localization" class="text-xs text-destructive">
                    {{ form.errors.outbound_localization }}
                  </p>
                </div>
              </div>

              <div class="space-y-3">
                <Label>Outbound Codecs</Label>
                <div class="flex flex-wrap gap-2">
                  <Button
                    v-for="(label, value) in configuration.outbound_codecs || {}"
                    :key="value"
                    type="button"
                    size="sm"
                    :variant="form.outbound_codecs.includes(value) ? 'secondary' : 'outline'"
                    @click="toggleArrayValue('outbound_codecs', value)"
                  >
                    {{ label }}
                  </Button>
                </div>
                <p v-if="form.errors.outbound_codecs" class="text-xs text-destructive">
                  {{ form.errors.outbound_codecs }}
                </p>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                  <Label for="outbound_channel_limit">Channel Limit</Label>
                  <Input
                    id="outbound_channel_limit"
                    v-model="form.outbound_channel_limit"
                    type="number"
                    min="1"
                    placeholder="Unlimited"
                  />
                </div>
                <div class="space-y-2">
                  <Label>Voice Profile</Label>
                  <Select
                    :model-value="form.outbound_voice_profile_id || undefined"
                    @update:modelValue="(value) => (form.outbound_voice_profile_id = value ?? '')"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Telnyx default" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="profile in props.voiceProfiles"
                        :key="profile.id"
                        :value="String(profile.id)"
                      >
                        {{ profile.name }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>

              <div class="grid gap-4 md:grid-cols-2">
                <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                  <div>
                    <p class="text-sm font-medium text-foreground">Call Parking</p>
                    <p class="text-xs text-muted-foreground">Allow callers to be parked and retrieved later.</p>
                  </div>
                  <Switch v-model:checked="form.outbound_call_parking" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                  <div>
                    <p class="text-sm font-medium text-foreground">Instant Ringback</p>
                    <p class="text-xs text-muted-foreground">Begin ringback tone as soon as the call is placed.</p>
                  </div>
                  <Switch v-model:checked="form.outbound_instant_ringback" />
                </div>
                <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                  <div>
                    <p class="text-sm font-medium text-foreground">Outbound Ringback Tone</p>
                    <p class="text-xs text-muted-foreground">Play a custom tone while the remote party alerts.</p>
                  </div>
                  <Switch v-model:checked="form.outbound_ringback_tone" />
                </div>
                <div class="space-y-2">
                  <Label>T.38 Re-Invite Source</Label>
                  <Select
                    :model-value="form.outbound_t38_reinvite_source || undefined"
                    @update:modelValue="(value) => (form.outbound_t38_reinvite_source = value ?? '')"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Auto negotiate" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem
                        v-for="(label, value) in configuration.outbound_t38_reinvite_source || {}"
                        :key="value"
                        :value="value"
                      >
                        {{ label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Monitoring &amp; RTCP</CardTitle>
              <CardDescription>Gather quality telemetry and tune reporting cadence.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="space-y-2">
                <Label>RTCP Port Policy</Label>
                <Select
                  :model-value="form.rtcp_port || undefined"
                  @update:modelValue="(value) => (form.rtcp_port = value ?? '')"
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Follow media port" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem
                      v-for="(label, value) in configuration.rtcp_port || {}"
                      :key="value"
                      :value="value"
                    >
                      {{ label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <p v-if="form.errors.rtcp_port" class="text-xs text-destructive">
                  {{ form.errors.rtcp_port }}
                </p>
              </div>
              <div class="space-y-2">
                <Label for="rtcp_report_frequency">Report Frequency (seconds)</Label>
                <Input
                  id="rtcp_report_frequency"
                  v-model="form.rtcp_report_frequency"
                  type="number"
                  min="1"
                  max="60"
                  placeholder="10"
                />
                <p v-if="form.errors.rtcp_report_frequency" class="text-xs text-destructive">
                  {{ form.errors.rtcp_report_frequency }}
                </p>
              </div>
              <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                <div>
                  <p class="text-sm font-medium text-foreground">RTCP Capture</p>
                  <p class="text-xs text-muted-foreground">Collect QoS metrics for real-time voice analysis.</p>
                </div>
                <Switch v-model:checked="form.rtcp_capture_enabled" />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Device Push Credentials</CardTitle>
              <CardDescription>Associate Telnyx softphone credentials for mobile notification flows.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="space-y-2">
                <Label for="ios_push_credential_id">iOS Credential ID</Label>
                <Input
                  id="ios_push_credential_id"
                  v-model="form.ios_push_credential_id"
                  placeholder="Optional APNs credential UUID"
                />
                <p v-if="form.errors.ios_push_credential_id" class="text-xs text-destructive">
                  {{ form.errors.ios_push_credential_id }}
                </p>
              </div>
              <div class="space-y-2">
                <Label for="android_push_credential_id">Android Credential ID</Label>
                <Input
                  id="android_push_credential_id"
                  v-model="form.android_push_credential_id"
                  placeholder="Optional FCM credential UUID"
                />
                <p v-if="form.errors.android_push_credential_id" class="text-xs text-destructive">
                  {{ form.errors.android_push_credential_id }}
                </p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Phone Number Assignment</CardTitle>
              <CardDescription>Select DID inventory that should land on this trunk.</CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="props.phoneNumbers.length" class="space-y-4">
                <div
                  v-for="(phoneNumber, index) in form.phone_numbers"
                  :key="index"
                  class="flex flex-col gap-3 rounded-lg border border-border/60 bg-muted/30 p-4 md:flex-row md:items-center md:gap-4"
                >
                  <div class="flex-1 space-y-2">
                    <Label :for="`phone_number_${index}`">Phone Number</Label>
                    <Select
                      :model-value="phoneNumber.phone_number_id || undefined"
                      @update:modelValue="(value) => updatePhoneNumberId(index, value)"
                    >
                      <SelectTrigger :id="`phone_number_${index}`">
                        <SelectValue placeholder="Select available number" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem
                          v-for="number in props.phoneNumbers"
                          :key="number.id"
                          :value="String(number.id)"
                          :disabled="isNumberDisabled(number.id, index)"
                        >
                          {{ number.phone_number }}
                          <span class="text-xs text-muted-foreground">
                            · {{ number.number_type || 'local' }}
                          </span>
                        </SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <div class="w-full space-y-2 md:w-40">
                    <Label :for="`assignment_${index}`">Role</Label>
                    <Select
                      :model-value="phoneNumber.assignment_type"
                      @update:modelValue="(value) => (phoneNumber.assignment_type = value)"
                    >
                      <SelectTrigger :id="`assignment_${index}`">
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="primary">Primary</SelectItem>
                        <SelectItem value="secondary">Secondary</SelectItem>
                        <SelectItem value="backup">Backup</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="self-start text-destructive hover:text-destructive focus-visible:ring-destructive"
                    @click="removePhoneNumber(index)"
                  >
                    <Trash2 class="h-4 w-4" />
                    <span class="sr-only">Remove</span>
                  </Button>
                </div>
                <Button type="button" variant="outline" size="sm" class="gap-2" @click="addPhoneNumber">
                  <Plus class="h-4 w-4" />
                  Add phone number
                </Button>
              </div>
              <div v-else class="flex items-center gap-3 rounded-lg border border-dashed border-border px-4 py-6 text-sm text-muted-foreground">
                <Info class="h-4 w-4 text-muted-foreground" />
                You do not currently have unassigned phone numbers. Acquire inventory to route calls through this trunk.
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <Separator />

      <div class="flex flex-wrap items-center justify-end gap-3">
        <Link
          :href="route('sip-trunks.index')"
          class="text-sm text-muted-foreground transition hover:text-foreground"
        >
          Cancel
        </Link>
        <Button type="submit" :disabled="form.processing">
          {{ form.processing ? 'Creating…' : 'Create SIP Trunk' }}
        </Button>
      </div>
    </form>
  </DashboardLayout>
</template>

