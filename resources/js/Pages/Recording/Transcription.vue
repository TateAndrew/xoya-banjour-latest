<script setup>
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { Head } from '@inertiajs/vue3'

import DashboardLayout from '@/Layouts/DashboardLayout.vue'

import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardDescription from '@/Components/ui/CardDescription.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import Input from '@/Components/ui/Input.vue'
import Label from '@/Components/ui/Label.vue'
import Separator from '@/Components/ui/Separator.vue'
import Progress from '@/Components/ui/Progress.vue'
import Tabs from '@/Components/ui/Tabs.vue'
import TabsList from '@/Components/ui/TabsList.vue'
import TabsTrigger from '@/Components/ui/TabsTrigger.vue'
import TabsContent from '@/Components/ui/TabsContent.vue'
import Select from '@/Components/ui/Select.vue'
import SelectTrigger from '@/Components/ui/SelectTrigger.vue'
import SelectContent from '@/Components/ui/SelectContent.vue'
import SelectItem from '@/Components/ui/SelectItem.vue'
import SelectValue from '@/Components/ui/SelectValue.vue'

import {
  Mic,
  Square,
  RotateCcw,
  Upload,
  FileAudio,
  Sparkles,
  Clock,
  CheckCircle,
  AlertCircle,
  Wand2
} from 'lucide-vue-next'

const isRecording = ref(false)
const supportsRecording = ref(false)
const mediaRecorder = ref(null)
const activeStream = ref(null)
const recordedChunks = ref([])
const audioUrl = ref('')
const audioPlayer = ref(null)
const recordingDuration = ref(0)
const lastRecordingDuration = ref(0)
const timerInterval = ref(null)

const transcriptionStatus = ref('idle')
const transcriptionText = ref('Press record to start capturing audio or upload an existing file.')
const transcriptionSummary = ref('')
const transcriptionKeywords = ref([])
const confidenceScore = ref(null)
const isTranscribing = ref(false)

const uploadedFile = ref(null)
const uploadErrors = ref('')
const uploadProgress = ref(0)
const fileInput = ref(null)

const lastTranscribedAt = ref(null)
const selectedModel = ref('whisper-large')
const activeTab = ref('transcript')

const modelOptions = [
  {
    value: 'whisper-large',
    label: 'Whisper Large',
    description: 'Balanced accuracy and latency. Recommended for most cases.'
  },
  {
    value: 'whisper-medium',
    label: 'Whisper Medium',
    description: 'Faster with slightly reduced accuracy. Great for live previews.'
  },
  {
    value: 'whisper-large-v3',
    label: 'Whisper Large v3',
    description: 'Highest accuracy for challenging environments.'
  }
]

const historyItems = ref([
  {
    id: 1,
    title: 'Customer Support Call',
    duration: 428,
    createdAt: '2025-11-09T10:12:00Z',
    model: 'whisper-large',
    status: 'completed',
    confidence: 0.92
  },
  {
    id: 2,
    title: 'Product Feedback Session',
    duration: 312,
    createdAt: '2025-11-08T18:24:00Z',
    model: 'whisper-large-v3',
    status: 'completed',
    confidence: 0.88
  },
  {
    id: 3,
    title: 'Voice Memo',
    duration: 96,
    createdAt: '2025-11-07T07:45:00Z',
    model: 'whisper-medium',
    status: 'processing',
    confidence: null
  }
])

const formattedDuration = computed(() => {
  const minutes = Math.floor(recordingDuration.value / 60)
  const seconds = recordingDuration.value % 60
  return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
})

const formattedLastTranscribed = computed(() => {
  if (!lastTranscribedAt.value) return 'Not available yet'
  return new Date(lastTranscribedAt.value).toLocaleString()
})

const hasPreview = computed(() => !!audioUrl.value)

const activeModelMeta = computed(() => {
  return modelOptions.find((option) => option.value === selectedModel.value) || modelOptions[0]
})

const confidenceBadgeVariant = computed(() => {
  if (confidenceScore.value == null) return 'secondary'
  if (confidenceScore.value >= 0.9) return 'secondary'
  if (confidenceScore.value >= 0.75) return 'outline'
  return 'destructive'
})

const transcriptionStatusLabel = computed(() => {
  switch (transcriptionStatus.value) {
    case 'recording':
      return 'Recording in progress'
    case 'processing':
      return 'Processing transcription'
    case 'completed':
      return 'Transcription ready'
    case 'error':
      return 'Transcription failed'
    default:
      return 'Awaiting audio input'
  }
})

const transcriptionStatusIcon = computed(() => {
  switch (transcriptionStatus.value) {
    case 'completed':
      return CheckCircle
    case 'error':
      return AlertCircle
    case 'processing':
      return Clock
    case 'recording':
      return Mic
    default:
      return FileAudio
  }
})

const stopTimer = () => {
  clearInterval(timerInterval.value)
  timerInterval.value = null
}

const resetTimer = () => {
  stopTimer()
  recordingDuration.value = 0
}

const stopActiveStream = () => {
  if (activeStream.value) {
    activeStream.value.getTracks().forEach((track) => track.stop())
    activeStream.value = null
  }
}

const revokeAudioUrl = () => {
  if (audioUrl.value) {
    URL.revokeObjectURL(audioUrl.value)
    audioUrl.value = ''
  }
}

const startRecording = async () => {
  if (isRecording.value || !supportsRecording.value) return

  uploadErrors.value = ''
  uploadedFile.value = null
  uploadProgress.value = 0
  transcriptionSummary.value = ''
  transcriptionKeywords.value = []
  confidenceScore.value = null

  try {
    const stream = await navigator.mediaDevices.getUserMedia({ audio: true })
    activeStream.value = stream
    recordedChunks.value = []

    const recorder = new MediaRecorder(stream)
    mediaRecorder.value = recorder

    recorder.ondataavailable = (event) => {
      if (event.data.size > 0) {
        recordedChunks.value.push(event.data)
      }
    }

    recorder.onstop = () => {
      const blob = new Blob(recordedChunks.value, { type: 'audio/webm' })
      revokeAudioUrl()
      audioUrl.value = URL.createObjectURL(blob)
      transcriptionStatus.value = 'idle'
      lastRecordingDuration.value = recordingDuration.value
    }

    recorder.start()
    isRecording.value = true
    transcriptionStatus.value = 'recording'

    resetTimer()
    timerInterval.value = setInterval(() => {
      recordingDuration.value += 1
    }, 1000)
  } catch (error) {
    uploadErrors.value = 'Unable to access microphone. Please check permissions.'
    stopActiveStream()
    mediaRecorder.value = null
    isRecording.value = false
  }
}

const stopRecording = () => {
  if (!isRecording.value) return

  mediaRecorder.value?.stop()
  stopActiveStream()
  stopTimer()
  isRecording.value = false

  transcriptionStatus.value = 'idle'
}

const resetRecording = () => {
  if (isRecording.value) {
    stopRecording()
  }
  recordedChunks.value = []
  uploadedFile.value = null
  uploadErrors.value = ''
  uploadProgress.value = 0
  lastRecordingDuration.value = 0
  stopActiveStream()
  resetTimer()
  revokeAudioUrl()
  transcriptionStatus.value = 'idle'
  transcriptionText.value = 'Press record to start capturing audio or upload an existing file.'
  transcriptionSummary.value = ''
  transcriptionKeywords.value = []
  confidenceScore.value = null
  lastTranscribedAt.value = null
}

const triggerFilePicker = () => {
  fileInput.value?.click()
}

const handleFileUpload = (event) => {
  const file = event.target.files?.[0]
  if (!file) return

  if (!file.type.startsWith('audio/')) {
    uploadErrors.value = 'Only audio files are supported.'
    return
  }

  uploadErrors.value = ''
  uploadedFile.value = file
  recordedChunks.value = []

  uploadProgress.value = 10
  setTimeout(() => {
    uploadProgress.value = 60
  }, 300)
  setTimeout(() => {
    uploadProgress.value = 100
  }, 600)

  revokeAudioUrl()
  audioUrl.value = URL.createObjectURL(file)
  transcriptionStatus.value = 'idle'
}

const buildAudioBlob = () => {
  if (recordedChunks.value.length > 0) {
    return new Blob(recordedChunks.value, { type: 'audio/webm' })
  }
  if (uploadedFile.value) {
    return uploadedFile.value
  }
  return null
}

const fakeKeywordExtraction = (transcript) => {
  if (!transcript) return []
  const words = transcript
    .toLowerCase()
    .replace(/[^a-z0-9\s]/g, '')
    .split(/\s+/)
    .filter(Boolean)

  const frequency = words.reduce((acc, word) => {
    acc[word] = (acc[word] || 0) + 1
    return acc
  }, {})

  return Object.entries(frequency)
    .sort((a, b) => b[1] - a[1])
    .slice(0, 5)
    .map(([word, count]) => `${word} (${count})`)
}

const fakeSummary = (transcript) => {
  if (!transcript) return ''
  if (transcript.length < 120) {
    return 'Recording is brief. Consider adding more detail for a richer summary.'
  }
  return 'Summary: Key discussion points were captured, highlighting customer intent and follow-up actions.'
}

const transcribeRecording = async () => {
  if (isTranscribing.value) return

  const audioBlob = buildAudioBlob()
  if (!audioBlob) {
    uploadErrors.value = 'Record or upload audio before requesting transcription.'
    return
  }

  isTranscribing.value = true
  transcriptionStatus.value = 'processing'
  uploadErrors.value = ''

  // Simulate upload/transcription workflow
  await new Promise((resolve) => setTimeout(resolve, 900))

  try {
    // Placeholder: replace with API call
    const mockTranscript =
      'Thank you for joining the session today. We walked through the onboarding flow and discussed upcoming milestones.'

    transcriptionText.value = mockTranscript
    transcriptionSummary.value = fakeSummary(mockTranscript)
    transcriptionKeywords.value = fakeKeywordExtraction(mockTranscript)
    confidenceScore.value = 0.91
    transcriptionStatus.value = 'completed'
    lastTranscribedAt.value = new Date().toISOString()

    const durationForHistory =
      lastRecordingDuration.value || recordingDuration.value || 180

    historyItems.value = [
      {
        id: Date.now(),
        title: uploadedFile.value?.name || 'Live Recording',
        duration: durationForHistory,
        createdAt: new Date().toISOString(),
        model: selectedModel.value,
        status: 'completed',
        confidence: confidenceScore.value
      },
      ...historyItems.value.slice(0, 4)
    ]
  } catch (error) {
    uploadErrors.value = 'Unable to transcribe audio at this time.'
    transcriptionStatus.value = 'error'
  } finally {
    isTranscribing.value = false
  }
}

const watchAudioUrlCleanup = watch(
  audioUrl,
  (newValue, oldValue) => {
    if (oldValue && oldValue !== newValue) {
      URL.revokeObjectURL(oldValue)
    }
  },
  { flush: 'post' }
)

onMounted(() => {
  supportsRecording.value =
    typeof window !== 'undefined' &&
    typeof navigator !== 'undefined' &&
    !!navigator.mediaDevices?.getUserMedia &&
    typeof MediaRecorder !== 'undefined'
})

onBeforeUnmount(() => {
  stopTimer()
  stopActiveStream()
  watchAudioUrlCleanup()
  revokeAudioUrl()
})
</script>

<template>
  <Head title="Recording & Transcription" />

  <DashboardLayout>
    <template #header>
      <div class="flex flex-col gap-2">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Recording & Transcription</h1>
          <p class="text-muted-foreground">
            Capture high-quality audio, generate transcripts, and keep your conversations organized.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Badge variant="secondary" class="gap-1">
            <Sparkles :size="14" />
            AI-Assisted Summaries
          </Badge>
          <Badge variant="outline" class="gap-1">
            <FileAudio :size="14" />
            Multi-format Uploads
          </Badge>
        </div>
      </div>
    </template>

    <div class="grid xl:grid-cols-[2fr_3fr] gap-6 pb-10">
      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Live Recorder</CardTitle>
            <CardDescription>
              Capture crystal-clear audio directly in your browser. Works best with a dedicated microphone.
            </CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <div
              class="rounded-2xl border bg-muted/40 p-6 flex flex-col gap-6 md:flex-row md:items-center md:justify-between"
            >
              <div class="space-y-2">
                <div class="flex items-center gap-2">
                  <Badge :variant="confidenceBadgeVariant">
                    {{ transcriptionStatusLabel }}
                  </Badge>
                  <component :is="transcriptionStatusIcon" :size="18" class="text-muted-foreground" />
                </div>
                <h2 class="text-4xl font-mono font-semibold tracking-wider">
                  {{ formattedDuration }}
                </h2>
                <p class="text-sm text-muted-foreground">
                  {{ supportsRecording ? 'Microphone ready' : 'Browser capture not supported in this environment.' }}
                </p>
              </div>

              <div class="flex items-center gap-3">
                <Button
                  variant="outline"
                  size="icon"
                  class="h-12 w-12"
                  @click="resetRecording"
                  :disabled="isRecording || (!hasPreview && !uploadedFile)"
                >
                  <RotateCcw :size="18" />
                </Button>
                <Button
                  v-if="!isRecording"
                  class="h-12 px-6 gap-2"
                  @click="startRecording"
                  :disabled="!supportsRecording"
                >
                  <Mic :size="18" />
                  Start Recording
                </Button>
                <Button
                  v-else
                  variant="destructive"
                  class="h-12 px-6 gap-2"
                  @click="stopRecording"
                >
                  <Square :size="18" />
                  Stop &amp; Preview
                </Button>
              </div>
            </div>

            <div class="space-y-3">
              <Label>Preview</Label>
              <div
                class="relative rounded-xl border bg-background p-6 flex flex-col gap-4"
                :class="{ 'opacity-60': !hasPreview }"
              >
                <div class="flex items-center justify-between">
                  <p class="font-medium">Waveform Preview</p>
                  <Badge variant="outline" class="gap-1">
                    <Clock :size="14" />
                    Real-time
                  </Badge>
                </div>
                <div
                  class="h-24 rounded-lg bg-gradient-to-r from-primary/10 via-primary/30 to-primary/10 flex items-center justify-center text-sm text-muted-foreground"
                >
                  <span v-if="hasPreview">Waveform visualization ready</span>
                  <span v-else>Recording or upload to view waveform</span>
                </div>
                <audio
                  v-if="hasPreview"
                  ref="audioPlayer"
                  :src="audioUrl"
                  controls
                  class="w-full"
                />
              </div>
            </div>

            <Separator />

            <div class="grid gap-4 sm:grid-cols-2">
              <div class="rounded-lg border bg-muted/30 p-4">
                <p class="text-sm font-medium">Recording Tips</p>
                <ul class="mt-3 space-y-2 text-sm text-muted-foreground">
                  <li>• Use a quiet room whenever possible.</li>
                  <li>• Keep a consistent distance from the microphone.</li>
                  <li>• Pause briefly between sections for better segmentation.</li>
                </ul>
              </div>
              <div class="rounded-lg border bg-muted/30 p-4">
                <p class="text-sm font-medium">Model Settings</p>
                <p class="mt-2 text-sm text-muted-foreground">
                  {{ activeModelMeta.description }}
                </p>
                <label class="mt-3 block text-xs font-medium text-muted-foreground">Transcription Model</label>
                <div class="mt-1">
                  <Select v-model="selectedModel">
                    <SelectTrigger>
                      <SelectValue placeholder="Select model" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem v-for="option in modelOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                      </SelectItem>
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Upload Existing Audio</CardTitle>
            <CardDescription>Drag-and-drop or select files to transcribe without recording live.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <div
              class="flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed bg-muted/20 p-8 text-center transition-colors hover:border-primary/70"
              @click="triggerFilePicker"
            >
              <Upload :size="32" class="text-primary" />
              <div>
                <p class="text-sm font-medium">Drop audio here or click to browse</p>
                <p class="text-xs text-muted-foreground mt-1">Supports MP3, WAV, M4A, and WebM up to 2 hours.</p>
              </div>
              <input
                ref="fileInput"
                type="file"
                accept="audio/*"
                class="hidden"
                @change="handleFileUpload"
              />
              <Button variant="outline" class="gap-2">
                <FileAudio :size="16" />
                Choose Audio File
              </Button>
            </div>

            <div v-if="uploadedFile" class="space-y-3 rounded-xl border bg-muted/20 p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium">{{ uploadedFile.name }}</p>
                  <p class="text-xs text-muted-foreground">
                    {{ (uploadedFile.size / 1024 / 1024).toFixed(2) }} MB • {{ uploadedFile.type || 'Unknown format' }}
                  </p>
                </div>
                <Badge variant="secondary" class="gap-1">
                  <Upload :size="14" />
                  Ready
                </Badge>
              </div>
              <Progress :model-value="uploadProgress" class="h-2" />
            </div>

            <p v-if="uploadErrors" class="text-sm text-destructive">
              {{ uploadErrors }}
            </p>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
              <div class="flex items-center gap-2 text-xs text-muted-foreground">
                <Wand2 :size="14" />
                AI-enhanced cleanup runs automatically for noisy recordings.
              </div>
              <Button
                class="gap-2"
                :disabled="isTranscribing || (!uploadedFile && recordedChunks.length === 0 && !hasPreview)"
                @click="transcribeRecording"
              >
                <Sparkles :size="18" />
                {{ isTranscribing ? 'Transcribing…' : 'Generate Transcript' }}
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <div class="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle>Transcription Workspace</CardTitle>
            <CardDescription>Review transcripts, extract insights, and share outcomes in one place.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-6">
            <Tabs v-model="activeTab" class="w-full">
              <TabsList>
                <TabsTrigger value="transcript">Transcript</TabsTrigger>
                <TabsTrigger value="summary">Summary</TabsTrigger>
                <TabsTrigger value="keywords">Keywords</TabsTrigger>
              </TabsList>

              <TabsContent value="transcript" class="space-y-3 focus:outline-none">
                <Label class="text-sm font-medium">Full Transcript</Label>
                <textarea
                  :value="transcriptionText"
                  class="min-h-[220px] w-full rounded-xl border border-input bg-background px-4 py-3 text-sm leading-relaxed shadow-sm focus-visible:ring-2 focus-visible:ring-ring"
                  readonly
                />
              </TabsContent>

              <TabsContent value="summary" class="space-y-3 focus:outline-none">
                <Label class="text-sm font-medium">AI Summary</Label>
                <div class="min-h-[220px] rounded-xl border bg-muted/20 p-4 text-sm leading-relaxed text-muted-foreground">
                  <p v-if="transcriptionSummary">
                    {{ transcriptionSummary }}
                  </p>
                  <p v-else>Generate a transcript to unlock condensed insights and action items.</p>
                </div>
              </TabsContent>

              <TabsContent value="keywords" class="space-y-3 focus:outline-none">
                <Label class="text-sm font-medium">Key Topics</Label>
                <div class="min-h-[220px] rounded-xl border bg-muted/20 p-4 text-sm leading-relaxed">
                  <div v-if="transcriptionKeywords.length" class="flex flex-wrap gap-2">
                    <Badge v-for="keyword in transcriptionKeywords" :key="keyword" variant="outline">
                      {{ keyword }}
                    </Badge>
                  </div>
                  <p v-else class="text-muted-foreground">
                    Keywords appear here after transcription, highlighting recurrent themes.
                  </p>
                </div>
              </TabsContent>
            </Tabs>

            <Separator />

            <div class="grid gap-4 sm:grid-cols-2">
              <div class="rounded-lg border bg-muted/30 p-4 space-y-2">
                <Label class="text-xs uppercase text-muted-foreground">Confidence</Label>
                <div class="flex items-center gap-2">
                  <Badge :variant="confidenceBadgeVariant">
                    {{ confidenceScore != null ? `${Math.round(confidenceScore * 100)}%` : 'Pending' }}
                  </Badge>
                  <span class="text-xs text-muted-foreground">
                    {{ confidenceScore != null ? 'Model confidence score' : 'Awaiting results' }}
                  </span>
                </div>
                <Progress :model-value="confidenceScore != null ? confidenceScore * 100 : 0" class="h-2" />
              </div>

              <div class="rounded-lg border bg-muted/30 p-4 space-y-2">
                <Label class="text-xs uppercase text-muted-foreground">Last Transcribed</Label>
                <p class="text-sm font-medium">{{ formattedLastTranscribed }}</p>
                <p class="text-xs text-muted-foreground">Maintain audit-ready logs with precise timestamps.</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Session History</CardTitle>
            <CardDescription>Keep track of recent transcripts and monitor their processing status.</CardDescription>
          </CardHeader>
          <CardContent class="space-y-4">
            <div v-if="historyItems.length === 0" class="rounded-xl border bg-muted/10 p-8 text-center text-sm text-muted-foreground">
              No transcription sessions yet. Your most recent activity will appear here.
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="item in historyItems"
                :key="item.id"
                class="rounded-xl border bg-muted/10 p-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
              >
                <div>
                  <p class="font-medium">{{ item.title }}</p>
                  <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                    <span>{{ Math.floor(item.duration / 60) }}m {{ item.duration % 60 }}s</span>
                    <span>•</span>
                    <span>{{ new Date(item.createdAt).toLocaleString() }}</span>
                    <span>•</span>
                    <span>{{ modelOptions.find((m) => m.value === item.model)?.label || item.model }}</span>
                  </div>
                </div>

                <div class="flex items-center gap-3">
                  <Badge
                    :variant="item.status === 'completed' ? 'secondary' : 'outline'"
                    class="gap-1"
                  >
                    <component
                      :is="item.status === 'completed' ? CheckCircle : Clock"
                      :size="14"
                    />
                    {{ item.status === 'completed' ? 'Completed' : 'Processing' }}
                  </Badge>
                  <Badge
                    v-if="item.confidence != null"
                    :variant="item.confidence >= 0.9 ? 'secondary' : item.confidence >= 0.75 ? 'outline' : 'destructive'"
                  >
                    {{ Math.round(item.confidence * 100) }}%
                  </Badge>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  </DashboardLayout>
</template>

