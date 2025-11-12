<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
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
import axios from 'axios'
import {
  RefreshCw,
  Filter,
  FileText,
  Search,
  Calendar,
  Loader2,
  Sparkles,
  Clock,
  CheckCircle,
  Hourglass,
  AlertTriangle,
  Eye
} from 'lucide-vue-next'

const props = defineProps({
  filters: {
    type: Object,
    default: () => ({})
  }
})

const initialStatusFilter =
  props.filters?.status && props.filters.status !== '' ? props.filters.status : 'all'

const loading = ref(false)
const transcriptions = ref([])
const searchQuery = ref('')
const filters = ref({
  status: initialStatusFilter,
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || ''
})
const viewingTranscription = ref(null)
const detailDialogOpen = ref(false)
const lastSyncedAt = ref(null)

const transcriptionsCount = computed(() => transcriptions.value.length)
const completedCount = computed(() =>
  transcriptions.value.filter((item) => item.status?.toLowerCase() === 'completed').length
)
const processingCount = computed(() =>
  transcriptions.value.filter((item) => item.status?.toLowerCase() === 'processing').length
)
const failedCount = computed(() =>
  transcriptions.value.filter((item) => item.status?.toLowerCase() === 'failed').length
)
const totalDurationMillis = computed(() =>
  transcriptions.value.reduce((sum, item) => sum + (item.duration_millis || 0), 0)
)
const totalDurationDisplay = computed(() => formatDurationExtended(totalDurationMillis.value))

const averageConfidence = computed(() => {
  const values = transcriptions.value
    .map((item) => item.confidence)
    .filter((value) => typeof value === 'number' && !Number.isNaN(value))

  if (!values.length) {
    return null
  }

  const total = values.reduce((sum, value) => sum + value, 0)
  return total / values.length
})

const averageConfidenceDisplay = computed(() => {
  if (averageConfidence.value == null) return '—'
  return `${Math.round(averageConfidence.value * 100)}%`
})

const displayedCount = computed(() => filteredTranscriptions.value.length)

const hasRemoteFilters = computed(() => {
  const statusActive = filters.value.status && filters.value.status !== 'all'
  return Boolean(statusActive || filters.value.date_from || filters.value.date_to)
})

const hasSearch = computed(() => Boolean(searchQuery.value.trim()))

const formattedLastSyncedAt = computed(() => {
  if (!lastSyncedAt.value) return 'Not synced yet'
  const date = new Date(lastSyncedAt.value)
  return `${date.toLocaleDateString()} at ${date.toLocaleTimeString([], {
    hour: '2-digit',
    minute: '2-digit'
  })}`
})

const filteredTranscriptions = computed(() => {
  const source = transcriptions.value
  if (!searchQuery.value) {
    return source
  }

  const query = searchQuery.value.toLowerCase()
  return source.filter((item) => {
    const searchableText = [
      item.from_number,
      item.to_number,
      item.call?.from_number,
      item.call?.to_number,
      item.transcription_text,
      item.recording_id,
      item.status
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    return searchableText.includes(query)
  })
})

onMounted(() => {
  refreshTranscriptions()
})

const refreshTranscriptions = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.status && filters.value.status !== 'all') {
      params.append('status', filters.value.status)
    }
    if (filters.value.date_from) params.append('date_from', filters.value.date_from)
    if (filters.value.date_to) params.append('date_to', filters.value.date_to)

    const response = await axios.get(`/api/transcriptions?${params.toString()}`)
    if (response.data.success) {
      const payload = Array.isArray(response.data.transcriptions)
        ? response.data.transcriptions
        : response.data.transcriptions?.data
      transcriptions.value = payload || []
      lastSyncedAt.value = new Date().toISOString()
    }
  } catch (error) {
    console.error('Error fetching transcriptions:', error)
    alert('Failed to load transcriptions. Please try again.')
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  refreshTranscriptions()
}

const clearFilters = () => {
  filters.value = {
    status: 'all',
    date_from: '',
    date_to: ''
  }
  searchQuery.value = ''
  refreshTranscriptions()
}

const viewTranscription = (transcription) => {
  viewingTranscription.value = transcription
  detailDialogOpen.value = true
}

const handleDialogOpenChange = (value) => {
  if (!value) {
    closeTranscription()
  } else {
    detailDialogOpen.value = value
  }
}

const closeTranscription = () => {
  detailDialogOpen.value = false
  viewingTranscription.value = null
}

const getTranscriptPreview = (text) => {
  if (!text) return 'No transcription available yet.'
  const trimmed = text.trim()
  if (trimmed.length <= 120) return trimmed
  return `${trimmed.slice(0, 117)}…`
}

const formatDate = (dateString) => {
  if (!dateString) return 'Unknown'
  const date = new Date(dateString)
  return date.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatTime = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' })
}

const formatDuration = (millis) => {
  if (!millis) return '0:00'
  const totalSeconds = Math.floor(millis / 1000)
  const minutes = Math.floor(totalSeconds / 60)
  const seconds = totalSeconds % 60
  return `${minutes}:${seconds.toString().padStart(2, '0')}`
}

const formatDurationExtended = (millis) => {
  if (!millis) return '0m 00s'
  const totalSeconds = Math.floor(millis / 1000)
  const hours = Math.floor(totalSeconds / 3600)
  const minutes = Math.floor((totalSeconds % 3600) / 60)
  const seconds = totalSeconds % 60
  if (hours) {
    return `${hours}h ${minutes.toString().padStart(2, '0')}m`
  }
  return `${minutes}m ${seconds.toString().padStart(2, '0')}s`
}

const getStatusVariant = (status) => {
  switch (status?.toLowerCase()) {
    case 'completed':
      return 'secondary'
    case 'processing':
      return 'outline'
    case 'failed':
      return 'destructive'
    default:
      return 'outline'
  }
}

const getStatusIcon = (status) => {
  switch (status?.toLowerCase()) {
    case 'completed':
      return CheckCircle
    case 'processing':
      return Hourglass
    case 'failed':
      return AlertTriangle
    default:
      return Clock
  }
}

const formatDirection = (transcription) => {
  const direction = transcription?.direction || transcription?.call?.direction
  if (!direction || direction === 'unknown') return 'Unknown'
  return direction.charAt(0).toUpperCase() + direction.slice(1)
}

const formatConfidence = (value) => {
  if (value == null || Number.isNaN(value)) {
    return '—'
  }
  return `${Math.round(value * 100)}%`
}

const confidenceVariant = (value) => {
  if (value == null || Number.isNaN(value)) return 'outline'
  if (value >= 0.9) return 'secondary'
  if (value >= 0.75) return 'outline'
  return 'destructive'
}

const shortRecordingId = (transcription) => {
  if (!transcription?.recording_id) return 'N/A'
  return `${transcription.recording_id.slice(0, 8)}…`
}
</script>

<template>
  <Head title="Transcription Library" />

  <DashboardLayout>
    <template #header>
      <div class="flex flex-col gap-2">
        <h1 class="text-3xl font-bold tracking-tight">Transcription Library</h1>
        <p class="text-muted-foreground">
          Search, review, and share AI-generated summaries of your recorded calls.
        </p>
      </div>
    </template>

    <div class="space-y-6 pb-12">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="space-y-1">
          <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Last synced</p>
          <p class="text-sm font-semibold text-foreground">{{ formattedLastSyncedAt }}</p>
          <p class="text-xs text-muted-foreground">
            Showing {{ displayedCount }} of {{ transcriptionsCount }} transcriptions
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <Button
            variant="outline"
            class="gap-2"
            :disabled="loading"
            @click="refreshTranscriptions"
          >
            <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
            <RefreshCw v-else class="h-4 w-4" />
            {{ loading ? 'Refreshing…' : 'Refresh' }}
          </Button>

          <Link
            :href="route('recordings.index')"
            class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition hover:bg-accent hover:text-accent-foreground"
          >
            <FileText class="h-4 w-4" />
            Recordings
          </Link>

          <Link
            :href="route('recordings.workspace')"
            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90"
          >
            <Sparkles class="h-4 w-4" />
            AI Workspace
          </Link>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Transcript Time</CardTitle>
            <Clock class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ totalDurationDisplay }}</div>
            <p class="text-xs text-muted-foreground">Across {{ transcriptionsCount }} transcripts</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Completed</CardTitle>
            <CheckCircle class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ completedCount }}</div>
            <p class="text-xs text-muted-foreground">Ready to review &amp; export</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Processing</CardTitle>
            <Hourglass class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ processingCount }}</div>
            <p class="text-xs text-muted-foreground">Queued &amp; pending</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Avg. Confidence</CardTitle>
            <Sparkles class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ averageConfidenceDisplay }}</div>
            <p class="text-xs text-muted-foreground">
              Failed {{ failedCount }} • Model health indicator
            </p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader class="pb-4">
          <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
              <CardTitle class="flex items-center gap-2 text-base">
                <Filter class="h-4 w-4" />
                Refine results
              </CardTitle>
              <CardDescription>
                Filter transcripts by status, capture window, or free-text search.
              </CardDescription>
            </div>
            <div class="flex items-center gap-2">
              <Badge
                v-if="hasRemoteFilters || hasSearch"
                variant="outline"
                class="uppercase tracking-wide"
              >
                Active
              </Badge>
              <Button
                v-if="hasRemoteFilters || hasSearch"
                variant="ghost"
                size="sm"
                class="h-9 gap-2 text-muted-foreground"
                @click="clearFilters"
              >
                Reset
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent>
          <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="md:col-span-1">
              <Label class="text-xs uppercase text-muted-foreground">Status</Label>
              <div class="mt-2">
                <Select v-model="filters.status" @update:modelValue="applyFilters">
                  <SelectTrigger>
                    <SelectValue placeholder="All statuses" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    <SelectItem value="completed">Completed</SelectItem>
                    <SelectItem value="processing">Processing</SelectItem>
                    <SelectItem value="failed">Failed</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div>
              <Label class="text-xs uppercase text-muted-foreground">Date from</Label>
              <div
                class="mt-2 flex items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-within:ring-2 focus-within:ring-ring"
              >
                <Calendar class="h-4 w-4 text-muted-foreground" />
                <input
                  v-model="filters.date_from"
                  type="date"
                  @change="applyFilters"
                  class="w-full bg-transparent outline-none"
                />
              </div>
            </div>

            <div>
              <Label class="text-xs uppercase text-muted-foreground">Date to</Label>
              <div
                class="mt-2 flex items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-within:ring-2 focus-within:ring-ring"
              >
                <Calendar class="h-4 w-4 text-muted-foreground" />
                <input
                  v-model="filters.date_to"
                  type="date"
                  @change="applyFilters"
                  class="w-full bg-transparent outline-none"
                />
              </div>
            </div>

            <div class="md:col-span-2">
              <Label class="text-xs uppercase text-muted-foreground">Search</Label>
              <div class="mt-2 relative">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                  v-model="searchQuery"
                  placeholder="Find by participant, recording ID, or transcript content"
                  class="pl-9"
                />
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader class="flex flex-wrap items-center justify-between gap-4 pb-3">
          <div>
            <CardTitle class="text-lg font-semibold">Transcript timeline</CardTitle>
            <CardDescription>
              Browse transcript previews, status indicators, and confidence signals.
            </CardDescription>
          </div>
          <Badge variant="outline" class="text-xs font-medium uppercase tracking-wide">
            {{ displayedCount }} visible
          </Badge>
        </CardHeader>
        <CardContent>
          <div
            v-if="loading"
            class="flex flex-col items-center justify-center gap-3 py-16 text-muted-foreground"
          >
            <Loader2 class="h-8 w-8 animate-spin" />
            <p class="text-sm font-medium">Refreshing transcriptions…</p>
          </div>

          <div
            v-else-if="!filteredTranscriptions.length"
            class="flex flex-col items-center justify-center gap-4 rounded-lg border border-dashed border-border bg-muted/40 px-8 py-16 text-center"
          >
            <FileText class="h-10 w-10 text-muted-foreground" />
            <div class="space-y-1">
              <h3 class="text-lg font-semibold">No transcriptions match your filters.</h3>
              <p class="text-sm text-muted-foreground">
                {{
                  hasRemoteFilters || hasSearch
                    ? 'Adjust the filters above to broaden the results.'
                    : 'Run or import recorded calls with transcription enabled to populate this view.'
                }}
              </p>
            </div>
            <Button variant="outline" class="gap-2" @click="refreshTranscriptions">
              <RefreshCw class="h-4 w-4" />
              Sync now
            </Button>
          </div>

          <div v-else class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
              <thead class="bg-muted/80 text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                  <th class="px-4 py-3 text-left font-medium">Captured</th>
                  <th class="px-4 py-3 text-left font-medium">Participants</th>
                  <th class="px-4 py-3 text-left font-medium">Transcript preview</th>
                  <th class="px-4 py-3 text-left font-medium">Duration</th>
                  <th class="px-4 py-3 text-left font-medium">Status</th>
                  <th class="px-4 py-3 text-left font-medium">Confidence</th>
                  <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="transcription in filteredTranscriptions"
                  :key="transcription.id"
                  class="border-t bg-background/80 transition hover:bg-muted/40"
                >
                  <td class="px-4 py-4 align-top text-sm text-muted-foreground">
                    <div class="font-medium text-foreground">
                      {{ formatDate(transcription.created_at) }}
                    </div>
                    <div class="text-xs">{{ formatTime(transcription.created_at) }}</div>
                  </td>
                  <td class="px-4 py-4 align-top text-sm">
                    <div v-if="transcription.from_number || transcription.call?.from_number" class="space-y-1">
                      <p class="font-medium text-foreground">
                        {{ transcription.from_number || transcription.call?.from_number || 'Unknown' }}
                        →
                        {{ transcription.to_number || transcription.call?.to_number || 'Unknown' }}
                      </p>
                      <p class="text-xs text-muted-foreground">
                        {{ formatDirection(transcription) }}
                      </p>
                    </div>
                    <div v-else class="space-y-1 text-muted-foreground">
                      <p class="font-medium text-foreground">Recording {{ shortRecordingId(transcription) }}</p>
                      <p class="text-xs">No participant metadata available</p>
                    </div>
                  </td>
                  <td class="px-4 py-4 align-top text-sm text-muted-foreground">
                    {{ getTranscriptPreview(transcription.transcription_text) }}
                  </td>
                  <td class="px-4 py-4 align-top text-sm font-medium text-foreground">
                    {{ formatDuration(transcription.duration_millis) }}
                  </td>
                  <td class="px-4 py-4 align-top">
                    <Badge :variant="getStatusVariant(transcription.status)" class="flex items-center gap-1 capitalize">
                      <component :is="getStatusIcon(transcription.status)" class="h-3.5 w-3.5" />
                      {{ transcription.status || 'Unknown' }}
                    </Badge>
                  </td>
                  <td class="px-4 py-4 align-top">
                    <Badge
                      :variant="confidenceVariant(transcription.confidence)"
                      class="text-xs font-medium"
                    >
                      {{ formatConfidence(transcription.confidence) }}
                    </Badge>
                  </td>
                  <td class="px-4 py-4 align-top">
                    <div class="flex justify-end">
                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-9 w-9 text-muted-foreground hover:text-foreground"
                        @click="viewTranscription(transcription)"
                        title="View full transcript"
                      >
                        <Eye class="h-4 w-4" />
                        <span class="sr-only">View transcript</span>
                      </Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <Dialog :open="detailDialogOpen" @update:open="handleDialogOpenChange">
        <DialogContent class="max-w-4xl">
          <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
              <Eye class="h-4 w-4" />
              Transcript detail
            </DialogTitle>
            <DialogDescription>
              Review the full transcript, call metadata, and confidence scores.
            </DialogDescription>
          </DialogHeader>

          <div v-if="!viewingTranscription" class="py-12 text-center text-sm text-muted-foreground">
            No transcription selected.
          </div>

          <div v-else class="space-y-6">
            <div class="grid gap-4 md:grid-cols-2 text-sm text-muted-foreground">
              <div class="space-y-1">
                <p class="text-xs font-medium uppercase text-muted-foreground/70">Participants</p>
                <p class="text-foreground font-semibold">
                  {{ viewingTranscription.from_number || viewingTranscription.call?.from_number || 'Unknown' }}
                  →
                  {{ viewingTranscription.to_number || viewingTranscription.call?.to_number || 'Unknown' }}
                </p>
                <p>Direction • {{ formatDirection(viewingTranscription) }}</p>
                <p>Captured • {{ formatDate(viewingTranscription.created_at) }} at {{ formatTime(viewingTranscription.created_at) }}</p>
              </div>
              <div class="space-y-1">
                <p class="text-xs font-medium uppercase text-muted-foreground/70">Transcript metadata</p>
                <div class="flex flex-wrap items-center gap-2">
                  <Badge :variant="getStatusVariant(viewingTranscription.status)" class="capitalize">
                    {{ viewingTranscription.status || 'Unknown' }}
                  </Badge>
                  <Badge
                    :variant="confidenceVariant(viewingTranscription.confidence)"
                    class="text-xs font-medium"
                  >
                    Confidence {{ formatConfidence(viewingTranscription.confidence) }}
                  </Badge>
                </div>
                <p>Duration • {{ formatDuration(viewingTranscription.duration_millis) }}</p>
                <p>Recording ID • {{ viewingTranscription.recording_id || 'Unavailable' }}</p>
              </div>
            </div>

            <Separator />

            <div class="rounded-lg border bg-muted/30 p-4 text-sm leading-relaxed text-muted-foreground">
              <p v-if="viewingTranscription.transcription_text" class="whitespace-pre-wrap">
                {{ viewingTranscription.transcription_text }}
              </p>
              <p v-else>No transcription text available.</p>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-muted-foreground">
              <span>Transcription ID • {{ viewingTranscription.id }}</span>
              <span v-if="viewingTranscription.updated_at">
                Updated {{ formatDate(viewingTranscription.updated_at) }}
              </span>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </DashboardLayout>
</template>

