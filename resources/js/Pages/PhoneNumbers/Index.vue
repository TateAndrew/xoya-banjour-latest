<template>
  <Head title="Phone Numbers" />

  <DashboardLayout>
    <template #header>
      <div class="flex flex-col gap-2">
        <h1 class="text-3xl font-bold tracking-tight">Phone Numbers</h1>
        <p class="text-muted-foreground">
          Search, purchase, and manage numbers powering your voice and messaging experiences.
        </p>
      </div>
    </template>

    <div class="space-y-6 pb-12">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="space-y-1">
          <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
            Overview
          </p>
          <p class="text-sm text-muted-foreground">
            {{ totalNumbersCount }} numbers in your inventory
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <Link
            :href="route('phone-numbers.manage')"
            class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition hover:bg-accent hover:text-accent-foreground"
          >
            <Settings class="h-4 w-4" />
            Manage Numbers
          </Link>
          <Link
            :href="route('phone-numbers.purchase-page')"
            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90"
          >
            <ShoppingCart class="h-4 w-4" />
            Purchase Number
          </Link>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Numbers</CardTitle>
            <Phone class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ totalNumbersCount }}</div>
            <p class="text-xs text-muted-foreground">
              {{ voiceCapableCount }} with voice, {{ smsCapableCount }} with SMS
            </p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Assigned</CardTitle>
            <ShieldCheck class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ assignedNumbersCount }}</div>
            <p class="text-xs text-muted-foreground">Provisioned to profiles or users</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Voice Ready</CardTitle>
            <PhoneCall class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ voiceCapableCount }}</div>
            <p class="text-xs text-muted-foreground">Support PSTN inbound &amp; outbound</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">SMS Ready</CardTitle>
            <MessageCircle class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ smsCapableCount }}</div>
            <p class="text-xs text-muted-foreground">Messaging enabled numbers</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader class="pb-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <CardTitle class="flex items-center gap-2 text-base">
                <Filter class="h-4 w-4" />
                Search inventory
              </CardTitle>
              <CardDescription>
                Filter available numbers by geography, area code, and capabilities.
              </CardDescription>
            </div>
            <div class="flex items-center gap-2">
              <Badge v-if="searchResults.length" variant="outline" class="uppercase tracking-wide">
                {{ searchResults.length }} found
              </Badge>
              <Button
                variant="ghost"
                size="sm"
                class="h-9 gap-2 text-muted-foreground"
                :disabled="!searchResults.length && !searched"
                @click="clearSearch"
              >
                Reset
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="md:col-span-1">
              <Label class="text-xs uppercase text-muted-foreground">Country</Label>
              <div class="mt-2">
                <Select v-model="searchForm.country_code">
                  <SelectTrigger>
                    <SelectValue placeholder="Select country" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="country in countryOptions" :key="country.value" :value="country.value">
                      {{ country.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div>
              <Label class="text-xs uppercase text-muted-foreground">Area code</Label>
              <Input
                v-model="searchForm.area_code"
                placeholder="e.g. 212"
                class="mt-2"
                maxlength="6"
              />
            </div>

            <div class="md:col-span-2">
              <Label class="text-xs uppercase text-muted-foreground">Capabilities</Label>
              <div class="mt-2 flex flex-wrap gap-2">
                <Button
                  v-for="feature in featureOptions"
                  :key="feature.value"
                  type="button"
                  variant="outline"
                  size="sm"
                  class="gap-2"
                  :class="{
                    'border-primary bg-primary/10 text-primary': isFeatureSelected(feature.value)
                  }"
                  @click="toggleFeature(feature.value)"
                >
                  <component :is="feature.icon" class="h-4 w-4" />
                  {{ feature.label }}
                </Button>
              </div>
            </div>

            <div class="flex items-end">
              <Button
                class="w-full gap-2"
                :disabled="searching"
                @click="searchNumbers"
              >
                <Loader2 v-if="searching" class="h-4 w-4 animate-spin" />
                <Search v-else class="h-4 w-4" />
                {{ searching ? 'Searching…' : 'Search' }}
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-wrap items-center justify-between gap-4 pb-3">
          <div>
            <CardTitle class="text-lg font-semibold">Available numbers</CardTitle>
            <CardDescription>
              Purchase instantly to add routing and assign to messaging profiles.
            </CardDescription>
          </div>
          <Badge variant="outline" class="text-xs font-medium uppercase tracking-wide">
            {{ searchResults.length }} results
          </Badge>
        </CardHeader>
        <CardContent>
          <div
            v-if="searchResults.length === 0"
            class="flex flex-col items-center justify-center gap-4 rounded-lg border border-dashed border-border bg-muted/40 px-8 py-16 text-center"
          >
            <ShoppingCart class="h-10 w-10 text-muted-foreground" />
            <div class="space-y-1">
              <h3 class="text-lg font-semibold">
                {{ searched ? 'No numbers match your filters.' : 'Use the filters above to discover numbers.' }}
              </h3>
              <p class="text-sm text-muted-foreground">
                Try a different region or capability mix to see more results.
              </p>
            </div>
          </div>

          <div
            v-else
            class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
          >
            <Card
              v-for="number in searchResults"
              :key="number.phone_number"
              class="border-border/60"
            >
              <CardHeader class="space-y-2 pb-2">
                <div class="flex items-start justify-between gap-3">
                  <div>
                    <CardTitle class="text-lg font-semibold tracking-wide">
                      {{ formatPhoneNumber(number.phone_number) }}
                    </CardTitle>
                    <CardDescription>
                      {{ number.phone_number_type ? number.phone_number_type.toUpperCase() : 'Local' }}
                    </CardDescription>
                  </div>
                  <Button
                    size="sm"
                    class="gap-2"
                    :disabled="purchasingNumber === number.phone_number"
                    @click="purchaseNumber(number)"
                  >
                    <Loader2
                      v-if="purchasingNumber === number.phone_number"
                      class="h-4 w-4 animate-spin"
                    />
                    <PlusCircle v-else class="h-4 w-4" />
                    {{ purchasingNumber === number.phone_number ? 'Purchasing…' : 'Purchase' }}
                  </Button>
                </div>
              </CardHeader>
              <CardContent class="space-y-3 text-sm text-muted-foreground">
                <div class="flex items-center justify-between">
                  <span>Monthly</span>
                  <span class="font-medium text-foreground">
                    {{ formatCurrency(number.cost_information?.monthly_cost) }}
                  </span>
                </div>
                <div class="flex items-center justify-between border-b border-border/60 pb-3">
                  <span>Setup</span>
                  <span class="font-medium text-foreground">
                    {{ formatCurrency(number.cost_information?.upfront_cost) }}
                  </span>
                </div>
                <div class="space-y-1">
                  <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground/70">
                    Capabilities
                  </p>
                  <div class="flex flex-wrap gap-1.5">
                    <Badge
                      v-for="feature in getFeatureNames(number.features)"
                      :key="feature"
                      variant="outline"
                      class="text-[11px] font-medium uppercase tracking-wide"
                    >
                      {{ feature }}
                    </Badge>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-wrap items-center justify-between gap-4 pb-3">
          <div>
            <CardTitle class="text-lg font-semibold">Your numbers</CardTitle>
            <CardDescription>
              Track statuses, capabilities, and jump into detailed configuration.
            </CardDescription>
          </div>
          <Badge variant="outline" class="text-xs font-medium uppercase tracking-wide">
            {{ totalNumbersCount }} owned
          </Badge>
        </CardHeader>
        <CardContent>
          <div
            v-if="!userNumbers.length"
            class="flex flex-col items-center justify-center gap-4 rounded-lg border border-dashed border-border bg-muted/40 px-8 py-16 text-center"
          >
            <Phone class="h-10 w-10 text-muted-foreground" />
            <div class="space-y-1">
              <h3 class="text-lg font-semibold">No phone numbers yet.</h3>
              <p class="text-sm text-muted-foreground">
                Purchase a number to begin routing calls and messages through the platform.
              </p>
            </div>
            <Link
              :href="route('phone-numbers.purchase-page')"
              class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90"
            >
              <ShoppingCart class="h-4 w-4" />
              Purchase Number
            </Link>
          </div>

          <div v-else class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
              <thead class="bg-muted/80 text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                  <th class="px-4 py-3 text-left font-medium">Number</th>
                  <th class="px-4 py-3 text-left font-medium">Capabilities</th>
                  <th class="px-4 py-3 text-left font-medium">Status</th>
                  <th class="px-4 py-3 text-left font-medium">Purchased</th>
                  <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="number in userNumbers"
                  :key="number.id"
                  class="border-t bg-background/80 transition hover:bg-muted/40"
                >
                  <td class="px-4 py-4 align-top">
                    <p class="font-medium text-foreground">
                      {{ formatPhoneNumber(number.phone_number) }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                      {{ number.friendly_name || 'Telnyx Provisioned' }}
                    </p>
                  </td>
                  <td class="px-4 py-4 align-top">
                    <div class="flex flex-wrap gap-1.5">
                      <Badge
                        v-for="capability in capabilityLabels(number.capabilities)"
                        :key="capability"
                        variant="outline"
                        class="text-[11px] font-medium uppercase tracking-wide"
                      >
                        {{ capability }}
                      </Badge>
                    </div>
                  </td>
                  <td class="px-4 py-4 align-top">
                    <Badge :variant="statusVariant(number.status)" class="capitalize">
                      {{ number.status || 'unknown' }}
                    </Badge>
                  </td>
                  <td class="px-4 py-4 align-top text-sm text-muted-foreground">
                    {{ formatDate(number.purchased_at) }}
                  </td>
                  <td class="px-4 py-4 align-top">
                    <div class="flex justify-end gap-2">
                      <Link
                        :href="route('phone-numbers.show', number.id)"
                        class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium shadow-sm transition hover:bg-accent hover:text-accent-foreground"
                      >
                        <Eye class="h-3.5 w-3.5" />
                        View
                      </Link>
                      <Link
                        :href="route('phone-numbers.edit-recording-settings', number.id)"
                        class="inline-flex items-center gap-2 rounded-md bg-primary/10 px-3 py-1.5 text-xs font-medium text-primary transition hover:bg-primary/20"
                      >
                        <Mic class="h-3.5 w-3.5" />
                        Recording
                      </Link>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardDescription from '@/Components/ui/CardDescription.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import Button from '@/Components/ui/Button.vue'
import Input from '@/Components/ui/Input.vue'
import Label from '@/Components/ui/Label.vue'
import Badge from '@/Components/ui/Badge.vue'
import Select from '@/Components/ui/Select.vue'
import SelectTrigger from '@/Components/ui/SelectTrigger.vue'
import SelectContent from '@/Components/ui/SelectContent.vue'
import SelectItem from '@/Components/ui/SelectItem.vue'
import SelectValue from '@/Components/ui/SelectValue.vue'
import {
  Filter,
  Search,
  Loader2,
  ShoppingCart,
  Settings,
  Phone,
  ShieldCheck,
  PhoneCall,
  MessageCircle,
  PlusCircle,
  Mic,
  Eye
} from 'lucide-vue-next'
import axios from 'axios'

const props = defineProps({
  userNumbers: {
    type: Array,
    default: () => []
  }
})

const countryOptions = [
  { value: 'US', label: 'United States' },
  { value: 'CA', label: 'Canada' },
  { value: 'GB', label: 'United Kingdom' },
  { value: 'AU', label: 'Australia' }
]

const featureOptions = [
  { value: 'voice', label: 'Voice', icon: PhoneCall },
  { value: 'sms', label: 'SMS', icon: MessageCircle }
]

const searchForm = reactive({
  country_code: 'US',
  area_code: '',
  features: ['voice', 'sms'],
  limit: 20
})

const searchResults = ref([])
const searching = ref(false)
const searched = ref(false)
const purchasingNumber = ref(null)

const normalizeStringArray = (items) => {
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
    .map((value) => value.toString().toLowerCase())
}

const hasCapability = (number, capability) =>
  normalizeStringArray(number?.capabilities).includes(capability)

const capabilityLabels = (capabilities) =>
  normalizeStringArray(capabilities).map((capability) => capability.toUpperCase())

const totalNumbersCount = computed(() => props.userNumbers.length)
const assignedNumbersCount = computed(() =>
  props.userNumbers.filter((number) =>
    ['assigned', 'purchased', 'active'].includes((number.status || '').toLowerCase())
  ).length
)
const voiceCapableCount = computed(() =>
  props.userNumbers.filter((number) => hasCapability(number, 'voice')).length
)
const smsCapableCount = computed(() =>
  props.userNumbers.filter((number) => hasCapability(number, 'sms')).length
)

const searchNumbers = async () => {
  searching.value = true
  try {
    const payload = {
      country_code: searchForm.country_code,
      area_code: searchForm.area_code,
      features: [...searchForm.features],
      limit: searchForm.limit
    }

    const response = await axios.post(route('phone-numbers.search'), payload)

    if (response.data.success) {
      searchResults.value = response.data.data || []
    } else {
      alert('Error searching numbers: ' + (response.data.error || 'Unknown error'))
    }

    searched.value = true
  } catch (error) {
    console.error('Search error:', error)
    const message =
      error.response?.data?.error || 'Error searching numbers. Please try again.'
    alert(message)
  } finally {
    searching.value = false
  }
}

const purchaseNumber = async (number) => {
  if (!confirm(`Purchase ${formatPhoneNumber(number.phone_number)}?`)) {
    return
  }

  purchasingNumber.value = number.phone_number
  try {
    const response = await axios.post(route('phone-numbers.purchase'), {
      phone_number: number.phone_number,
      country_code: searchForm.country_code,
      features: [...searchForm.features]
    })

    if (response.data.success) {
      alert('Phone number purchased successfully!')
      router.reload()
    } else {
      alert('Error purchasing number: ' + (response.data.error || 'Unknown error'))
    }
  } catch (error) {
    console.error('Purchase error:', error)
    const message =
      error.response?.data?.error || 'Error purchasing number. Please try again.'
    alert(message)
  } finally {
    purchasingNumber.value = null
  }
}

const clearSearch = () => {
  searchForm.country_code = 'US'
  searchForm.area_code = ''
  searchForm.features = ['voice', 'sms']
  searchResults.value = []
  searched.value = false
}

const isFeatureSelected = (value) => searchForm.features.includes(value)

const toggleFeature = (value) => {
  const index = searchForm.features.indexOf(value)
  if (index > -1) {
    searchForm.features.splice(index, 1)
  } else {
    searchForm.features.push(value)
  }
}

const getFeatureNames = (features) =>
  normalizeStringArray(features).map((feature) => feature.toUpperCase())

const formatCurrency = (value) => {
  if (value == null || Number.isNaN(Number(value))) {
    return '—'
  }

  return `$${Number(value).toFixed(2)}`
}

const formatPhoneNumber = (number) => {
  if (!number) return 'Unknown'
  const digits = number.replace(/\D/g, '')

  if (digits.length === 11) {
    return number.replace(/(\d{1})(\d{3})(\d{3})(\d{4})/, '+$1 ($2) $3-$4')
  }

  return number
}

const formatDate = (date) => {
  if (!date) return '—'
  const parsed = new Date(date)
  if (Number.isNaN(parsed.getTime())) return '—'

  return parsed.toLocaleDateString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
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
</script>