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
import Select from '@/Components/ui/Select.vue'
import SelectTrigger from '@/Components/ui/SelectTrigger.vue'
import SelectContent from '@/Components/ui/SelectContent.vue'
import SelectItem from '@/Components/ui/SelectItem.vue'
import SelectValue from '@/Components/ui/SelectValue.vue'
import Dialog from '@/Components/ui/Dialog.vue'
import DialogContent from '@/Components/ui/DialogContent.vue'
import DialogHeader from '@/Components/ui/DialogHeader.vue'
import DialogTitle from '@/Components/ui/DialogTitle.vue'
import DialogDescription from '@/Components/ui/DialogDescription.vue'
import Separator from '@/Components/ui/Separator.vue'
import axios from 'axios'
import {
  RefreshCw,
  Sparkles,
  Filter,
  Mic,
  FileAudio,
  Clock,
  Play,
  Download,
  Trash2,
  Loader2,
  FileText,
  Search,
  Calendar,
  Headphones
} from 'lucide-vue-next'

const props = defineProps({
  recordings: {
    type: Object,
    default: () => ({ data: [] })
  },
  filters: {
    type: Object,
    default: () => ({})
  }
})

const loading = ref(false)
const recordings = ref(props.recordings?.data || [])
const searchQuery = ref('')
const initialStatusFilter =
  props.filters?.status && props.filters.status !== '' ? props.filters.status : 'all'

const filters = ref({
  status: initialStatusFilter,
  date_from: props.filters?.date_from || '',
  date_to: props.filters?.date_to || ''
})
const playingRecording = ref(null)
const playerDialogOpen = ref(false)
const viewingTranscription = ref(null)
const transcriptionDialogOpen = ref(false)
const currentTranscription = ref(null)
const transcriptionLoading = ref(false)
const transcriptionError = ref(null)
const lastSyncedAt = ref(null)

const recordingsCount = computed(() => recordings.value?.length || 0)
const completedCount = computed(() =>
  (recordings.value || []).filter((recording) => recording.status?.toLowerCase() === 'completed').length
)
const processingCount = computed(() =>
  (recordings.value || []).filter((recording) => recording.status?.toLowerCase() === 'processing').length
)
const deletedCount = computed(() =>
  (recordings.value || []).filter((recording) => recording.status?.toLowerCase() === 'deleted').length
)
const completionRate = computed(() => {
  if (!recordingsCount.value) return 0
  return Math.round((completedCount.value / recordingsCount.value) * 100)
})
const totalDurationMillis = computed(() =>
  (recordings.value || []).reduce((sum, recording) => sum + (recording.duration_millis || 0), 0)
)
const totalDurationDisplay = computed(() => formatDurationExtended(totalDurationMillis.value))

const hasRemoteFilters = computed(() => {
  const statusActive = filters.value.status && filters.value.status !== 'all'
  return Boolean(statusActive || filters.value.date_from || filters.value.date_to)
})
const hasSearch = computed(() => Boolean(searchQuery.value.trim()))

const filteredRecordings = computed(() => {
  const source = recordings.value || []

  if (!searchQuery.value) {
    return source
  }

  const query = searchQuery.value.toLowerCase()
  return source.filter((recording) => {
    const searchableText = [
      recording.from_number,
      recording.to_number,
      recording.call?.from_number,
      recording.call?.to_number,
      recording.call_session_id,
      recording.status
    ]
      .filter(Boolean)
      .join(' ')
      .toLowerCase()

    return searchableText.includes(query)
  })
})

const displayedCount = computed(() => filteredRecordings.value.length)

const formattedLastSyncedAt = computed(() => {
  if (!lastSyncedAt.value) return 'Not synced yet'
  const date = new Date(lastSyncedAt.value)
  return `${date.toLocaleDateString()} at ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`
})

onMounted(() => {
  refreshRecordings()
})

const refreshRecordings = async () => {
  loading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.status && filters.value.status !== 'all') {
      params.append('status', filters.value.status)
    }
    if (filters.value.date_from) params.append('date_from', filters.value.date_from)
    if (filters.value.date_to) params.append('date_to', filters.value.date_to)

    const response = await axios.get(`/api/recordings?${params.toString()}`)

    if (response.data.success) {
      const payload = Array.isArray(response.data.recordings)
        ? response.data.recordings
        : response.data.recordings?.data

      recordings.value = payload || []
      lastSyncedAt.value = new Date().toISOString()
    }
  } catch (error) {
    console.error('Error fetching recordings:', error)
    alert('Failed to load recordings from Telnyx. Please try again.')
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  refreshRecordings()
}

const clearFilters = () => {
  filters.value = {
    status: 'all',
    date_from: '',
    date_to: ''
  }
  searchQuery.value = ''
  refreshRecordings()
}

const playRecording = (recording) => {
  if (!getDownloadUrl(recording, 'mp3')) return
  playingRecording.value = recording
  playerDialogOpen.value = true
}

const handlePlayerOpenChange = (value) => {
  if (!value) {
    closePlayer()
  } else {
    playerDialogOpen.value = value
  }
}

const closePlayer = () => {
  playerDialogOpen.value = false
  playingRecording.value = null
}

const viewTranscription = async (recording) => {
  viewingTranscription.value = recording
  transcriptionDialogOpen.value = true
  currentTranscription.value = null
  transcriptionLoading.value = true
  transcriptionError.value = null

  try {
    const response = await axios.get(`/api/recordings/${recording.id}/transcription`)
    if (response.data.success) {
      currentTranscription.value = response.data.transcription
    } else {
      transcriptionError.value = response.data.error || 'Transcription not available.'
    }
  } catch (error) {
    console.error('Error fetching transcription:', error)
    if (error.response?.status === 404) {
      transcriptionError.value = 'No transcription available for this recording yet.'
    } else {
      transcriptionError.value = 'Failed to load transcription. Please retry.'
    }
  } finally {
    transcriptionLoading.value = false
  }
}

const handleTranscriptionOpenChange = (value) => {
  if (!value) {
    closeTranscription()
  } else {
    transcriptionDialogOpen.value = value
  }
}

const closeTranscription = () => {
  transcriptionDialogOpen.value = false
  viewingTranscription.value = null
  currentTranscription.value = null
  transcriptionError.value = null
}

const downloadRecording = async (recording, format) => {
  const url = getDownloadUrl(recording, format)
  if (!url) {
    alert(`Download URL for ${format.toUpperCase()} is not available.`)
    return
  }
  window.open(url, '_blank')
}

const deleteRecording = async (recording) => {
  if (
    !confirm(
      'Delete this recording permanently? This removes the media from Telnyx and cannot be undone.'
    )
  ) {
    return
  }

  try {
    const response = await axios.delete(`/api/recordings/${recording.id}`)
    if (response.data.success) {
      recordings.value = (recordings.value || []).filter((item) => item.id !== recording.id)
      alert('Recording deleted successfully.')
    } else {
      alert('Failed to delete recording.')
    }
  } catch (error) {
    console.error('Error deleting recording:', error)
    alert('Failed to delete recording. Please try again.')
  }
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
    case 'deleted':
      return 'destructive'
    default:
      return 'outline'
  }
}

const getDownloadUrl = (recording, format) => {
  if (!recording) return null
  if (recording.download_urls) {
    return format === 'wav' ? recording.download_urls.wav : recording.download_urls.mp3
  }
  return format === 'wav' ? recording.download_url_wav : recording.download_url_mp3
}

const formatDirection = (recording) => {
  const direction = recording?.direction || recording?.call?.direction
  if (!direction || direction === 'unknown') return 'Unknown'
  return direction.charAt(0).toUpperCase() + direction.slice(1)
}

const shortSessionId = (recording) => {
  if (!recording?.call_session_id) return 'N/A'
  return `${recording.call_session_id.slice(0, 8)}…`
}
</script>

<template>
  <Head title="Call Recordings" />

  <DashboardLayout>
    <template #header>
      <div class="flex flex-col gap-2">
        <h1 class="text-3xl font-bold tracking-tight">Call Recordings</h1>
        <p class="text-muted-foreground">
          Monitor captured conversations, replay key moments, and hand off transcripts with ease.
        </p>
      </div>
    </template>

    <div class="space-y-6 pb-12">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="space-y-1">
          <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Last synced</p>
          <p class="text-sm font-semibold text-foreground">{{ formattedLastSyncedAt }}</p>
          <p class="text-xs text-muted-foreground">
            Showing {{ displayedCount }} of {{ recordingsCount }} recordings
          </p>
        </div>

        <div class="flex flex-wrap gap-2">
          <Button
            variant="outline"
            class="gap-2"
            :disabled="loading"
            @click="refreshRecordings"
          >
            <Loader2 v-if="loading" class="h-4 w-4 animate-spin" />
            <RefreshCw v-else class="h-4 w-4" />
            {{ loading ? 'Refreshing…' : 'Refresh from Telnyx' }}
          </Button>

          <Link
            :href="route('transcriptions.index')"
            class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition hover:bg-accent hover:text-accent-foreground"
          >
            <FileText class="h-4 w-4" />
            Transcription Library
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
            <CardTitle class="text-sm font-medium">Total Recorded Time</CardTitle>
            <Clock class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ totalDurationDisplay }}</div>
            <p class="text-xs text-muted-foreground">Across {{ recordingsCount }} sessions</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Completed</CardTitle>
            <Headphones class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ completedCount }}</div>
            <p class="text-xs text-muted-foreground">Ready for playback &amp; export</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Processing</CardTitle>
            <Mic class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ processingCount }}</div>
            <p class="text-xs text-muted-foreground">Currently finalizing media</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Completion Rate</CardTitle>
            <FileAudio class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ completionRate }}%</div>
            <p class="text-xs text-muted-foreground">Deleted: {{ deletedCount }}</p>
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
              <CardDescription>Filter recordings by lifecycle status, capture window, or keyword.</CardDescription>
            </div>
            <div class="flex items-center gap-2">
              <Badge v-if="hasRemoteFilters || hasSearch" variant="outline" class="uppercase tracking-wide">
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
                    <SelectItem value="deleted">Deleted</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div>
              <Label class="text-xs uppercase text-muted-foreground">Date from</Label>
              <div class="mt-2 flex items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-within:ring-2 focus-within:ring-ring">
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
              <div class="mt-2 flex items-center gap-2 rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm focus-within:ring-2 focus-within:ring-ring">
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
                  placeholder="Find by caller, recipient, session ID, or status"
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
            <CardTitle class="text-lg font-semibold">Recording timeline</CardTitle>
            <CardDescription>
              Review captured calls, play audio, download files, and surface transcripts.
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
            <p class="text-sm font-medium">Refreshing recordings…</p>
          </div>

          <div
            v-else-if="!filteredRecordings.length"
            class="flex flex-col items-center justify-center gap-4 rounded-lg border border-dashed border-border bg-muted/40 px-8 py-16 text-center"
          >
            <Mic class="h-10 w-10 text-muted-foreground" />
            <div class="space-y-1">
              <h3 class="text-lg font-semibold">No recordings match your filters.</h3>
              <p class="text-sm text-muted-foreground">
                {{
                  hasRemoteFilters || hasSearch
                    ? 'Adjust the filters above to broaden the results.'
                    : 'Place or receive calls with recording enabled to populate this view.'
                }}
              </p>
            </div>
            <Button variant="outline" class="gap-2" @click="refreshRecordings">
              <RefreshCw class="h-4 w-4" />
              Sync now
            </Button>
          </div>

          <div v-else class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
              <thead class="bg-muted/80 text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                  <th class="px-4 py-3 text-left font-medium">Captured</th>
                  <th class="px-4 py-3 text-left font-medium">Call details</th>
                  <th class="px-4 py-3 text-left font-medium">Duration</th>
                  <th class="px-4 py-3 text-left font-medium">Status</th>
                  <th class="px-4 py-3 text-right font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="recording in filteredRecordings"
                  :key="recording.id"
                  class="border-t bg-background/80 transition hover:bg-muted/40"
                >
                  <td class="px-4 py-4 align-top text-sm text-muted-foreground">
                    <div class="font-medium text-foreground">
                      {{ formatDate(recording.created_at) }}
                    </div>
                    <div class="text-xs">{{ formatTime(recording.created_at) }}</div>
                  </td>
                  <td class="px-4 py-4 align-top text-sm">
                    <div v-if="recording.from_number || recording.call?.from_number" class="space-y-1">
                      <p class="font-medium text-foreground">
                        {{ recording.from_number || recording.call?.from_number || 'Unknown' }}
                        →
                        {{ recording.to_number || recording.call?.to_number || 'Unknown' }}
                      </p>
                      <p class="text-xs text-muted-foreground">
                        {{ formatDirection(recording) }}
                        <span class="px-2 text-muted-foreground/70">•</span>
                        {{ recording.channels || 'dual-channel' }}
                      </p>
                    </div>
                    <div v-else class="space-y-1 text-muted-foreground">
                      <p class="font-medium text-foreground">Session {{ shortSessionId(recording) }}</p>
                      <p class="text-xs">No call metadata available</p>
                    </div>
                  </td>
                  <td class="px-4 py-4 align-top text-sm font-medium text-foreground">
                    {{ formatDuration(recording.duration_millis) }}
                  </td>
                  <td class="px-4 py-4 align-top">
                    <Badge :variant="getStatusVariant(recording.status)" class="capitalize">
                      {{ recording.status || 'Unknown' }}
                    </Badge>
                  </td>
                  <td class="px-4 py-4 align-top">
                    <div class="flex justify-end gap-1">
                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-9 w-9 text-muted-foreground hover:text-foreground"
                        :disabled="!getDownloadUrl(recording, 'mp3')"
                        @click="playRecording(recording)"
                        title="Play recording"
                      >
                        <Play class="h-4 w-4" />
                        <span class="sr-only">Play</span>
                      </Button>

                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-9 w-9 text-muted-foreground hover:text-foreground"
                        @click="viewTranscription(recording)"
                        title="View transcription"
                      >
                        <FileText class="h-4 w-4" />
                        <span class="sr-only">View transcription</span>
                      </Button>

                      <Button
                        v-if="getDownloadUrl(recording, 'mp3')"
                        variant="ghost"
                        size="icon"
                        class="h-9 w-9 text-muted-foreground hover:text-foreground"
                        @click="downloadRecording(recording, 'mp3')"
                        title="Download MP3"
                      >
                        <Download class="h-4 w-4" />
                        <span class="sr-only">Download MP3</span>
                      </Button>

                      <Button
                        v-if="getDownloadUrl(recording, 'wav')"
                        variant="ghost"
                        size="icon"
                        class="h-9 w-9 text-muted-foreground hover:text-foreground"
                        @click="downloadRecording(recording, 'wav')"
                        title="Download WAV"
                      >
                        <Download class="h-4 w-4" />
                        <span class="sr-only">Download WAV</span>
                      </Button>

                      <Button
                        variant="ghost"
                        size="icon"
                        class="h-9 w-9 text-destructive hover:text-destructive"
                        @click="deleteRecording(recording)"
                        title="Delete recording"
                      >
                        <Trash2 class="h-4 w-4" />
                        <span class="sr-only">Delete</span>
                      </Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <Dialog :open="playerDialogOpen" @update:open="handlePlayerOpenChange">
        <DialogContent class="max-w-xl">
          <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
              <Play class="h-4 w-4" />
              Playback
            </DialogTitle>
            <DialogDescription>Listen to the selected recording without leaving the page.</DialogDescription>
          </DialogHeader>
          <div v-if="playingRecording" class="space-y-4">
            <div class="grid gap-2 text-sm text-muted-foreground">
              <div class="flex flex-wrap items-center gap-2 text-foreground">
                <span class="font-semibold">
                  {{ playingRecording.from_number || playingRecording.call?.from_number || 'Unknown' }}
                </span>
                <span class="text-muted-foreground">→</span>
                <span class="font-semibold">
                  {{ playingRecording.to_number || playingRecording.call?.to_number || 'Unknown' }}
                </span>
              </div>
              <div>Captured {{ formatDate(playingRecording.created_at) }} at {{ formatTime(playingRecording.created_at) }}</div>
              <div>Direction • {{ formatDirection(playingRecording) }}</div>
              <div>Duration • {{ formatDuration(playingRecording.duration_millis) }}</div>
            </div>
            <Separator />
            <audio
              v-if="getDownloadUrl(playingRecording, 'mp3')"
              :key="playingRecording.id"
              controls
              autoplay
              class="w-full rounded-md border border-input bg-background p-2"
              :src="getDownloadUrl(playingRecording, 'mp3')"
            />
          </div>
        </DialogContent>
      </Dialog>

      <Dialog :open="transcriptionDialogOpen" @update:open="handleTranscriptionOpenChange">
        <DialogContent class="max-w-3xl">
          <DialogHeader>
            <DialogTitle class="flex items-center gap-2">
              <FileText class="h-4 w-4" />
              Recording transcription
            </DialogTitle>
            <DialogDescription>
              Review the generated transcript, then copy or share highlights with your team.
            </DialogDescription>
          </DialogHeader>

          <div v-if="transcriptionLoading" class="flex items-center justify-center gap-3 py-12">
            <Loader2 class="h-5 w-5 animate-spin text-muted-foreground" />
            <span class="text-sm text-muted-foreground">Loading transcription…</span>
          </div>

          <div v-else-if="transcriptionError" class="rounded-md border border-destructive/40 bg-destructive/5 p-4 text-sm text-destructive">
            {{ transcriptionError }}
          </div>

          <div v-else-if="viewingTranscription" class="space-y-5">
            <div class="grid gap-3 text-sm text-muted-foreground">
              <div class="flex flex-wrap items-center gap-2 text-foreground">
                <span class="font-semibold">
                  {{ viewingTranscription.from_number || viewingTranscription.call?.from_number || 'Unknown' }}
                </span>
                <span class="text-muted-foreground">→</span>
                <span class="font-semibold">
                  {{ viewingTranscription.to_number || viewingTranscription.call?.to_number || 'Unknown' }}
                </span>
              </div>
              <div>Captured {{ formatDate(viewingTranscription.created_at) }} at {{ formatTime(viewingTranscription.created_at) }}</div>
              <div>
                Status:
                <Badge :variant="getStatusVariant(currentTranscription?.status)" class="ml-2 capitalize">
                  {{ currentTranscription?.status || 'Unknown' }}
                </Badge>
              </div>
              <div>
                Duration • {{ formatDuration(currentTranscription?.duration_millis || viewingTranscription.duration_millis) }}
              </div>
            </div>

            <Separator />

            <div class="rounded-lg border bg-muted/30 p-4 text-sm leading-relaxed text-muted-foreground">
              <template v-if="currentTranscription?.transcription_text">
                <p class="whitespace-pre-wrap">
                  {{ currentTranscription.transcription_text }}
                </p>
              </template>
              <template v-else>
                <p>No transcript has been generated for this recording yet.</p>
              </template>
            </div>

            <div class="flex items-center justify-between text-xs text-muted-foreground">
              <span>Session ID: {{ shortSessionId(viewingTranscription) }}</span>
              <span v-if="currentTranscription?.updated_at">
                Updated {{ formatDate(currentTranscription.updated_at) }}
              </span>
            </div>
          </div>
        </DialogContent>
      </Dialog>
    </div>
  </DashboardLayout>
</template>

