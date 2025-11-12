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

const props = defineProps({
  countries: {
    type: Array,
    default: () => []
  },
  defaultWebhookUrl: {
    type: String,
    default: ''
  }
})

const form = useForm({
  name: '',
  enabled: true,
  whitelisted_destinations: [],
  webhook_url: props.defaultWebhookUrl || '',
  webhook_failover_url: '',
  webhook_api_version: '2',
  number_pool_settings: null,
  url_shortener_settings: null,
  alpha_sender: '',
  daily_spend_limit: '',
  daily_spend_limit_enabled: false,
  mms_fall_back_to_sms: false,
  mms_transcoding: false
})

const allowAllCountries = ref(false)
const enableNumberPool = ref(false)
const enableUrlShortener = ref(false)

const nonWildcardCountries = computed(() =>
  props.countries.filter((country) => country.code !== '*')
)

watch(allowAllCountries, (value) => {
  form.whitelisted_destinations = value ? ['*'] : []
})

watch(enableNumberPool, (value) => {
  form.number_pool_settings = value
    ? {
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
    ? {
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

const submit = () => {
  if (!allowAllCountries.value && form.whitelisted_destinations.length === 0) {
    alert('Select at least one destination or allow all countries.')
    return
  }

  form.post(route('messaging-profiles.store'))
}
</script>

<template>
  <DashboardLayout>
    <Head title="Create Messaging Profile" />

    <template #header>
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Create Messaging Profile</h1>
          <p class="text-sm text-muted-foreground">
            Configure destinations, webhooks, and routing preferences for outbound messaging.
          </p>
        </div>
        <Link
          :href="route('messaging-profiles.index')"
          class="inline-flex items-center gap-2 text-sm text-muted-foreground transition hover:text-foreground"
        >
          ← Back to Profiles
        </Link>
      </div>
    </template>

    <form class="space-y-6 pb-12" @submit.prevent="submit">
      <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <Card class="lg:col-span-1">
          <CardHeader>
            <CardTitle>Profile Details</CardTitle>
            <CardDescription>Core information and feature flags.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <div class="space-y-2">
              <Label>Profile Name</Label>
              <Input v-model="form.name" placeholder="Marketing Campaign Profile" required />
              <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
            </div>

            <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
              <div>
                <p class="text-sm font-medium text-foreground">Status</p>
                <p class="text-xs text-muted-foreground">Enable to immediately allow traffic.</p>
              </div>
              <Switch v-model:checked="form.enabled" />
            </div>

            <div class="space-y-2">
              <Label>Alpha Sender (optional)</Label>
              <Input v-model="form.alpha_sender" maxlength="11" placeholder="MYSENDER" />
              <p class="text-xs text-muted-foreground">
                1-11 alphanumeric characters; shown as sender where supported.
              </p>
            </div>

            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <Label>Daily Spend Limit (USD)</Label>
                <div class="flex items-center gap-2">
                  <span class="text-xs text-muted-foreground">Enable</span>
                  <Switch v-model:checked="form.daily_spend_limit_enabled" />
                </div>
              </div>
              <Input
                v-model="form.daily_spend_limit"
                :disabled="!form.daily_spend_limit_enabled"
                placeholder="100.00"
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
            <CardTitle>Destinations</CardTitle>
            <CardDescription>Control where outbound traffic is permitted.</CardDescription>
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
          <CardTitle>Webhook configuration</CardTitle>
          <CardDescription>
            Provide delivery callback URLs for message status updates.
          </CardDescription>
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
            <CardTitle>Number pool</CardTitle>
            <CardDescription>Evenly distribute messaging volume across numbers.</CardDescription>
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
                    min="0"
                    step="0.1"
                    type="number"
                  />
                </div>
                <div class="space-y-2">
                  <Label>Long code weight</Label>
                  <Input
                    v-model.number="form.number_pool_settings.long_code_weight"
                    min="0"
                    step="0.1"
                    type="number"
                  />
                </div>
              </div>
              <div class="space-y-2">
                <div class="flex items-center justify-between rounded-md border border-border/60 bg-muted/40 p-3">
                  <div>
                    <p class="text-xs font-medium text-foreground">Skip unhealthy numbers</p>
                  </div>
                  <Switch v-model:checked="form.number_pool_settings.skip_unhealthy" />
                </div>
                <div class="flex items-center justify-between rounded-md border border-border/60 bg-muted/40 p-3">
                  <div>
                    <p class="text-xs font-medium text-foreground">Sticky sender</p>
                    <p class="text-[11px] text-muted-foreground">
                      Keep the same sender per recipient for consistency.
                    </p>
                  </div>
                  <Switch v-model:checked="form.number_pool_settings.sticky_sender" />
                </div>
                <div class="flex items-center justify-between rounded-md border border-border/60 bg-muted/40 p-3">
                  <div>
                    <p class="text-xs font-medium text-foreground">Geomatch</p>
                    <p class="text-[11px] text-muted-foreground">
                      Match recipients to local area codes (NANP only).
                    </p>
                  </div>
                  <Switch v-model:checked="form.number_pool_settings.geomatch" />
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>URL shortener</CardTitle>
            <CardDescription>Rewrite tracked links with Telnyx branded URLs.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="flex items-center justify-between rounded-lg border border-border/60 bg-muted/30 p-4">
              <div>
                <p class="text-sm font-medium text-foreground">Enable URL shortener</p>
                <p class="text-xs text-muted-foreground">
                  Replace commonly blocked public shorteners automatically.
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
                  <span class="text-xs font-medium text-foreground">Replace blacklist only</span>
                  <Switch v-model:checked="form.url_shortener_settings.replace_blacklist_only" />
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
          {{ form.processing ? 'Creating…' : 'Create Profile' }}
        </Button>
      </div>
    </form>
  </DashboardLayout>
</template>

