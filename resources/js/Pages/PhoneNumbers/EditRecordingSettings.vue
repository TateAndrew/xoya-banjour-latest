<script setup>
import { ref, watch, computed } from 'vue'
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardDescription from '@/Components/ui/CardDescription.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import Separator from '@/Components/ui/Separator.vue'
import Label from '@/Components/ui/Label.vue'
import Switch from '@/Components/ui/Switch.vue'
import Select from '@/Components/ui/Select.vue'
import SelectTrigger from '@/Components/ui/SelectTrigger.vue'
import SelectContent from '@/Components/ui/SelectContent.vue'
import SelectItem from '@/Components/ui/SelectItem.vue'
import SelectValue from '@/Components/ui/SelectValue.vue'
import {
  Phone,
  MapPin,
  ShieldCheck,
  Waves,
  Music2,
  Info,
  Loader2,
  CheckCircle,
  AlertTriangle
} from 'lucide-vue-next'

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
  inbound_call_recording_channels: props.phoneNumber.inbound_call_recording_channels || 'single'
})

const locationLabel = computed(() => {
  const parts = []
  if (props.phoneNumber.city) parts.push(props.phoneNumber.city)
  if (props.phoneNumber.state) parts.push(props.phoneNumber.state)
  if (!parts.length && props.phoneNumber.country) {
    parts.push(props.phoneNumber.country)
  }
  return parts.join(', ') || 'Location unavailable'
})

const capabilityBadges = computed(() =>
  normalizeStringArray(props.phoneNumber.capabilities || [])
)

const recordingStateLabel = computed(() =>
  form.inbound_call_recording_enabled ? 'Enabled' : 'Disabled'
)

watch(
  () => page.props.flash,
  (flash) => {
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
  },
  { deep: true, immediate: true }
)

const submitForm = () => {
  successMessage.value = ''
  errorMessage.value = ''

  form.put(route('phone-numbers.update-recording-settings', props.phoneNumber.id), {
    preserveScroll: true,
    onSuccess: () => {
      successMessage.value = 'Recording settings updated successfully.'
      setTimeout(() => {
        successMessage.value = ''
      }, 5000)
    },
    onError: () => {
      errorMessage.value =
        form.errors.error ||
        'Failed to update recording settings. Please check the form and try again.'
      setTimeout(() => {
        errorMessage.value = ''
      }, 5000)
    }
  })
}

const statusVariant = (status) => {
  switch ((status || '').toLowerCase()) {
    case 'purchased':
    case 'assigned':
    case 'active':
      return 'secondary'
    case 'pending':
      return 'outline'
    case 'failed':
      return 'destructive'
    default:
      return 'outline'
  }
}

function normalizeStringArray(items) {
  if (!items) return []

  return items
    .map((item) => {
      if (!item) return null
      if (typeof item === 'string') return item
      if (typeof item === 'object') {
        return item.name || item.code || item.capability || null
      }
      return item.toString()
    })
    .filter(Boolean)
    .map((value) => value.toString().toUpperCase())
}

const formatPhoneNumber = (number) => {
  if (!number) return 'Unknown'
  const digits = number.replace(/\D/g, '')

  if (digits.length === 11) {
    return digits.replace(/(\d{1})(\d{3})(\d{3})(\d{4})/, '+$1 ($2) $3-$4')
  }

  if (digits.length === 10) {
    return digits.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3')
  }

  return number
}
</script>

<template>
  <Head :title="`Recording Settings · ${formatPhoneNumber(phoneNumber.phone_number)}`" />

  <DashboardLayout>
    <template #header>
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Recording Settings</h1>
          <p class="text-sm text-muted-foreground">
            Configure inbound call recording defaults for this number.
          </p>
        </div>
        <Link
          :href="route('phone-numbers.manage')"
          class="inline-flex items-center gap-2 text-sm text-muted-foreground transition hover:text-foreground"
        >
          ← Back to Phone Numbers
        </Link>
      </div>
    </template>

    <div class="space-y-6 pb-12">
      <Card>
        <CardHeader>
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
              <CardTitle class="text-2xl font-semibold">
                {{ formatPhoneNumber(phoneNumber.phone_number) }}
              </CardTitle>
              <CardDescription>Inbound recording configuration</CardDescription>
            </div>
            <Badge :variant="statusVariant(phoneNumber.status)" class="uppercase tracking-wide">
              {{ phoneNumber.status || 'Unknown' }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent class="grid gap-4 sm:grid-cols-2">
          <div class="flex items-start gap-3 rounded-lg border border-border/60 bg-muted/40 p-4">
            <div class="mt-1 rounded-md bg-primary/10 p-2 text-primary">
              <Phone class="h-4 w-4" />
            </div>
            <div>
              <p class="text-sm font-medium text-foreground">Recording Status</p>
              <p class="text-sm text-muted-foreground">{{ recordingStateLabel }}</p>
            </div>
          </div>
          <div class="flex items-start gap-3 rounded-lg border border-border/60 bg-muted/40 p-4">
            <div class="mt-1 rounded-md bg-primary/10 p-2 text-primary">
              <MapPin class="h-4 w-4" />
            </div>
            <div>
              <p class="text-sm font-medium text-foreground">Location</p>
              <p class="text-sm text-muted-foreground">{{ locationLabel }}</p>
            </div>
          </div>
          <div class="flex items-start gap-3 rounded-lg border border-border/60 bg-muted/40 p-4 sm:col-span-2">
            <div class="mt-1 rounded-md bg-primary/10 p-2 text-primary">
              <ShieldCheck class="h-4 w-4" />
            </div>
            <div class="flex w-full flex-col gap-2">
              <p class="text-sm font-medium text-foreground">Capabilities</p>
              <div class="flex flex-wrap gap-1.5">
                <Badge
                  v-for="capability in capabilityBadges"
                  :key="capability"
                  variant="outline"
                  class="text-[11px] font-medium uppercase tracking-wide"
                >
                  {{ capability }}
                </Badge>
              </div>
              <p class="text-xs text-muted-foreground">
                Capabilities determine downstream routing and compliance requirements.
              </p>
            </div>
          </div>
        </CardContent>
      </Card>

      <div
        v-if="successMessage"
        class="flex items-start gap-3 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
      >
        <CheckCircle class="h-4 w-4 text-green-600" />
        <span>{{ successMessage }}</span>
      </div>

      <div
        v-if="errorMessage"
        class="flex items-start gap-3 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
      >
        <AlertTriangle class="h-4 w-4 text-red-600" />
        <span>{{ errorMessage }}</span>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Inbound call recording</CardTitle>
          <CardDescription>Control how inbound calls are captured and stored.</CardDescription>
        </CardHeader>
        <CardContent>
          <form class="space-y-6" @submit.prevent="submitForm">
            <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
              <div class="flex items-center justify-between gap-4">
                <div>
                  <p class="text-sm font-medium text-foreground">Call recording</p>
                  <p class="text-sm text-muted-foreground">
                    Toggle to capture all inbound audio on this number.
                  </p>
                </div>
                <Switch v-model:checked="form.inbound_call_recording_enabled" />
              </div>
              <p
                v-if="form.errors.inbound_call_recording_enabled"
                class="mt-3 text-sm text-destructive"
              >
                {{ form.errors.inbound_call_recording_enabled }}
              </p>
            </div>

            <Separator />

            <div
              :class="[
                'space-y-2',
                !form.inbound_call_recording_enabled ? 'pointer-events-none opacity-60' : ''
              ]"
            >
              <Label class="text-sm font-medium text-foreground">Recording format</Label>
              <Select
                v-model="form.inbound_call_recording_format"
                :disabled="!form.inbound_call_recording_enabled"
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select format" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="wav">
                    <div class="flex items-center gap-2">
                      <Music2 class="h-4 w-4 text-muted-foreground" />
                      <span>WAV · Uncompressed</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="mp3">
                    <div class="flex items-center gap-2">
                      <Music2 class="h-4 w-4 text-muted-foreground" />
                      <span>MP3 · Compressed</span>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.inbound_call_recording_format" class="text-sm text-destructive">
                {{ form.errors.inbound_call_recording_format }}
              </p>
              <p class="text-xs text-muted-foreground">
                WAV preserves full fidelity for compliance archives. MP3 reduces file size with minor
                compression.
              </p>
            </div>

            <div
              :class="[
                'space-y-2',
                !form.inbound_call_recording_enabled ? 'pointer-events-none opacity-60' : ''
              ]"
            >
              <Label class="text-sm font-medium text-foreground">Recording channels</Label>
              <Select
                v-model="form.inbound_call_recording_channels"
                :disabled="!form.inbound_call_recording_enabled"
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select channel mode" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="single">
                    <div class="flex items-center gap-2">
                      <Waves class="h-4 w-4 text-muted-foreground" />
                      <span>Single · Mixed audio</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="dual">
                    <div class="flex items-center gap-2">
                      <Waves class="h-4 w-4 text-muted-foreground" />
                      <span>Dual · Separate parties</span>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
              <p v-if="form.errors.inbound_call_recording_channels" class="text-sm text-destructive">
                {{ form.errors.inbound_call_recording_channels }}
              </p>
              <p class="text-xs text-muted-foreground">
                Dual-channel splits caller and agent audio for downstream transcription and QA
                workflows.
              </p>
            </div>

            <div class="flex items-center justify-between pt-6">
              <Link
                :href="route('phone-numbers.manage')"
                class="text-sm text-muted-foreground transition hover:text-foreground"
              >
                Cancel
              </Link>
              <Button type="submit" class="gap-2" :disabled="form.processing">
                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                {{ form.processing ? 'Saving…' : 'Save settings' }}
              </Button>
            </div>
          </form>

          <div class="mt-8 rounded-lg border border-border/60 bg-muted/30 p-4">
            <div class="flex items-start gap-3">
              <div class="rounded-md bg-primary/10 p-2 text-primary">
                <Info class="h-4 w-4" />
              </div>
              <div class="space-y-2 text-sm text-muted-foreground">
                <p class="font-medium text-foreground">Important considerations</p>
                <ul class="list-disc pl-5 space-y-1">
                  <li>Recording laws vary by region—obtain consent where required.</li>
                  <li>Changes apply to new inbound calls immediately after saving.</li>
                  <li>Store recordings securely and adhere to your retention policy.</li>
                  <li>Dual-channel audio improves transcription and dispute resolution.</li>
                </ul>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </DashboardLayout>
</template>

