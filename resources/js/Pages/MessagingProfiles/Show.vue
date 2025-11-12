<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import axios from 'axios'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardDescription from '@/Components/ui/CardDescription.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import Dialog from '@/Components/ui/Dialog.vue'
import DialogContent from '@/Components/ui/DialogContent.vue'
import DialogHeader from '@/Components/ui/DialogHeader.vue'
import DialogTitle from '@/Components/ui/DialogTitle.vue'
import DialogDescription from '@/Components/ui/DialogDescription.vue'
import Select from '@/Components/ui/Select.vue'
import SelectTrigger from '@/Components/ui/SelectTrigger.vue'
import SelectContent from '@/Components/ui/SelectContent.vue'
import SelectItem from '@/Components/ui/SelectItem.vue'
import SelectValue from '@/Components/ui/SelectValue.vue'
import Separator from '@/Components/ui/Separator.vue'
import {
  MessageSquare,
  ShieldCheck,
  Globe2,
  LinkIcon,
  Trash2,
  PlusCircle,
  Loader2,
  Phone,
  Activity,
  ExternalLink,
  RefreshCw
} from 'lucide-vue-next'

const props = defineProps({
  messagingProfile: {
    type: Object,
    required: true
  },
  assignedPhoneNumbers: {
    type: Array,
    default: () => []
  },
  availablePhoneNumbers: {
    type: Array,
    default: () => []
  }
})

const assignDialogOpen = ref(false)
const availableNumbers = ref(props.availablePhoneNumbers || [])
const selectedNumberId = ref('')
const assigning = ref(false)
const unassigningId = ref(null)
const deleting = ref(false)

watch(
  () => props.availablePhoneNumbers,
  (numbers) => {
    availableNumbers.value = numbers || []
  },
  { immediate: true }
)

const statusVariant = (enabled) => (enabled ? 'secondary' : 'destructive')

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

const destinationsLabel = computed(() => {
  const destinations = props.messagingProfile.whitelisted_destinations || []
  if (!destinations.length) return 'Not configured'
  if (destinations.includes('*')) return 'All countries'
  return destinations.join(', ')
})

const webhookStatus = computed(() =>
  props.messagingProfile.webhook_url ? 'Configured' : 'Not set'
)

const assignedNumbers = computed(() => props.assignedPhoneNumbers || [])

const openAssignDialog = () => {
  if (!availableNumbers.value.length) {
    alert('No unassigned phone numbers are currently available.')
    return
  }
  selectedNumberId.value = availableNumbers.value[0]?.id || ''
  assignDialogOpen.value = true
}

const closeAssignDialog = () => {
  assignDialogOpen.value = false
  selectedNumberId.value = ''
}

const assignPhoneNumber = async () => {
  if (!selectedNumberId.value) {
    alert('Select a phone number to assign.')
    return
  }

  assigning.value = true
  try {
    const response = await axios.post(
      `/messaging-profiles/${props.messagingProfile.id}/assign-phone`,
      { phone_number_id: selectedNumberId.value }
    )

    if (response.data?.success) {
      alert('Phone number assigned successfully.')
      window.location.reload()
    } else {
      throw new Error(response.data?.message || 'Assignment failed.')
    }
  } catch (error) {
    console.error('Assign error', error)
    alert(error.message || 'Unable to assign phone number.')
  } finally {
    assigning.value = false
    closeAssignDialog()
  }
}

const unassignPhoneNumber = async (phoneNumberId) => {
  if (!confirm('Unassign this phone number from the messaging profile?')) return

  unassigningId.value = phoneNumberId
  try {
    const response = await axios.delete(
      `/messaging-profiles/${props.messagingProfile.id}/unassign-phone`,
      {
        data: { phone_number_id: phoneNumberId }
      }
    )

    if (response.data?.success) {
      alert('Phone number unassigned.')
      window.location.reload()
    } else {
      throw new Error(response.data?.message || 'Failed to unassign number.')
    }
  } catch (error) {
    console.error('Unassign error', error)
    alert(error.message || 'Unable to unassign phone number.')
  } finally {
    unassigningId.value = null
  }
}

const deleteProfile = async () => {
  if (!confirm('Delete this messaging profile? This action cannot be undone.')) return

  deleting.value = true
  try {
    await axios.delete(`/messaging-profiles/${props.messagingProfile.id}`)
    alert('Messaging profile deleted.')
    window.location.href = route('messaging-profiles.index')
  } catch (error) {
    console.error('Delete error', error)
    alert('Deletion failed. Please try again.')
    deleting.value = false
  }
}
</script>

<template>
  <DashboardLayout>
    <Head :title="`Messaging Profile · ${messagingProfile.name}`" />

    <template #header>
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Messaging Profile</h1>
          <p class="text-sm text-muted-foreground">
            Inspect profile configuration, webhook endpoints, and linked phone numbers.
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Link
            :href="route('messaging-profiles.edit', messagingProfile.id)"
            class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition hover:bg-accent hover:text-accent-foreground"
          >
            <ShieldCheck class="h-4 w-4" />
            Edit Profile
          </Link>
          <Button
            variant="destructive"
            class="gap-2"
            :disabled="deleting"
            @click="deleteProfile"
          >
            <Loader2 v-if="deleting" class="h-4 w-4 animate-spin" />
            <Trash2 v-else class="h-4 w-4" />
            Delete
          </Button>
          <Link
            :href="route('messaging-profiles.index')"
            class="inline-flex items-center gap-2 text-sm text-muted-foreground transition hover:text-foreground"
          >
            Back to Profiles
          </Link>
        </div>
      </div>
    </template>

    <div class="space-y-6 pb-12">
      <Card>
        <CardHeader>
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
              <CardTitle class="text-2xl font-semibold">{{ messagingProfile.name }}</CardTitle>
              <CardDescription>
                Telnyx ID • {{ messagingProfile.telnyx_profile_id || 'Unavailable' }}
              </CardDescription>
            </div>
            <Badge :variant="statusVariant(messagingProfile.enabled)" class="capitalize">
              {{ messagingProfile.enabled ? 'Enabled' : 'Disabled' }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-foreground">Created</span>
              <Calendar class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
              {{ formatDateTime(messagingProfile.created_at) }}
            </p>
          </div>
          <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-foreground">Updated</span>
              <RefreshCw class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
              {{ formatDateTime(messagingProfile.updated_at) }}
            </p>
          </div>
          <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-foreground">Daily spend limit</span>
              <Activity class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
              <span v-if="messagingProfile.daily_spend_limit && messagingProfile.daily_spend_limit_enabled">
                ${{ messagingProfile.daily_spend_limit }}
              </span>
              <span v-else>Not set</span>
            </p>
          </div>
          <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-foreground">Alpha sender ID</span>
              <MessageSquare class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
              {{ messagingProfile.alpha_sender || 'Not configured' }}
            </p>
          </div>
          <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-foreground">MMS fallback to SMS</span>
              <MessageSquare class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
              {{ messagingProfile.mms_fall_back_to_sms ? 'Enabled' : 'Disabled' }}
            </p>
          </div>
          <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-foreground">MMS transcoding</span>
              <MessageSquare class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
              {{ messagingProfile.mms_transcoding ? 'Enabled' : 'Disabled' }}
            </p>
          </div>
        </CardContent>
      </Card>

      <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Webhook configuration</CardTitle>
              <CardDescription>Endpoints receiving delivery and status callbacks.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3 text-sm text-muted-foreground">
              <div class="flex items-center justify-between">
                <span class="font-medium text-foreground">Primary URL</span>
                <span class="max-w-md truncate text-right">
                  {{ messagingProfile.webhook_url || 'Not set' }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="font-medium text-foreground">Failover URL</span>
                <span class="max-w-md truncate text-right">
                  {{ messagingProfile.webhook_failover_url || 'Not set' }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="font-medium text-foreground">API version</span>
                <span>{{ messagingProfile.webhook_api_version || 'Not specified' }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="font-medium text-foreground">Status</span>
                <Badge variant="outline">{{ webhookStatus }}</Badge>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Whitelisted destinations</CardTitle>
              <CardDescription>Countries permitted for outbound messaging.</CardDescription>
            </CardHeader>
            <CardContent>
              <div class="rounded-lg border border-border/60 bg-muted/30 p-4 text-sm text-muted-foreground">
                <span v-if="destinationsLabel === 'All countries'" class="inline-flex items-center gap-1 font-medium text-foreground">
                  <Globe2 class="h-4 w-4" />
                  All countries
                </span>
                <span v-else>{{ destinationsLabel }}</span>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader class="flex flex-wrap items-center justify-between gap-4">
              <div>
                <CardTitle>Assigned phone numbers</CardTitle>
                <CardDescription>
                  Numbers delivering messages using this profile.
                </CardDescription>
              </div>
              <Button variant="outline" size="sm" class="gap-2" @click="openAssignDialog">
                <Phone class="h-3.5 w-3.5" />
                Assign number
              </Button>
            </CardHeader>
            <CardContent>
              <div v-if="assignedNumbers.length" class="space-y-3">
                <div
                  v-for="number in assignedNumbers"
                  :key="number.id"
                  class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border/60 bg-muted/30 p-4"
                >
                  <div>
                    <p class="font-medium text-foreground">{{ number.phone_number }}</p>
                    <p class="text-xs text-muted-foreground">
                      {{ number.country_code }} • {{ number.number_type || 'Standard' }}
                    </p>
                    <p v-if="number.assigned_to_profile_at" class="text-xs text-muted-foreground">
                      Assigned {{ formatDate(number.assigned_to_profile_at) }}
                    </p>
                  </div>
                  <div class="flex flex-wrap items-center gap-2">
                    <Link
                      :href="route('phone-numbers.show', number.id)"
                      class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium shadow-sm transition hover:bg-accent hover:text-accent-foreground"
                    >
                      <ExternalLink class="h-3.5 w-3.5" />
                      View number
                    </Link>
                    <Button
                      variant="destructive"
                      size="sm"
                      class="gap-2"
                      :disabled="unassigningId === number.id"
                      @click="unassignPhoneNumber(number.id)"
                    >
                      <Loader2
                        v-if="unassigningId === number.id"
                        class="h-3.5 w-3.5 animate-spin"
                      />
                      <Trash2 v-else class="h-3.5 w-3.5" />
                      Unassign
                    </Button>
                  </div>
                </div>
              </div>
              <div
                v-else
                class="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-border px-6 py-12 text-center text-sm text-muted-foreground"
              >
                <Phone class="h-6 w-6 text-muted-foreground" />
                <p>No phone numbers assigned yet.</p>
                <Button variant="outline" size="sm" class="gap-2" @click="openAssignDialog">
                  <Phone class="h-3.5 w-3.5" />
                  Assign number
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>

        <Card>
          <CardHeader class="flex flex-wrap items-center justify-between gap-4">
            <div>
              <CardTitle>Available phone numbers</CardTitle>
              <CardDescription>
                Numbers that can be mapped to this messaging profile.
              </CardDescription>
            </div>
            <Badge variant="outline" class="text-xs font-medium uppercase tracking-wide">
              {{ availableNumbers.length }} available
            </Badge>
          </CardHeader>
          <CardContent>
            <div v-if="availableNumbers.length" class="space-y-3 text-sm text-muted-foreground">
              <div
                v-for="number in availableNumbers"
                :key="number.id"
                class="rounded-lg border border-border/60 bg-muted/30 p-3"
              >
                <p class="font-medium text-foreground">{{ number.phone_number }}</p>
                <p class="text-xs text-muted-foreground">
                  {{ number.country_code }} • {{ number.number_type || 'Standard' }}
                </p>
              </div>
            </div>
            <div
              v-else
              class="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-border px-6 py-12 text-center text-sm text-muted-foreground"
            >
              <MessageSquare class="h-6 w-6 text-muted-foreground" />
              <p>No unassigned numbers available. Purchase or release a number to add it here.</p>
              <Link
                :href="route('phone-numbers.purchase-page')"
                class="inline-flex items-center gap-2 text-xs font-medium text-primary transition hover:text-primary/80"
              >
                Purchase numbers →
              </Link>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>

    <Dialog :open="assignDialogOpen" @update:open="assignDialogOpen = $event">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>Assign phone number</DialogTitle>
          <DialogDescription>
            Select a phone number to route messages with this profile.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4">
          <div>
            <label class="text-xs font-medium uppercase text-muted-foreground">
              Phone number
            </label>
            <Select v-model="selectedNumberId">
              <SelectTrigger class="mt-2">
                <SelectValue placeholder="Select phone number" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="number in availableNumbers"
                  :key="number.id"
                  :value="number.id"
                >
                  {{ number.formatted_number || number.phone_number }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>
          <Separator />
          <div class="flex items-center justify-end gap-2">
            <Button variant="ghost" @click="closeAssignDialog">Cancel</Button>
            <Button class="gap-2" :disabled="assigning" @click="assignPhoneNumber">
              <Loader2 v-if="assigning" class="h-4 w-4 animate-spin" />
              {{ assigning ? 'Assigning…' : 'Assign number' }}
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  </DashboardLayout>
</template>

