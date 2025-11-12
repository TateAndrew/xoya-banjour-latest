<script setup>
import { ref, watch } from 'vue'
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
import { ShieldCheck, Gauge, CircleDollarSign, Info, ArrowLeft } from 'lucide-vue-next'

const form = useForm({
  name: '',
  enabled: true,
  concurrent_call_limit: '',
  max_destination_rate: '',
  daily_spend_limit: '',
  daily_spend_limit_enabled: 'disabled',
  call_recording_type: 'all',
  call_recording_channels: null,
  call_recording_format: 'mp3',
  tags: '',
  billing_group_id: ''
})

const spendLimitEnabled = ref(false)

watch(
  () => form.daily_spend_limit_enabled,
  (value) => {
    spendLimitEnabled.value = value === 'enabled'
  },
  { immediate: true }
)

watch(spendLimitEnabled, (value) => {
  form.daily_spend_limit_enabled = value ? 'enabled' : 'disabled'
  if (!value) {
    form.daily_spend_limit = ''
  }
})

const clearRecordingChannels = () => {
  form.call_recording_channels = null
}

const clearRecordingFormat = () => {
  form.call_recording_format = null
}

const submit = () => {
  form
    .transform((data) => ({
      ...data,
      concurrent_call_limit: data.concurrent_call_limit
        ? Number(data.concurrent_call_limit)
        : null,
      max_destination_rate: data.max_destination_rate
        ? Number(data.max_destination_rate)
        : null,
      daily_spend_limit: data.daily_spend_limit ? Number(data.daily_spend_limit) : null,
      call_recording_type: data.call_recording_type || null,
      call_recording_channels: data.call_recording_channels || null,
      call_recording_format: data.call_recording_format || null,
      tags: data.tags || null,
      billing_group_id: data.billing_group_id || null
    }))
    .post(route('outbound-voice-profiles.store'), {
      onFinish: () => {
        form.transform((data) => data)
      }
    })
}
</script>

<template>
  <DashboardLayout>
    <Head title="Create Outbound Voice Profile" />

    <template #header>
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Create Outbound Voice Profile</h1>
          <p class="text-sm text-muted-foreground">
            Define default routing, compliance, and recording behaviour for outbound calls.
          </p>
        </div>
        <Link
          :href="route('outbound-voice-profiles.index')"
          class="inline-flex items-center gap-2 text-sm text-muted-foreground transition hover:text-foreground"
        >
          <ArrowLeft class="h-4 w-4" />
          Back to profiles
        </Link>
      </div>
    </template>

    <form class="space-y-6 pb-12" @submit.prevent="submit">
      <div
        v-if="form.errors && Object.keys(form.errors).length"
        class="rounded-lg border border-destructive/40 bg-destructive/10 px-4 py-3 text-sm text-destructive"
      >
        <p class="font-medium">Please fix the following errors:</p>
        <ul class="mt-2 list-disc pl-5">
          <li v-for="(error, key) in form.errors" :key="key">
            {{ error }}
          </li>
        </ul>
      </div>

      <div class="grid gap-6 xl:grid-cols-[2fr_1fr]">
        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Profile Details</CardTitle>
              <CardDescription>Core identity, availability, and classification.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <div class="space-y-2">
                <Label for="name">Profile Name</Label>
                <Input id="name" v-model="form.name" placeholder="Global Voice Routing" required />
                <p v-if="form.errors.name" class="text-xs text-destructive">
                  {{ form.errors.name }}
                </p>
              </div>

              <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
                <div>
                  <p class="text-sm font-medium text-foreground">Status</p>
                  <p class="text-xs text-muted-foreground">
                    Toggle to temporarily suspend outbound traffic under this profile.
                  </p>
                </div>
                <Switch v-model:checked="form.enabled" />
              </div>

              <div class="space-y-2">
                <Label for="tags">Tags (optional)</Label>
                <Input
                  id="tags"
                  v-model="form.tags"
                  placeholder="marketing, emea"
                />
                <p class="text-xs text-muted-foreground">
                  Comma-separated labels for reporting and automation.
                </p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Call &amp; Spend Controls</CardTitle>
              <CardDescription>Safeguard concurrency and cost exposure.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-2">
                  <Label for="concurrent_call_limit">Concurrent Call Limit</Label>
                  <Input
                    id="concurrent_call_limit"
                    v-model="form.concurrent_call_limit"
                    type="number"
                    min="1"
                    placeholder="Unlimited"
                  />
                  <p class="text-xs text-muted-foreground">
                    Leave blank for unlimited simultaneous calls.
                  </p>
                  <p v-if="form.errors.concurrent_call_limit" class="text-xs text-destructive">
                    {{ form.errors.concurrent_call_limit }}
                  </p>
                </div>
                <div class="space-y-2">
                  <Label for="max_destination_rate">Max Destination Rate (¢/min)</Label>
                  <Input
                    id="max_destination_rate"
                    v-model="form.max_destination_rate"
                    type="number"
                    min="0"
                    placeholder="e.g. 35"
                  />
                  <p class="text-xs text-muted-foreground">
                    Reject calls exceeding this per-minute rate.
                  </p>
                </div>
              </div>

              <Separator />

              <div class="space-y-4 rounded-lg border border-border/60 bg-muted/30 p-4">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <p class="text-sm font-medium text-foreground">Daily Spend Limit</p>
                    <p class="text-xs text-muted-foreground">
                      Cap outbound charges for this profile each calendar day.
                    </p>
                  </div>
                  <Switch v-model:checked="spendLimitEnabled" />
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                  <div class="space-y-2">
                    <Label for="daily_spend_limit">Limit (cents)</Label>
                    <Input
                      id="daily_spend_limit"
                      v-model="form.daily_spend_limit"
                      type="number"
                      min="0"
                      :disabled="!spendLimitEnabled"
                      placeholder="5000"
                    />
                    <p v-if="form.errors.daily_spend_limit" class="text-xs text-destructive">
                      {{ form.errors.daily_spend_limit }}
                    </p>
                  </div>
                  <div class="space-y-2">
                    <Label>Status</Label>
                    <div class="flex h-10 items-center rounded-md border border-border bg-background px-3 text-sm text-muted-foreground">
                      {{ spendLimitEnabled ? 'Enabled' : 'Disabled' }}
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Recording Defaults</CardTitle>
              <CardDescription>Configure recording policies and channel preferences.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
              <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-2">
                  <Label>Call Recording Type</Label>
                  <Select v-model="form.call_recording_type">
                    <SelectTrigger>
                      <SelectValue placeholder="Select recording strategy" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">Record all calls</SelectItem>
                      <SelectItem value="none">Do not record</SelectItem>
                    </SelectContent>
                  </Select>
                  <p class="text-xs text-muted-foreground">
                    Select how Telnyx should handle recordings by default.
                  </p>
                </div>

                <div class="space-y-2">
                  <Label>Recording Channels</Label>
                  <Select
                    :model-value="form.call_recording_channels ?? undefined"
                    @update:modelValue="(value) => (form.call_recording_channels = value ?? null)"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Auto (Telnyx default)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="single">Single channel</SelectItem>
                      <SelectItem value="dual">Dual channel</SelectItem>
                    </SelectContent>
                  </Select>
                  <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Pick how participants are mixed in the recording.</span>
                    <button
                      type="button"
                      class="font-medium text-primary underline-offset-4 hover:underline"
                      @click="clearRecordingChannels"
                    >
                      Clear
                    </button>
                  </div>
                </div>
              </div>

              <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-2">
                  <Label>Recording Format</Label>
                  <Select
                    :model-value="form.call_recording_format ?? undefined"
                    @update:modelValue="(value) => (form.call_recording_format = value ?? null)"
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Auto (Telnyx default)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="mp3">MP3</SelectItem>
                      <SelectItem value="wav">WAV</SelectItem>
                    </SelectContent>
                  </Select>
                  <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Choose a file container or use the Telnyx default.</span>
                    <button
                      type="button"
                      class="font-medium text-primary underline-offset-4 hover:underline"
                      @click="clearRecordingFormat"
                    >
                      Clear
                    </button>
                  </div>
                </div>
                <div class="space-y-2">
                  <Label for="billing_group_id">Billing Group ID</Label>
                  <Input
                    id="billing_group_id"
                    v-model="form.billing_group_id"
                    placeholder="Optional billing reference"
                  />
                </div>
              </div>
            </CardContent>
          </Card>
        </div>

        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Operational Context</CardTitle>
              <CardDescription>Useful guidance before you go live.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-4 text-sm text-muted-foreground">
              <div class="flex items-start gap-3">
                <ShieldCheck class="mt-1 h-4 w-4 text-primary" />
                <p>
                  Ensure emergency calling requirements are satisfied for every destination you plan
                  to reach.
                </p>
              </div>
              <div class="flex items-start gap-3">
                <Gauge class="mt-1 h-4 w-4 text-primary" />
                <p>
                  Monitor utilisation after launch and raise concurrency limits before traffic
                  peaks to avoid call blocking.
                </p>
              </div>
              <div class="flex items-start gap-3">
                <CircleDollarSign class="mt-1 h-4 w-4 text-primary" />
                <p>
                  Spend caps apply per UTC day. Set alerts within Telnyx to complement these limits.
                </p>
              </div>
              <div class="flex items-start gap-3">
                <Info class="mt-1 h-4 w-4 text-primary" />
                <p>
                  Recording policies vary by jurisdiction. Confirm local compliance before
                  activating capture.
                </p>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3">
        <Link
          :href="route('outbound-voice-profiles.index')"
          class="text-sm text-muted-foreground transition hover:text-foreground"
        >
          Cancel
        </Link>
        <Button type="submit" :disabled="form.processing">
          {{ form.processing ? 'Creating…' : 'Create Voice Profile' }}
        </Button>
      </div>
    </form>
  </DashboardLayout>
</template>
