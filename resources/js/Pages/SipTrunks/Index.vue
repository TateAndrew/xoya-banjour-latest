<script setup>
import { ref, reactive, computed } from 'vue'
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
import Separator from '@/Components/ui/Separator.vue'
import Select from '@/Components/ui/Select.vue'
import SelectTrigger from '@/Components/ui/SelectTrigger.vue'
import SelectContent from '@/Components/ui/SelectContent.vue'
import SelectItem from '@/Components/ui/SelectItem.vue'
import SelectValue from '@/Components/ui/SelectValue.vue'
import Input from '@/Components/ui/Input.vue'
import {
  CloudCog,
  ShieldCheck,
  PhoneCall,
  Activity,
  AlertTriangle,
  Loader2,
  Settings,
  Phone,
  Power,
  PlusCircle,
  RefreshCw
} from 'lucide-vue-next'

const props = defineProps({
  sipTrunks: {
    type: Object,
    required: true
  }
})

const testing = ref(null)
const assignDialogOpen = ref(false)
const selectedTrunk = ref(null)
const availablePhoneNumbers = ref([])
const form = reactive({
  phone_number_id: '',
  assignment_type: 'primary'
})
const assigning = ref(false)

const trunks = computed(() => props.sipTrunks?.data || [])
const totalTrunks = computed(() => trunks.value.length)
const activeTrunks = computed(() =>
  trunks.value.filter((trunk) => (trunk.status || '').toLowerCase() === 'active').length
)
const pendingTrunks = computed(() =>
  trunks.value.filter((trunk) => (trunk.status || '').toLowerCase() === 'pending').length
)
const totalAssignedNumbers = computed(() =>
  trunks.value.reduce((sum, trunk) => sum + (trunk.phone_numbers?.length || 0), 0)
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

const formatAssignmentType = (value) => {
  switch ((value || '').toLowerCase()) {
    case 'primary':
      return 'Primary'
    case 'secondary':
      return 'Secondary'
    case 'backup':
      return 'Backup'
    default:
      return 'Unknown'
  }
}

const fetchAvailablePhoneNumbers = async () => {
  const response = await axios.get('/api/phone-numbers')
  const payload = response.data || []

  availablePhoneNumbers.value = payload.filter((number) => {
    const assigned = Array.isArray(number.sip_trunks) && number.sip_trunks.length > 0
    return !assigned
  })
}

const openAssignDialog = async (trunk) => {
  selectedTrunk.value = trunk
  form.phone_number_id = ''
  form.assignment_type = 'primary'

  try {
    await fetchAvailablePhoneNumbers()
    assignDialogOpen.value = true
  } catch (error) {
    console.error('Failed to load phone numbers', error)
    alert('Unable to load phone numbers. Please try again later.')
  }
}

const closeAssignDialog = () => {
  assignDialogOpen.value = false
  selectedTrunk.value = null
  form.phone_number_id = ''
  form.assignment_type = 'primary'
  availablePhoneNumbers.value = []
}

const assignPhoneNumber = async () => {
  if (!selectedTrunk.value || !form.phone_number_id) {
    alert('Select a phone number to assign.')
    return
  }

  assigning.value = true

  try {
    const response = await axios.post(
      `/sip-trunks/${selectedTrunk.value.id}/assign-number`,
      {
        phone_number_id: form.phone_number_id,
        assignment_type: form.assignment_type
      }
    )

    if (response.data?.success) {
      alert('Phone number assigned successfully.')
      closeAssignDialog()
      window.location.reload()
    } else {
      throw new Error(response.data?.message || 'Assignment failed.')
    }
  } catch (error) {
    console.error('Assignment error', error)
    alert(error.message || 'Failed to assign phone number.')
  } finally {
    assigning.value = false
  }
}

const testConnection = async (trunk) => {
  testing.value = trunk.id
  try {
    const response = await axios.post(`/sip-trunks/${trunk.id}/test`)
    if (response.data?.success) {
      alert('Connection test succeeded.')
    } else {
      alert('Connection test failed: ' + (response.data?.message || 'Unknown error'))
    }
  } catch (error) {
    console.error('Test connection error', error)
    alert('Connection test failed. Please retry.')
  } finally {
    testing.value = null
  }
}

const activateTrunk = async (trunk) => {
  if (!confirm('Activate this SIP trunk?')) return

  try {
    await axios.post(`/sip-trunks/${trunk.id}/activate`)
    alert('SIP trunk activated.')
    window.location.reload()
  } catch (error) {
    console.error('Activate error', error)
    alert('Activation failed. Please try again.')
  }
}

const deactivateTrunk = async (trunk) => {
  if (!confirm('Deactivate this SIP trunk?')) return

  try {
    await axios.post(`/sip-trunks/${trunk.id}/deactivate`)
    alert('SIP trunk deactivated.')
    window.location.reload()
  } catch (error) {
    console.error('Deactivate error', error)
    alert('Deactivation failed. Please try again.')
  }
}

const deleteTrunk = async (trunk) => {
  if (!confirm('Delete this SIP trunk? This action cannot be undone.')) return

  try {
    await axios.delete(`/sip-trunks/${trunk.id}`)
    alert('SIP trunk deleted.')
    window.location.reload()
  } catch (error) {
    console.error('Delete error', error)
    alert('Deletion failed. Please try again.')
  }
}
</script>

<template>
  <DashboardLayout>
    <Head title="SIP Trunks" />

    <template #header>
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">SIP Trunks</h1>
          <p class="text-sm text-muted-foreground">
            Manage carrier connections, monitor status, and assign numbers to routes.
          </p>
        </div>
        <Link
          :href="route('sip-trunks.create')"
          class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90"
        >
          <PlusCircle class="h-4 w-4" />
          New SIP Trunk
        </Link>
      </div>
    </template>

    <div class="space-y-6 pb-12">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total trunks</CardTitle>
            <CloudCog class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ totalTrunks }}</div>
            <p class="text-xs text-muted-foreground">Across all providers and routing policies</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active</CardTitle>
            <ShieldCheck class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ activeTrunks }}</div>
            <p class="text-xs text-muted-foreground">Healthy and routing traffic</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Pending</CardTitle>
            <Activity class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ pendingTrunks }}</div>
            <p class="text-xs text-muted-foreground">Awaiting activation or testing</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Assigned numbers</CardTitle>
            <PhoneCall class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ totalAssignedNumbers }}</div>
            <p class="text-xs text-muted-foreground">Mapped phone numbers across trunks</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <CardTitle class="text-lg font-semibold">SIP trunk inventory</CardTitle>
            <CardDescription>
              Trigger tests, change lifecycle state, or assign numbers to a trunk.
            </CardDescription>
          </div>
          <Button variant="outline" class="gap-2" @click="window.location.reload()">
            <RefreshCw class="h-4 w-4" />
            Refresh
          </Button>
        </CardHeader>
        <CardContent>
          <div
            v-if="!trunks.length"
            class="flex flex-col items-center justify-center gap-4 rounded-lg border border-dashed border-border bg-muted/40 px-8 py-16 text-center"
          >
            <CloudCog class="h-10 w-10 text-muted-foreground" />
            <div class="space-y-1">
              <h3 class="text-lg font-semibold">No SIP trunks yet.</h3>
              <p class="text-sm text-muted-foreground">
                Create a trunk to start routing calls through your carrier connections.
              </p>
            </div>
            <Link
              :href="route('sip-trunks.create')"
              class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90"
            >
              <PlusCircle class="h-4 w-4" />
              Create SIP Trunk
            </Link>
          </div>

          <div v-else class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
              <thead class="bg-muted/80 text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                  <th class="px-4 py-3 text-left font-medium">Trunk</th>
                  <th class="px-4 py-3 text-left font-medium">Status</th>
                  <th class="px-4 py-3 text-left font-medium">Type</th>
                  <th class="px-4 py-3 text-left font-medium">Phone numbers</th>
                  <th class="px-4 py-3 text-left font-medium">Created</th>
                  <th class="px-4 py-3 text-left font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="trunk in trunks"
                  :key="trunk.id"
                  class="border-t bg-background/80 transition hover:bg-muted/40"
                >
                  <td class="px-4 py-4 align-top">
                    <p class="font-medium text-foreground">{{ trunk.name }}</p>
                    <p class="text-xs text-muted-foreground">
                      Telnyx ID • {{ trunk.telnyx_connection_id || 'N/A' }}
                    </p>
                  </td>
                  <td class="px-4 py-4 align-top">
                    <Badge :variant="statusVariant(trunk.status)" class="capitalize">
                      {{ trunk.status || 'unknown' }}
                    </Badge>
                  </td>
                  <td class="px-4 py-4 align-top text-sm text-muted-foreground">
                    {{ trunk.connection_type || 'N/A' }}
                  </td>
                  <td class="px-4 py-4 align-top text-sm text-muted-foreground">
                    {{ trunk.phone_numbers?.length || 0 }} assigned
                  </td>
                  <td class="px-4 py-4 align-top text-sm text-muted-foreground">
                    {{ formatDate(trunk.created_at) }}
                  </td>
                  <td class="px-4 py-4 align-top">
                    <div class="flex flex-wrap gap-2">
                      <Button
                        variant="outline"
                        size="sm"
                        class="gap-2"
                        :disabled="testing === trunk.id"
                        @click="testConnection(trunk)"
                      >
                        <Loader2 v-if="testing === trunk.id" class="h-3.5 w-3.5 animate-spin" />
                        <Activity v-else class="h-3.5 w-3.5" />
                        {{ testing === trunk.id ? 'Testing…' : 'Test' }}
                      </Button>

                      <Button
                        v-if="(trunk.status || '').toLowerCase() === 'inactive'"
                        variant="outline"
                        size="sm"
                        class="gap-2 text-green-700"
                        @click="activateTrunk(trunk)"
                      >
                        <Power class="h-3.5 w-3.5" />
                        Activate
                      </Button>

                      <Button
                        v-if="(trunk.status || '').toLowerCase() === 'active'"
                        variant="outline"
                        size="sm"
                        class="gap-2 text-amber-700"
                        @click="deactivateTrunk(trunk)"
                      >
                        <Power class="h-3.5 w-3.5" />
                        Deactivate
                      </Button>

                      <Link
                        :href="route('sip-trunks.show', trunk.id)"
                        class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium shadow-sm transition hover:bg-accent hover:text-accent-foreground"
                      >
                        <Settings class="h-3.5 w-3.5" />
                        View
                      </Link>

                      <Link
                        :href="route('sip-trunks.edit', trunk.id)"
                        class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium shadow-sm transition hover:bg-accent hover:text-accent-foreground"
                      >
                        <CloudCog class="h-3.5 w-3.5" />
                        Edit
                      </Link>

                      <Button
                        variant="destructive"
                        size="sm"
                        class="gap-2"
                        @click="deleteTrunk(trunk)"
                      >
                        <AlertTriangle class="h-3.5 w-3.5" />
                        Delete
                      </Button>

                      <Button
                        variant="outline"
                        size="sm"
                        class="gap-2 text-green-700"
                        @click="openAssignDialog(trunk)"
                      >
                        <Phone class="h-3.5 w-3.5" />
                        Assign number
                      </Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="props.sipTrunks.links && props.sipTrunks.links.length > 3"
            class="border-t px-4 py-3"
          >
            <div class="flex justify-end gap-2">
              <Link
                v-for="(link, index) in props.sipTrunks.links"
                :key="index"
                :href="link.url || '#'"
                class="rounded-md border border-input px-3 py-1 text-xs font-medium transition hover:bg-accent hover:text-accent-foreground"
                :class="{ 'bg-primary text-primary-foreground border-primary': link.active }"
                v-html="link.label"
              />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <Dialog :open="assignDialogOpen" @update:open="assignDialogOpen = $event">
      <DialogContent class="max-w-lg">
        <DialogHeader>
          <DialogTitle>Assign phone number</DialogTitle>
          <DialogDescription>
            Select a phone number to associate with
            <strong>{{ selectedTrunk?.name }}</strong>.
          </DialogDescription>
        </DialogHeader>

        <div class="space-y-4">
          <div>
            <label class="text-xs font-medium uppercase text-muted-foreground">Phone number</label>
            <Select v-model="form.phone_number_id">
              <SelectTrigger class="mt-2">
                <SelectValue placeholder="Select phone number" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem
                  v-for="number in availablePhoneNumbers"
                  :key="number.id"
                  :value="number.id"
                >
                  {{ number.formatted_number || number.phone_number }}
                </SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div>
            <label class="text-xs font-medium uppercase text-muted-foreground">Assignment type</label>
            <Select v-model="form.assignment_type">
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
