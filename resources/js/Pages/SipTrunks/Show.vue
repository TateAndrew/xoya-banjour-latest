<script setup>
import { ref, computed, watch } from 'vue'
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
  CloudCog,
  ShieldCheck,
  Phone,
  PhoneCall,
  Activity,
  Calendar,
  AlertTriangle,
  Loader2,
  Mic,
  Power,
  ArrowLeft,
  LinkIcon,
  Trash2
} from 'lucide-vue-next'

const props = defineProps({
  sipTrunk: {
    type: Object,
    required: true
  },
  telnyxDetails: {
    type: Object,
    default: () => ({ success: false })
  },
  availablePhoneNumbers: {
    type: Array,
    default: () => []
  }
})

const assignDialogOpen = ref(false)
const availableNumbers = ref(props.availablePhoneNumbers || [])
const selectedNumberId = ref('')
const assignmentType = ref('primary')
const assigning = ref(false)
const testing = ref(false)
const unassigningId = ref(null)

watch(
  () => props.availablePhoneNumbers,
  (numbers) => {
    availableNumbers.value = numbers || []
  },
  { immediate: true }
)

const statusVariant = (status) => {
  switch ((status || '').toLowerCase()) {
    case 'active':
      return 'secondary'
    case 'pending':
      return 'outline'
    case 'failed':
    case 'inactive':
      return 'destructive'
    default:
      return 'outline'
  }
}

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

const formatDuration = (seconds) => {
  if (!seconds) return '—'
  const mins = Math.floor(seconds / 60)
  const remaining = seconds % 60
  return `${mins}:${remaining.toString().padStart(2, '0')}m`
}

const daysActive = computed(() => {
  if (!props.sipTrunk.created_at) return 0
  const created = new Date(props.sipTrunk.created_at)
  const now = new Date()
  const diff = Math.abs(now.getTime() - created.getTime())
  return Math.max(0, Math.ceil(diff / (1000 * 60 * 60 * 24)))
})

const normalizedNumbers = computed(() => props.sipTrunk.phone_numbers || [])
const callHistory = computed(() => (props.sipTrunk.calls || []).slice(0, 5))

const telnyxMeta = computed(() => {
  if (!props.telnyxDetails?.success) return null
  return props.telnyxDetails
})

const openAssignDialog = () => {
  selectedNumberId.value = ''
  assignmentType.value = 'primary'
  if (!availableNumbers.value.length) {
    alert('No unassigned phone numbers are currently available.')
    return
  }
  assignDialogOpen.value = true
}

const closeAssignDialog = () => {
  assignDialogOpen.value = false
  selectedNumberId.value = ''
  assignmentType.value = 'primary'
}

const assignPhoneNumber = async () => {
  if (!selectedNumberId.value) {
    alert('Select a phone number to assign.')
    return
  }

  assigning.value = true
  try {
    const response = await axios.post(
      route('sip-trunks.assign-number', props.sipTrunk.id),
      {
        phone_number_id: selectedNumberId.value,
        assignment_type: assignmentType.value
      }
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
  if (!confirm('Unassign this phone number from the trunk?')) return

  unassigningId.value = phoneNumberId
  try {
    const response = await axios.delete(
      route('sip-trunks.unassign-number', [props.sipTrunk.id, phoneNumberId])
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

const testConnection = async () => {
  testing.value = true
  try {
    const response = await axios.post(route('sip-trunks.test', props.sipTrunk.id))
    if (response.data?.success) {
      alert('Connection test completed successfully.')
    } else {
      alert('Connection test failed: ' + (response.data?.message || 'Unknown error'))
    }
  } catch (error) {
    console.error('Test connection error', error)
    alert('Unable to test the connection. Please try again.')
  } finally {
    testing.value = false
  }
}

const activateTrunk = async () => {
  if (!confirm('Activate this SIP trunk?')) return
  try {
    await axios.post(route('sip-trunks.activate', props.sipTrunk.id))
    alert('SIP trunk activated.')
    window.location.reload()
  } catch (error) {
    console.error('Activate error', error)
    alert('Activation failed. Please try again.')
  }
}

const deactivateTrunk = async () => {
  if (!confirm('Deactivate this SIP trunk?')) return
  try {
    await axios.post(route('sip-trunks.deactivate', props.sipTrunk.id))
    alert('SIP trunk deactivated.')
    window.location.reload()
  } catch (error) {
    console.error('Deactivate error', error)
    alert('Deactivation failed. Please try again.')
  }
}

const deleteTrunk = async () => {
  if (!confirm('Delete this SIP trunk? This action cannot be undone.')) return
  try {
    await axios.delete(route('sip-trunks.destroy', props.sipTrunk.id))
    alert('SIP trunk deleted.')
    window.location.href = route('sip-trunks.index')
  } catch (error) {
    console.error('Delete error', error)
    alert('Deletion failed. Please try again.')
  }
}
</script>

<template>
  <DashboardLayout>
    <Head :title="`SIP Trunk · ${sipTrunk.name}`" />

    <template #header>
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">SIP Trunk</h1>
          <p class="text-sm text-muted-foreground">
            Inspect trunk metadata, assigned numbers, and carrier connectivity.
          </p>
        </div>
        <div class="flex flex-wrap gap-2">
          <Link
            :href="route('sip-trunks.edit', sipTrunk.id)"
            class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition hover:bg-accent hover:text-accent-foreground"
          >
            <CloudCog class="h-4 w-4" />
            Edit Trunk
          </Link>
          <Link
            :href="route('sip-trunks.index')"
            class="inline-flex items-center gap-2 text-sm text-muted-foreground transition hover:text-foreground"
          >
            <ArrowLeft class="h-4 w-4" />
            Back to Trunks
          </Link>
        </div>
      </div>
    </template>

    <div class="space-y-6 pb-12">
      <Card>
        <CardHeader>
          <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
              <CardTitle class="text-2xl font-semibold">{{ sipTrunk.name }}</CardTitle>
              <CardDescription>
                Telnyx ID • {{ sipTrunk.telnyx_connection_id || 'Unavailable' }}
              </CardDescription>
            </div>
            <Badge :variant="statusVariant(sipTrunk.status)" class="capitalize">
              {{ sipTrunk.status || 'Unknown' }}
            </Badge>
          </div>
        </CardHeader>
        <CardContent class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-foreground">Connection Type</span>
              <CloudCog class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
              {{ sipTrunk.connection_type || 'Not specified' }}
            </p>
          </div>
          <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-foreground">Created</span>
              <Calendar class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
              {{ formatDateTime(sipTrunk.created_at) }}
            </p>
          </div>
          <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-foreground">Days Active</span>
              <ShieldCheck class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
              {{ daysActive }} days
            </p>
          </div>
          <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-foreground">Activated</span>
              <Power class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
              {{ formatDateTime(sipTrunk.activated_at) }}
            </p>
          </div>
          <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-foreground">Last health check</span>
              <Activity class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
              {{ formatDateTime(sipTrunk.last_health_check) }}
            </p>
          </div>
          <div class="rounded-lg border border-border/60 bg-muted/30 p-4">
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-foreground">Assigned numbers</span>
              <Phone class="h-4 w-4 text-muted-foreground" />
            </div>
            <p class="mt-2 text-sm text-muted-foreground">
              {{ normalizedNumbers.length }}
            </p>
          </div>
        </CardContent>
      </Card>

      <div class="grid gap-6 lg:grid-cols-[2fr_1fr]">
        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Assigned phone numbers</CardTitle>
              <CardDescription>
                Manage routing relationships between this trunk and phone numbers.
              </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="flex flex-wrap items-center justify-between gap-3">
                <span class="text-xs font-medium uppercase text-muted-foreground">
                  {{ normalizedNumbers.length }} numbers linked
                </span>
                <Button variant="outline" size="sm" class="gap-2" @click="openAssignDialog">
                  <Phone class="h-3.5 w-3.5" />
                  Assign number
                </Button>
              </div>

              <div v-if="normalizedNumbers.length" class="space-y-3">
                <div
                  v-for="number in normalizedNumbers"
                  :key="number.id"
                  class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border/60 bg-muted/30 p-4"
                >
                  <div>
                    <p class="font-medium text-foreground">{{ number.phone_number }}</p>
                    <div class="mt-1 flex flex-wrap gap-2 text-xs text-muted-foreground">
                      <Badge variant="outline" class="uppercase">
                        {{ number.pivot.assignment_type }}
                      </Badge>
                      <Badge
                        :variant="number.pivot.is_active ? 'secondary' : 'destructive'"
                        class="uppercase"
                      >
                        {{ number.pivot.is_active ? 'Active' : 'Inactive' }}
                      </Badge>
                      <span>Assigned {{ formatDate(number.pivot.assigned_at) }}</span>
                      <span v-if="number.pivot.last_used_at">
                        • Last used {{ formatDateTime(number.pivot.last_used_at) }}
                      </span>
                    </div>
                  </div>
                  <div class="flex flex-wrap items-center gap-2">
                    <Link
                      :href="route('phone-numbers.show', number.id)"
                      class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium shadow-sm transition hover:bg-accent hover:text-accent-foreground"
                    >
                      <LinkIcon class="h-3.5 w-3.5" />
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
                class="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-border bg-muted/40 px-6 py-12 text-center"
              >
                <Phone class="h-10 w-10 text-muted-foreground" />
                <div class="space-y-1">
                  <p class="text-sm font-medium text-foreground">No phone numbers assigned</p>
                  <p class="text-xs text-muted-foreground">
                    Link a number to start routing inbound calls through this trunk.
                  </p>
                </div>
                <Button variant="outline" size="sm" class="gap-2" @click="openAssignDialog">
                  <Phone class="h-3.5 w-3.5" />
                  Assign number
                </Button>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Telnyx metadata</CardTitle>
              <CardDescription>
                Connection details fetched directly from the Telnyx API.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="telnyxMeta" class="space-y-3 text-sm text-muted-foreground">
                <div class="flex justify-between">
                  <span class="font-medium text-foreground">Connection ID</span>
                  <span>{{ telnyxMeta.connection_id || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-foreground">Status</span>
                  <span>{{ telnyxMeta.status || '—' }}</span>
                </div>
                <div class="flex justify-between">
                  <span class="font-medium text-foreground">Created</span>
                  <span>{{ formatDateTime(telnyxMeta.created_at) }}</span>
                </div>
                <pre class="max-h-64 overflow-auto rounded-md bg-muted/40 p-3 text-xs text-muted-foreground">
{{ JSON.stringify(telnyxMeta.data || {}, null, 2) }}
                </pre>
              </div>
              <div v-else class="flex flex-col items-center justify-center gap-3 border border-dashed border-border px-6 py-12 text-center text-sm text-muted-foreground">
                <AlertTriangle class="h-5 w-5 text-amber-500" />
                <p>Telnyx metadata unavailable. Trigger a sync from the trunk list when needed.</p>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Recent calls</CardTitle>
              <CardDescription>Last interactions routed through this SIP trunk.</CardDescription>
            </CardHeader>
            <CardContent>
              <div v-if="callHistory.length" class="space-y-3">
                <div
                  v-for="call in callHistory"
                  :key="call.id"
                  class="rounded-lg border border-border/60 bg-muted/30 p-3 text-sm"
                >
                  <div class="flex items-center justify-between">
                    <p class="font-medium text-foreground">
                      {{ call.from }} → {{ call.to }}
                    </p>
                    <Badge
                      :variant="call.status === 'completed' ? 'secondary' : call.status === 'failed' ? 'destructive' : 'outline'"
                      class="capitalize"
                    >
                      {{ call.status || 'unknown' }}
                    </Badge>
                  </div>
                  <p class="text-xs text-muted-foreground">
                    {{ formatDateTime(call.created_at) }}
                    <span v-if="call.duration">• {{ formatDuration(call.duration) }}</span>
                  </p>
                </div>
                <div class="text-right">
                  <Link
                    :href="route('dialer.history')"
                    class="text-xs font-medium text-primary transition hover:text-primary/80"
                  >
                    View full call history →
                  </Link>
                </div>
              </div>
              <div
                v-else
                class="flex flex-col items-center justify-center gap-3 rounded-lg border border-dashed border-border px-6 py-12 text-center text-sm text-muted-foreground"
              >
                <PhoneCall class="h-6 w-6 text-muted-foreground" />
                <p>No calls have been routed through this trunk yet.</p>
              </div>
            </CardContent>
          </Card>
        </div>

        <div class="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Actions</CardTitle>
              <CardDescription>Operational tools for this SIP trunk.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-3">
              <Button
                variant="outline"
                class="w-full justify-start gap-2"
                :disabled="testing"
                @click="testConnection"
              >
                <Loader2 v-if="testing" class="h-4 w-4 animate-spin" />
                <Activity v-else class="h-4 w-4" />
                {{ testing ? 'Testing connection…' : 'Test connection' }}
              </Button>

              <Button
                v-if="(sipTrunk.status || '').toLowerCase() === 'active'"
                variant="outline"
                class="w-full justify-start gap-2 text-amber-700"
                @click="deactivateTrunk"
              >
                <Power class="h-4 w-4" />
                Deactivate trunk
              </Button>
              <Button
                v-else
                variant="outline"
                class="w-full justify-start gap-2 text-green-700"
                @click="activateTrunk"
              >
                <Power class="h-4 w-4" />
                Activate trunk
              </Button>

              <Link
                :href="route('dialer')"
                class="inline-flex w-full items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm font-medium shadow-sm transition hover:bg-accent hover:text-accent-foreground"
              >
                <Mic class="h-4 w-4" />
                Use in dialer
              </Link>

              <Button variant="destructive" class="w-full justify-start gap-2" @click="deleteTrunk">
                <Trash2 class="h-4 w-4" />
                Delete trunk
              </Button>
            </CardContent>
          </Card>

          <Card v-if="sipTrunk.notes">
            <CardHeader>
              <CardTitle>Notes</CardTitle>
              <CardDescription>Operational context and reminders.</CardDescription>
            </CardHeader>
            <CardContent>
              <p class="text-sm text-muted-foreground whitespace-pre-line">
                {{ sipTrunk.notes }}
              </p>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>

    <Dialog :open="assignDialogOpen" @update:open="assignDialogOpen = $event">
      <DialogContent class="max-w-lg">
        <DialogHeader>
          <DialogTitle>Assign phone number</DialogTitle>
          <DialogDescription>
            Link an available phone number to route through this SIP trunk.
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
          <div>
            <label class="text-xs font-medium uppercase text-muted-foreground">
              Assignment type
            </label>
            <Select v-model="assignmentType">
              <SelectTrigger class="mt-2">
                <SelectValue placeholder="Select type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="primary">Primary</SelectItem>
                <SelectItem value="secondary">Secondary</SelectItem>
                <SelectItem value="backup">Backup</SelectItem>
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
<template>
    <DashboardLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    SIP Trunk: {{ sipTrunk.name }}
                </h2>
                <div class="flex space-x-3">
                    <Link
                        :href="route('sip-trunks.edit', sipTrunk.id)"
                        class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md font-semibold text-xs text-blue-700 uppercase tracking-widest shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Edit
                    </Link>
                    <Link
                        :href="route('sip-trunks.index')"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    >
                        Back to List
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Success Message -->
                <div v-if="$page.props.flash.success" class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ $page.props.flash.success }}
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Basic Information -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ sipTrunk.name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                                        <dd class="mt-1">
                                            <span
                                                :class="{
                                                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium': true,
                                                    'bg-green-100 text-green-800': sipTrunk.status === 'active',
                                                    'bg-yellow-100 text-yellow-800': sipTrunk.status === 'pending',
                                                    'bg-red-100 text-red-800': sipTrunk.status === 'failed',
                                                    'bg-gray-100 text-gray-800': sipTrunk.status === 'inactive'
                                                }"
                                            >
                                                {{ sipTrunk.status }}
                                            </span>
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Connection Type</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ sipTrunk.connection_type }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(sipTrunk.created_at) }}</dd>
                                    </div>
                                    <div v-if="sipTrunk.activated_at">
                                        <dt class="text-sm font-medium text-gray-500">Activated</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(sipTrunk.activated_at) }}</dd>
                                    </div>
                                    <div v-if="sipTrunk.last_health_check">
                                        <dt class="text-sm font-medium text-gray-500">Last Health Check</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(sipTrunk.last_health_check) }}</dd>
                                    </div>
                                </div>
                                
                                <div v-if="sipTrunk.notes" class="mt-4">
                                    <dt class="text-sm font-medium text-gray-500">Notes</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ sipTrunk.notes }}</dd>
                                </div>
                            </div>
                        </div>

                        <!-- Telnyx Connection Details -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Telnyx Connection</h3>
                                <div v-if="telnyxDetails.success" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Connection ID</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ telnyxDetails.connection_id }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ telnyxDetails.status }}</dd>
                                    </div>
                                    <div v-if="telnyxDetails.created_at">
                                        <dt class="text-sm font-medium text-gray-500">Created in Telnyx</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ formatDate(telnyxDetails.created_at) }}</dd>
                                    </div>
                                </div>
                                <div v-else class="text-sm text-red-600">
                                    Failed to load Telnyx connection details: {{ telnyxDetails.error }}
                                </div>
                            </div>
                        </div>

                        <!-- Phone Numbers -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <div class="flex justify-between items-center mb-4">
                                    <h3 class="text-lg font-medium text-gray-900">Assigned Phone Numbers</h3>
                                    <div class="flex items-center space-x-3">
                                        <span class="text-sm text-gray-500">{{ sipTrunk.phone_numbers.length }} assigned</span>
                                        <button
                                            @click="showAssignModal = true"
                                            class="inline-flex items-center px-3 py-1 border border-blue-300 rounded-md text-xs text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                        >
                                            Assign Number
                                        </button>
                                    </div>
                                </div>
                                
                                <div v-if="sipTrunk.phone_numbers.length > 0" class="space-y-3">
                                    <div
                                        v-for="phoneNumber in sipTrunk.phone_numbers"
                                        :key="phoneNumber.id"
                                        class="flex items-center justify-between p-3 border border-gray-200 rounded-lg"
                                    >
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3">
                                                <span class="text-sm font-medium text-gray-900">
                                                    {{ phoneNumber.phone_number }}
                                                </span>
                                                <span
                                                    :class="{
                                                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium': true,
                                                        'bg-blue-100 text-blue-800': phoneNumber.pivot.assignment_type === 'primary',
                                                        'bg-green-100 text-green-800': phoneNumber.pivot.assignment_type === 'secondary',
                                                        'bg-orange-100 text-orange-800': phoneNumber.pivot.assignment_type === 'backup'
                                                    }"
                                                >
                                                    {{ phoneNumber.pivot.assignment_type }}
                                                </span>
                                                <span
                                                    :class="{
                                                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium': true,
                                                        'bg-green-100 text-green-800': phoneNumber.pivot.is_active,
                                                        'bg-red-100 text-red-800': !phoneNumber.pivot.is_active
                                                    }"
                                                >
                                                    {{ phoneNumber.pivot.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500">
                                                Assigned: {{ formatDate(phoneNumber.pivot.assigned_at) }}
                                                <span v-if="phoneNumber.pivot.last_used_at">
                                                    • Last used: {{ formatDate(phoneNumber.pivot.last_used_at) }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <div class="flex space-x-2">
                                            <Link
                                                :href="route('phone-numbers.show', phoneNumber.id)"
                                                class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                            >
                                                View Details
                                            </Link>
                                            <button
                                                @click="unassignPhoneNumber(phoneNumber.id)"
                                                class="text-red-600 hover:text-red-800 text-sm font-medium"
                                                :disabled="unassigning === phoneNumber.id"
                                            >
                                                {{ unassigning === phoneNumber.id ? 'Unassigning...' : 'Unassign' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div v-else class="text-center py-8 text-gray-500">
                                    <PhoneIcon class="mx-auto h-12 w-12 text-gray-400 mb-2" />
                                    <p>No phone numbers assigned to this trunk</p>
                                    <button
                                        @click="showAssignModal = true"
                                        class="mt-3 inline-flex items-center px-4 py-2 border border-blue-300 rounded-md text-sm text-blue-700 bg-blue-50 hover:bg-blue-100"
                                    >
                                        Assign Phone Number
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Call History -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Calls</h3>
                                <div v-if="sipTrunk.calls && sipTrunk.calls.length > 0" class="space-y-3">
                                    <div
                                        v-for="call in sipTrunk.calls.slice(0, 5)"
                                        :key="call.id"
                                        class="flex items-center justify-between p-3 border border-gray-200 rounded-lg"
                                    >
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-3">
                                                <span class="text-sm font-medium text-gray-900">
                                                    {{ call.from }} → {{ call.to }}
                                                </span>
                                                <span
                                                    :class="{
                                                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium': true,
                                                        'bg-green-100 text-green-800': call.status === 'completed',
                                                        'bg-red-100 text-red-800': call.status === 'failed',
                                                        'bg-yellow-100 text-yellow-800': call.status === 'in-progress'
                                                    }"
                                                >
                                                    {{ call.status }}
                                                </span>
                                            </div>
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ formatDate(call.created_at) }}
                                                <span v-if="call.duration">
                                                    • Duration: {{ formatDuration(call.duration) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div v-if="sipTrunk.calls.length > 5" class="text-center py-2">
                                        <Link
                                            :href="route('dialer.history')"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                        >
                                            View all calls →
                                        </Link>
                                    </div>
                                </div>
                                
                                <div v-else class="text-center py-8 text-gray-500">
                                    <p>No calls made through this trunk yet</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Actions -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Actions</h3>
                                <div class="space-y-3">
                                    <!-- Test Connection -->
                                    <button
                                        @click="testConnection"
                                        :disabled="testing"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                                    >
                                        <span v-if="testing">Testing...</span>
                                        <span v-else>Test Connection</span>
                                    </button>

                                    <!-- Status Toggle -->
                                    <button
                                        v-if="sipTrunk.status === 'active'"
                                        @click="deactivateTrunk"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-yellow-300 rounded-md font-semibold text-xs text-yellow-700 uppercase tracking-widest shadow-sm hover:bg-yellow-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                                    >
                                        Deactivate Trunk
                                    </button>
                                    <button
                                        v-else
                                        @click="activateTrunk"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-green-300 rounded-md font-semibold text-xs text-green-700 uppercase tracking-widest shadow-sm hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                    >
                                        Activate Trunk
                                    </button>

                                    <!-- Use for Calls -->
                                    <Link
                                        :href="route('dialer')"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-blue-300 rounded-md font-semibold text-xs text-blue-700 uppercase tracking-widest shadow-sm hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        Use for Calls
                                    </Link>

                                    <!-- Delete -->
                                    <button
                                        @click="deleteTrunk"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 border border-red-300 rounded-md font-semibold text-xs text-red-700 uppercase tracking-widest shadow-sm hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    >
                                        Delete Trunk
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Stats</h3>
                                <div class="space-y-4">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">Phone Numbers</span>
                                        <span class="text-sm font-medium text-gray-900">{{ sipTrunk.phone_numbers.length }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">Total Calls</span>
                                        <span class="text-sm font-medium text-gray-900">{{ sipTrunk.calls ? sipTrunk.calls.length : 0 }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500">Days Active</span>
                                        <span class="text-sm font-medium text-gray-900">{{ getDaysActive() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Phone Number Assignment Modal -->
        <div v-if="showAssignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Assign Phone Number</h3>
                        <button
                            @click="showAssignModal = false"
                            class="text-gray-400 hover:text-gray-600"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div v-if="availablePhoneNumbers.length > 0" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Phone Number</label>
                            <select
                                v-model="selectedPhoneNumber"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">Choose a phone number</option>
                                <option v-for="phoneNumber in availablePhoneNumbers" :key="phoneNumber.id" :value="phoneNumber.id">
                                    {{ phoneNumber.phone_number }} ({{ phoneNumber.number_type || 'local' }})
                                </option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Assignment Type</label>
                            <select
                                v-model="assignmentType"
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="primary">Primary</option>
                                <option value="secondary">Secondary</option>
                                <option value="backup">Backup</option>
                            </select>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-4">
                            <button
                                @click="showAssignModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200"
                            >
                                Cancel
                            </button>
                            <button
                                @click="assignPhoneNumber"
                                :disabled="!selectedPhoneNumber || assigning"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 disabled:opacity-50"
                            >
                                {{ assigning ? 'Assigning...' : 'Assign' }}
                            </button>
                        </div>
                    </div>
                    
                    <div v-else class="text-center py-6">
                        <PhoneIcon class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                        <p class="text-gray-500 mb-4">No phone numbers available for assignment</p>
                        <Link
                            :href="route('phone-numbers.index')"
                            class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-md text-sm text-blue-700 bg-blue-50 hover:bg-blue-100"
                        >
                            Purchase Phone Numbers
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Notifications -->
        <Toast 
            v-if="toast.show" 
            :message="toast.message" 
            :type="toast.type" 
            :duration="3000"
        />
    </DashboardLayout>
</template>

<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { PhoneIcon } from '@heroicons/vue/24/outline'
import Toast from '@/Components/Toast.vue'

const props = defineProps({
    sipTrunk: Object,
    telnyxDetails: Object,
    availablePhoneNumbers: Array
})

const testing = ref(false)
const unassigning = ref(null)
const assigning = ref(false)
const showAssignModal = ref(false)
const selectedPhoneNumber = ref('')
const assignmentType = ref('primary')
const toast = ref({
    show: false,
    message: '',
    type: 'info'
})

const showToast = (message, type = 'info') => {
    toast.value = {
        show: true,
        message,
        type
    }
}

const formatDate = (dateString) => {
    if (!dateString) return 'N/A'
    return new Date(dateString).toLocaleDateString()
}

const formatDuration = (seconds) => {
    if (!seconds) return 'N/A'
    const minutes = Math.floor(seconds / 60)
    const remainingSeconds = seconds % 60
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`
}

const getDaysActive = () => {
    if (!props.sipTrunk.created_at) return 0
    const created = new Date(props.sipTrunk.created_at)
    const now = new Date()
    const diffTime = Math.abs(now - created)
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
    return diffDays
}

const assignPhoneNumber = async () => {
    if (!selectedPhoneNumber.value) return
    
    assigning.value = true
    try {
        const response = await fetch(route('sip-trunks.assign-number', props.sipTrunk.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                phone_number_id: selectedPhoneNumber.value,
                assignment_type: assignmentType.value
            })
        })
        
        const result = await response.json()
        
        if (result.success) {
            showToast('Phone number assigned successfully!', 'success')
            showAssignModal.value = false
            selectedPhoneNumber.value = ''
            assignmentType.value = 'primary'
            window.location.reload()
        } else {
            showToast('Failed to assign phone number: ' + result.message, 'error')
        }
    } catch (error) {
        showToast('Failed to assign phone number. Please try again.', 'error')
    } finally {
        assigning.value = false
    }
}

const testConnection = async () => {
    testing.value = true
    try {
        const response = await fetch(route('sip-trunks.test', props.sipTrunk.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        
        const result = await response.json()
        
        if (result.success) {
            showToast('Connection test successful!', 'success')
            window.location.reload()
        } else {
            showToast('Connection test failed: ' + result.message, 'error')
        }
    } catch (error) {
        showToast('Test failed. Please try again.', 'error')
    } finally {
        testing.value = false
    }
}

const activateTrunk = async () => {
    if (confirm('Are you sure you want to activate this SIP trunk?')) {
        try {
            await fetch(route('sip-trunks.activate', props.sipTrunk.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            showToast('SIP trunk activated successfully!', 'success')
            window.location.reload()
        } catch (error) {
            showToast('Failed to activate trunk. Please try again.', 'error')
        }
    }
}

const deactivateTrunk = async () => {
    if (confirm('Are you sure you want to deactivate this SIP trunk?')) {
        try {
            await fetch(route('sip-trunks.deactivate', props.sipTrunk.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            showToast('SIP trunk deactivated successfully!', 'success')
            window.location.reload()
        } catch (error) {
            showToast('Failed to deactivate trunk. Please try again.', 'error')
        }
    }
}

const deleteTrunk = async () => {
    if (confirm('Are you sure you want to delete this SIP trunk? This action cannot be undone.')) {
        try {
            await fetch(route('sip-trunks.destroy', props.sipTrunk.id), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            showToast('SIP trunk deleted successfully!', 'success')
            window.location.href = route('sip-trunks.index')
        } catch (error) {
            showToast('Failed to delete trunk. Please try again.', 'error')
        }
    }
}

const unassignPhoneNumber = async (phoneNumberId) => {
    if (confirm('Are you sure you want to unassign this phone number from the SIP trunk?')) {
        unassigning.value = phoneNumberId
        try {
            const response = await fetch(route('sip-trunks.unassign-number', [props.sipTrunk.id, phoneNumberId]), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            })
            
            const result = await response.json()
            
            if (result.success) {
                showToast('Phone number unassigned successfully!', 'success')
                window.location.reload()
            } else {
                showToast('Failed to unassign phone number: ' + result.message, 'error')
            }
        } catch (error) {
            showToast('Failed to unassign phone number. Please try again.', 'error')
        } finally {
            unassigning.value = null
        }
    }
}
</script>
