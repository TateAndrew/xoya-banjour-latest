<script setup>
import { ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardDescription from '@/Components/ui/CardDescription.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import Separator from '@/Components/ui/Separator.vue'
import {
  Phone,
  MapPin,
  ShieldCheck,
  DollarSign,
  Calendar,
  Layers,
  AlertTriangle,
  Loader2,
  Trash2,
  Mic,
  ArrowLeft,
  ExternalLink,
  Globe
} from 'lucide-vue-next'
import axios from 'axios'

const props = defineProps({
  phoneNumber: {
    type: Object,
    required: true
  },
  telnyxData: {
    type: Object,
    default: () => ({ success: false, data: null })
  }
})

const releasing = ref(false)

const capabilityBadges = computed(() =>
  normalizeStringArray(props.phoneNumber.capabilities || [])
)

const telnyxUrl = computed(() => {
  if (!props.phoneNumber?.telnyx_id) return null
  return `https://portal.telnyx.com/#/numbers/${props.phoneNumber.telnyx_id}`
})

const releaseNumber = async () => {
  if (
    !confirm(
      'Release this phone number back to inventory? This action cannot be undone.'
    )
  ) {
    return
  }

  releasing.value = true
  try {
    const response = await axios.delete(route('phone-numbers.destroy', props.phoneNumber.id))

    if (response.data.success) {
      alert('Phone number released successfully.')
      router.visit(route('phone-numbers.index'))
    } else {
      alert('Error releasing number: ' + (response.data.error || 'Unknown error'))
    }
  } catch (error) {
    console.error('Release error:', error)
    const message =
      error.response?.data?.error || 'Error releasing number. Please try again.'
    alert(message)
  } finally {
    releasing.value = false
  }
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

const formatCurrency = (value) => {
  if (value == null || Number.isNaN(Number(value))) {
    return '—'
  }

  return `$${Number(value).toFixed(2)}`
}

const formatDate = (date) => {
  if (!date) return '—'
  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return '—'

  return parsed.toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <Head :title="`Phone Number · ${formatPhoneNumber(phoneNumber.phone_number)}`" />

  <DashboardLayout>
    <template #header>
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div class="space-y-1">
          <h1 class="text-3xl font-bold tracking-tight">Phone Number Details</h1>
          <p class="text-sm text-muted-foreground">
            Inspect metadata, capabilities, billing, and external references for this number.
          </p>
        </div>
        <Link
          :href="route('phone-numbers.index')"
          class="inline-flex items-center gap-2 text-sm text-muted-foreground transition hover:text-foreground"
        >
          <ArrowLeft class="h-4 w-4" />
          Back to Numbers
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
              <CardDescription>
                {{ phoneNumber.friendly_name || 'Telnyx Provisioned' }}
              </CardDescription>
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
              <p class="text-sm font-medium text-foreground">Number type</p>
              <p class="text-sm text-muted-foreground">
                {{ (phoneNumber.number_type || 'local').toUpperCase() }}
              </p>
            </div>
          </div>
          <div class="flex items-start gap-3 rounded-lg border border-border/60 bg-muted/40 p-4">
            <div class="mt-1 rounded-md bg-primary/10 p-2 text-primary">
              <MapPin class="h-4 w-4" />
            </div>
            <div>
              <p class="text-sm font-medium text-foreground">Region</p>
              <p class="text-sm text-muted-foreground">
                {{ phoneNumber.city || '—' }}, {{ phoneNumber.state || phoneNumber.country || '—' }}
              </p>
            </div>
          </div>
          <div class="flex items-start gap-3 rounded-lg border border-border/60 bg-muted/40 p-4">
            <div class="mt-1 rounded-md bg-primary/10 p-2 text-primary">
              <Globe class="h-4 w-4" />
            </div>
            <div>
              <p class="text-sm font-medium text-foreground">Country code</p>
              <p class="text-sm text-muted-foreground">
                {{ phoneNumber.country_code || '—' }}
              </p>
            </div>
          </div>
          <div class="flex items-start gap-3 rounded-lg border border-border/60 bg-muted/40 p-4">
            <div class="mt-1 rounded-md bg-primary/10 p-2 text-primary">
              <ShieldCheck class="h-4 w-4" />
            </div>
            <div>
              <p class="text-sm font-medium text-foreground">Capabilities</p>
              <div class="mt-1 flex flex-wrap gap-1.5">
                <Badge
                  v-for="capability in capabilityBadges"
                  :key="capability"
                  variant="outline"
                  class="text-[11px] font-medium uppercase tracking-wide"
                >
                  {{ capability }}
                </Badge>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Number profile</CardTitle>
              <CardDescription>Reference metadata for routing and caller ID policies.</CardDescription>
            </CardHeader>
            <CardContent class="grid gap-6 md:grid-cols-2">
              <dl class="space-y-3 text-sm">
                <div>
                  <dt class="text-xs font-medium uppercase text-muted-foreground">Area code</dt>
                  <dd class="text-foreground">
                    {{ phoneNumber.area_code || '—' }}
                  </dd>
                </div>
                <div>
                  <dt class="text-xs font-medium uppercase text-muted-foreground">Carrier</dt>
                  <dd class="text-foreground">
                    {{ phoneNumber.carrier || '—' }}
                  </dd>
                </div>
                <div>
                  <dt class="text-xs font-medium uppercase text-muted-foreground">Telnyx ID</dt>
                  <dd class="flex items-center gap-2 text-foreground">
                    <span>{{ phoneNumber.telnyx_id || '—' }}</span>
                    <a
                      v-if="telnyxUrl"
                      :href="telnyxUrl"
                      target="_blank"
                      rel="noopener noreferrer"
                      class="inline-flex items-center gap-1 text-xs text-primary transition hover:text-primary/80"
                    >
                      Open in Telnyx
                      <ExternalLink class="h-3 w-3" />
                    </a>
                  </dd>
                </div>
                <div>
                  <dt class="text-xs font-medium uppercase text-muted-foreground">Friendly name</dt>
                  <dd class="text-foreground">
                    {{ phoneNumber.friendly_name || 'Not assigned' }}
                  </dd>
                </div>
              </dl>

              <dl class="space-y-3 text-sm">
                <div>
                  <dt class="text-xs font-medium uppercase text-muted-foreground">Purchased on</dt>
                  <dd class="text-foreground">
                    {{ formatDate(phoneNumber.purchased_at) }}
                  </dd>
                </div>
                <div v-if="phoneNumber.expires_at">
                  <dt class="text-xs font-medium uppercase text-muted-foreground">Expires on</dt>
                  <dd class="text-foreground">
                    {{ formatDate(phoneNumber.expires_at) }}
                  </dd>
                </div>
                <div>
                  <dt class="text-xs font-medium uppercase text-muted-foreground">Assigned to</dt>
                  <dd class="text-foreground">
                    {{ phoneNumber.assigned_to || 'Not assigned' }}
                  </dd>
                </div>
                <div>
                  <dt class="text-xs font-medium uppercase text-muted-foreground">Messaging profile</dt>
                  <dd class="text-foreground">
                    {{ phoneNumber.messaging_profile?.name || 'None linked' }}
                  </dd>
                </div>
              </dl>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Billing</CardTitle>
              <CardDescription>Recurring and one-time charges for this number.</CardDescription>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2">
              <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-foreground">Monthly</span>
                  <DollarSign class="h-4 w-4 text-muted-foreground" />
                </div>
                <p class="mt-2 text-xl font-semibold text-foreground">
                  {{ formatCurrency(phoneNumber.monthly_rate) }}
                </p>
                <p class="text-xs text-muted-foreground">
                  Recurring usage and number rental charges.
                </p>
              </div>
              <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
                <div class="flex items-center justify-between">
                  <span class="text-sm font-medium text-foreground">Setup</span>
                  <Layers class="h-4 w-4 text-muted-foreground" />
                </div>
                <p class="mt-2 text-xl font-semibold text-foreground">
                  {{ formatCurrency(phoneNumber.setup_fee) }}
                </p>
                <p class="text-xs text-muted-foreground">
                  One-time provisioning and activation costs.
                </p>
              </div>
            </CardContent>
          </Card>
        </div>

        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Actions</CardTitle>
              <CardDescription>Manage recording defaults and lifecycle.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
              <Button
                variant="outline"
                class="w-full justify-start gap-2"
                :href="route('phone-numbers.edit-recording-settings', phoneNumber.id)"
                tag="a"
              >
                <Mic class="h-4 w-4" />
                Recording settings
              </Button>
              <Button
                variant="outline"
                class="w-full justify-start gap-2"
                :href="route('phone-numbers.manage')"
                tag="a"
              >
                <ShieldCheck class="h-4 w-4" />
                Manage assignments
              </Button>
              <Button
                variant="destructive"
                class="w-full justify-start gap-2"
                :disabled="releasing"
                @click="releaseNumber"
              >
                <Loader2 v-if="releasing" class="h-4 w-4 animate-spin" />
                <Trash2 v-else class="h-4 w-4" />
                {{ releasing ? 'Releasing…' : 'Release number' }}
              </Button>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Lifecycle</CardTitle>
              <CardDescription>Key milestone timestamps.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
              <div class="flex items-start gap-3 rounded-lg border border-border/60 bg-muted/30 p-4">
                <div class="mt-1 rounded-md bg-primary/10 p-2 text-primary">
                  <Calendar class="h-4 w-4" />
                </div>
                <div>
                  <p class="text-sm font-medium text-foreground">Purchased</p>
                  <p class="text-sm text-muted-foreground">
                    {{ formatDate(phoneNumber.purchased_at) }}
                  </p>
                </div>
              </div>
              <div
                v-if="phoneNumber.expires_at"
                class="flex items-start gap-3 rounded-lg border border-border/60 bg-muted/30 p-4"
              >
                <div class="mt-1 rounded-md bg-primary/10 p-2 text-primary">
                  <Calendar class="h-4 w-4" />
                </div>
                <div>
                  <p class="text-sm font-medium text-foreground">Expires</p>
                  <p class="text-sm text-muted-foreground">
                    {{ formatDate(phoneNumber.expires_at) }}
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card v-if="!telnyxData.success">
            <CardHeader>
              <CardTitle>Telnyx reference</CardTitle>
              <CardDescription>
                Sync for the latest provisioning details from the carrier.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-3 text-sm text-muted-foreground">
              <AlertTriangle class="h-4 w-4 text-amber-500" />
              <p>
                Telnyx metadata is not currently loaded. Trigger a sync from the number management page
                when needed.
              </p>
            </CardContent>
          </Card>

          <Card v-else>
            <CardHeader>
              <CardTitle>Telnyx metadata</CardTitle>
              <CardDescription>
                Fields fetched directly from the Telnyx API for troubleshooting.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <pre class="max-h-64 overflow-auto rounded-md bg-muted/40 p-3 text-xs text-muted-foreground">
{{ JSON.stringify(telnyxData.data, null, 2) }}
              </pre>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

