<script setup>
import { ref, watch, computed } from 'vue'
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
import Badge from '@/Components/ui/Badge.vue'

const props = defineProps({
  messagingProfile: {
    type: Object,
    required: true
  },
  countries: {
    type: Array,
    default: () => []
  }
})

const form = useForm({
  name: props.messagingProfile.name || '',
  enabled: Boolean(props.messagingProfile.enabled),
  whitelisted_destinations: [...(props.messagingProfile.whitelisted_destinations || [])],
  webhook_url: props.messagingProfile.webhook_url || '',
  webhook_failover_url: props.messagingProfile.webhook_failover_url || '',
  webhook_api_version: props.messagingProfile.webhook_api_version || '2',
  number_pool_settings: props.messagingProfile.number_pool_settings
    ? { ...props.messagingProfile.number_pool_settings }
    : null,
  url_shortener_settings: props.messagingProfile.url_shortener_settings
    ? { ...props.messagingProfile.url_shortener_settings }
    : null,
  alpha_sender: props.messagingProfile.alpha_sender || '',
  daily_spend_limit: props.messagingProfile.daily_spend_limit || '',
  daily_spend_limit_enabled: Boolean(props.messagingProfile.daily_spend_limit_enabled),
  mms_fall_back_to_sms: Boolean(props.messagingProfile.mms_fall_back_to_sms),
  mms_transcoding: Boolean(props.messagingProfile.mms_transcoding)
})

const allowAllCountries = ref(form.whitelisted_destinations.includes('*'))
const enableNumberPool = ref(Boolean(form.number_pool_settings))
const enableUrlShortener = ref(Boolean(form.url_shortener_settings))

const nonWildcardCountries = computed(() =>
  props.countries.filter((country) => country.code !== '*')
)

watch(allowAllCountries, (value) => {
  form.whitelisted_destinations = value ? ['*'] : []
})

watch(enableNumberPool, (value) => {
  form.number_pool_settings = value
    ? form.number_pool_settings || {
        toll_free_weight: 1,
        long_code_weight: 1,
        skip_unhealthy: true,
        sticky_sender: false,
        geomatch: false
      }
    : null
})

watch(enableUrlShortener, (value) => {
  form.url_shortener_settings = value
    ? form.url_shortener_settings || {
        domain: '',
        prefix: '',
        replace_blacklist_only: false,
        send_webhooks: false
      }
    : null
})

const toggleDestination = (code) => {
  if (form.whitelisted_destinations.includes(code)) {
    form.whitelisted_destinations = form.whitelisted_destinations.filter((item) => item !== code)
  } else {
    form.whitelisted_destinations = [...form.whitelisted_destinations, code]
  }
}

const statusVariant = computed(() => (form.enabled ? 'secondary' : 'destructive'))

const formatDateTime = (date) => {
  if (!date) return '—'
  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return '—'

  return parsed.toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const submit = () => {
  if (!allowAllCountries.value && form.whitelisted_destinations.length === 0) {
    alert('Select at least one destination or allow all countries.')
    return
  }

  form.put(route('messaging-profiles.update', props.messagingProfile.id))
}
</script>

<template>
  <DashboardLayout>
    <Head :title="`Edit Messaging Profile · ${messagingProfile.name}`" />

    <template #header>
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Edit Messaging Profile</h1>
          <p class="text-sm text-muted-foreground">
            Update destinations, webhook routing, and delivery controls.
          </p>
        </div>
        <Link
          :href="route('messaging-profiles.show', messagingProfile.id)"
          class="inline-flex items-center gap-2 text-sm text-muted-foreground transition hover:text-foreground"
        >
          ← Back to Profile
        </Link>
      </div>
    </template>

    <form class="space-y-6 pb-12" @submit.prevent="submit">
      <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <Card class="lg:col-span-1">
          <CardHeader>
            <CardTitle>Profile Summary</CardTitle>
            <CardDescription>Current state and identifiers.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <div class="space-y-2">
              <Label>Profile Name</Label>
              <Input v-model="form.name" required />
              <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
            </div>

            <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
              <div>
                <p class="text-sm font-medium text-foreground">Status</p>
                <p class="text-xs text-muted-foreground">Enable to allow outbound messaging.</p>
              </div>
              <Switch v-model:checked="form.enabled" />
            </div>

            <div class="rounded-lg border border-border/60 bg-muted/30 p-4 text-xs text-muted-foreground">
              <div class="flex items-center justify-between">
                <span class="font-medium text-foreground">Telnyx profile ID</span>
                <Badge variant="outline">{{ messagingProfile.telnyx_profile_id || 'N/A' }}</Badge>
              </div>
              <div class="mt-2 flex items-center justify-between">
                <span>Created</span>
                <span>{{ formatDateTime(messagingProfile.created_at) }}</span>
              </div>
              <div class="mt-1 flex items-center justify-between">
                <span>Updated</span>
                <span>{{ formatDateTime(messagingProfile.updated_at) }}</span>
              </div>
            </div>

            <div class="space-y-2">
              <Label>Alpha Sender</Label>
              <Input v-model="form.alpha_sender" maxlength="11" placeholder="MYSENDER" />
              <p class="text-xs text-muted-foreground">
                Optional 1-11 alphanumeric characters.
              </p>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <Label>Daily spend limit (USD)</Label>
                <div class="flex items-center gap-2">
                  <span class="text-xs text-muted-foreground">Enable</span>
                  <Switch v-model:checked="form.daily_spend_limit_enabled" />
                </div>
              </div>
              <Input
                v-model="form.daily_spend_limit"
                :disabled="!form.daily_spend_limit_enabled"
                inputmode="decimal"
              />
              <p v-if="form.errors.daily_spend_limit" class="text-xs text-destructive">
                {{ form.errors.daily_spend_limit }}
              </p>
            </div>

            <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
              <div>
                <p class="text-sm font-medium text-foreground">MMS fallback to SMS</p>
                <p class="text-xs text-muted-foreground">
                  Retry as SMS if MMS delivery fails.
                </p>
              </div>
              <Switch v-model:checked="form.mms_fall_back_to_sms" />
            </div>

            <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
              <div>
                <p class="text-sm font-medium text-foreground">MMS transcoding</p>
                <p class="text-xs text-muted-foreground">
                  Resize or reformat MMS attachments automatically.
                </p>
              </div>
              <Switch v-model:checked="form.mms_transcoding" />
            </div>
          </CardContent>
        </Card>

        <Card class="lg:col-span-1">
          <CardHeader>
            <CardTitle>Destination Controls</CardTitle>
            <CardDescription>Adjust regional coverage for outbound messaging.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
              <div>
                <p class="text-sm font-medium text-foreground">Allow all countries</p>
                <p class="text-xs text-muted-foreground">
                  Overrides individual country selections below.
                </p>
              </div>
              <Switch v-model:checked="allowAllCountries" />
            </div>

            <div
              v-if="!allowAllCountries"
              class="grid max-h-64 grid-cols-2 gap-2 overflow-y-auto rounded-lg border border-border/60 bg-muted/30 p-3 text-xs"
            >
              <button
                v-for="country in nonWildcardCountries"
                :key="country.code"
                type="button"
                class="rounded-md border px-2 py-1 text-left transition"
                :class="form.whitelisted_destinations.includes(country.code)
                  ? 'border-primary bg-primary/10 text-primary'
                  : 'border-border bg-background text-foreground hover:bg-muted'"
                @click="toggleDestination(country.code)"
              >
                {{ country.name }} ({{ country.code }})
              </button>
            </div>

            <p v-if="form.errors.whitelisted_destinations" class="text-xs text-destructive">
              {{ form.errors.whitelisted_destinations }}
            </p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Webhook Configuration</CardTitle>
          <CardDescription>Primary and failover URLs for delivery callbacks.</CardDescription>
        </CardHeader>
        <CardContent class="grid gap-6 md:grid-cols-2">
          <div class="space-y-2">
            <Label>Webhook URL</Label>
            <Input
              v-model="form.webhook_url"
              placeholder="https://example.com/webhooks/messaging"
              type="url"
            />
            <p v-if="form.errors.webhook_url" class="text-xs text-destructive">
              {{ form.errors.webhook_url }}
            </p>
          </div>
          <div class="space-y-2">
            <Label>Failover URL</Label>
            <Input
              v-model="form.webhook_failover_url"
              placeholder="https://backup.example.com/webhooks/messaging"
              type="url"
            />
            <p v-if="form.errors.webhook_failover_url" class="text-xs text-destructive">
              {{ form.errors.webhook_failover_url }}
            </p>
          </div>
          <div class="space-y-2">
            <Label>API version</Label>
            <Select v-model="form.webhook_api_version">
              <SelectTrigger>
                <SelectValue placeholder="Select version" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="1">Version 1</SelectItem>
                <SelectItem value="2">Version 2</SelectItem>
                <SelectItem value="2010-04-01">Version 2010-04-01</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      <div class="grid gap-6 md:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Number Pool</CardTitle>
            <CardDescription>Distribute messaging volume across assigned numbers.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
              <div>
                <p class="text-sm font-medium text-foreground">Enable number pool</p>
                <p class="text-xs text-muted-foreground">
                  Toggle weighted distribution and sticky sender features.
                </p>
              </div>
              <Switch v-model:checked="enableNumberPool" />
            </div>

            <div v-if="enableNumberPool" class="space-y-4">
              <div class="grid grid-cols-2 gap-3">
                <div class="space-y-2">
                  <Label>Toll-free weight</Label>
                  <Input
                    v-model.number="form.number_pool_settings.toll_free_weight"
                    type="number"
                    min="0"
                    step="0.1"
                  />
                </div>
                <div class="space-y-2">
                  <Label>Long code weight</Label>
                  <Input
                    v-model.number="form.number_pool_settings.long_code_weight"
                    type="number"
                    min="0"
                    step="0.1"
                  />
                </div>
              </div>
              <div class="space-y-2">
                <div class="flex items-center justify-between rounded-md border border-border/60 bg-muted/40 p-3">
                  <span class="text-xs font-medium text-foreground">Skip unhealthy numbers</span>
                  <Switch v-model:checked="form.number_pool_settings.skip_unhealthy" />
                </div>
                <div class="flex items-center justify-between rounded-md border border-border/60 bg-muted/40 p-3">
                  <span class="text-xs font-medium text-foreground">Sticky sender</span>
                  <Switch v-model:checked="form.number_pool_settings.sticky_sender" />
                </div>
                <div class="flex items-center justify-between rounded-md border border-border/60 bg-muted/40 p-3">
                  <span class="text-xs font-medium text-foreground">Geomatch</span>
                  <Switch v-model:checked="form.number_pool_settings.geomatch" />
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>URL Shortener</CardTitle>
            <CardDescription>Rewrite tracked links with Telnyx branded URLs.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
              <div>
                <p class="text-sm font-medium text-foreground">Enable URL shortener</p>
                <p class="text-xs text-muted-foreground">
                  Replace public shorteners automatically and track clicks.
                </p>
              </div>
              <Switch v-model:checked="enableUrlShortener" />
            </div>

            <div v-if="enableUrlShortener" class="space-y-4">
              <div class="space-y-2">
                <Label>Domain</Label>
                <Input v-model="form.url_shortener_settings.domain" placeholder="short.telnyx.com" />
              </div>
              <div class="space-y-2">
                <Label>Brand prefix (optional)</Label>
                <Input v-model="form.url_shortener_settings.prefix" placeholder="BRAND" />
              </div>
              <div class="space-y-2">
                <div class="flex items-center justify-between rounded-md border border-border/60 bg-muted/40 p-3">
                  <span class="text-xs font-medium text-foreground">Replace only blacklist</span>
                  <Switch
                    v-model:checked="form.url_shortener_settings.replace_blacklist_only"
                  />
                </div>
                <div class="flex items-center justify-between rounded-md border border-border/60 bg-muted/40 p-3">
                  <span class="text-xs font-medium text-foreground">Send webhooks</span>
                  <Switch v-model:checked="form.url_shortener_settings.send_webhooks" />
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <Separator />

      <div class="flex justify-end gap-2">
        <Button type="button" variant="ghost" @click="window.history.back()">Cancel</Button>
        <Button type="submit" :disabled="form.processing">
          {{ form.processing ? 'Saving…' : 'Save changes' }}
        </Button>
      </div>
    </form>
  </DashboardLayout>
</template>

