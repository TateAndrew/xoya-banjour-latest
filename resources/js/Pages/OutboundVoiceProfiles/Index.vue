<script setup>
import { computed, ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardDescription from '@/Components/ui/CardDescription.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import { PlusCircle, RefreshCw, Loader2, PhoneCall, ShieldCheck, Gauge } from 'lucide-vue-next'

const props = defineProps({
  profiles: {
    type: Object,
    required: true
  }
})

const syncing = ref(false)
const deletingId = ref(null)
const feedback = ref(null)

const pagination = computed(() => props.profiles ?? { data: [], links: [], total: 0 })

const profileRows = computed(() => pagination.value.data ?? [])
const totalProfiles = computed(() => pagination.value.total ?? profileRows.value.length)
const enabledProfiles = computed(() => profileRows.value.filter((profile) => profile.enabled).length)
const disabledProfiles = computed(() => Math.max(totalProfiles.value - enabledProfiles.value, 0))
const averageConcurrentLimit = computed(() => {
  const limits = profileRows.value
    .map((profile) => profile.concurrent_call_limit)
    .filter((limit) => limit && Number(limit) > 0)

  if (!limits.length) {
    return null
  }

  const sum = limits.reduce((acc, value) => acc + Number(value), 0)
  return Math.round(sum / limits.length)
})

const showFeedback = (message, type = 'success') => {
  feedback.value = { message, type }

  window.setTimeout(() => {
    feedback.value = null
  }, 3500)
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

const formatCallLimit = (limit) => {
  if (!limit) return 'Unlimited'
  return Number(limit).toLocaleString()
}

const syncProfiles = () => {
  if (syncing.value) return

  syncing.value = true
  router.post(route('outbound-voice-profiles.sync'), {}, {
    preserveScroll: true,
    onSuccess: () => {
      showFeedback('Profiles synced successfully.', 'success')
      router.reload({ only: ['profiles'] })
    },
    onError: () => {
      showFeedback('Failed to sync profiles.', 'error')
    },
    onFinish: () => {
      syncing.value = false
    }
  })
}

const deleteProfile = (profileId) => {
  if (deletingId.value) return
  if (!confirm('Delete this outbound voice profile? This action cannot be undone.')) return

  deletingId.value = profileId
  router.delete(route('outbound-voice-profiles.destroy', profileId), {
    preserveScroll: true,
    onSuccess: () => {
      showFeedback('Outbound voice profile deleted.', 'success')
    },
    onError: (errors) => {
      const errorMessage = errors?.error || 'Failed to delete outbound voice profile.'
      showFeedback(errorMessage, 'error')
    },
    onFinish: () => {
      deletingId.value = null
    }
  })
}
</script>

<template>
  <DashboardLayout>
    <Head title="Outbound Voice Profiles" />

    <template #header>
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Outbound Voice Profiles</h1>
          <p class="text-sm text-muted-foreground">
            Manage routing policies, call limits, and recording defaults for outbound traffic.
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <Button
            type="button"
            variant="outline"
            class="gap-2"
            :disabled="syncing"
            @click="syncProfiles"
          >
            <Loader2 v-if="syncing" class="h-4 w-4 animate-spin" />
            <RefreshCw v-else class="h-4 w-4" />
            Sync from Telnyx
          </Button>
          <Link :href="route('outbound-voice-profiles.create')">
            <Button type="button" class="gap-2">
              <PlusCircle class="h-4 w-4" />
              New Voice Profile
            </Button>
          </Link>
        </div>
      </div>
    </template>

    <div class="space-y-6 pb-12">
      <div class="space-y-3">
        <div
          v-if="$page.props.flash?.success"
          class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-700"
        >
          {{ $page.props.flash.success }}
        </div>
        <div
          v-if="$page.props.flash?.error"
          class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-700"
        >
          {{ $page.props.flash.error }}
        </div>
        <div
          v-if="feedback"
          class="rounded-lg border px-4 py-2 text-sm"
          :class="feedback.type === 'error'
            ? 'border-red-200 bg-red-50 text-red-700'
            : 'border-emerald-200 bg-emerald-50 text-emerald-700'"
        >
          {{ feedback.message }}
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total Profiles</CardTitle>
            <PhoneCall class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold tracking-tight">
              {{ totalProfiles.toLocaleString() }}
            </div>
            <p class="text-xs text-muted-foreground">Across all workspaces</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Active Capacity</CardTitle>
            <ShieldCheck class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent class="space-y-1.5">
            <div class="text-2xl font-semibold tracking-tight">
              {{ enabledProfiles.toLocaleString() }}
            </div>
            <div class="flex items-center gap-2 text-xs text-muted-foreground">
              Enabled profiles
              <Badge variant="outline">
                {{ disabledProfiles }} offline
              </Badge>
            </div>
          </CardContent>
        </Card>

        <Card class="md:col-span-2 xl:col-span-1">
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Concurrent Call Limit</CardTitle>
            <Gauge class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold tracking-tight">
              {{ averageConcurrentLimit ? `${averageConcurrentLimit.toLocaleString()}` : 'Unlimited' }}
            </div>
            <p class="text-xs text-muted-foreground">
              Average limit across profiles with defined caps.
            </p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader class="pb-4">
          <CardTitle>Profile Directory</CardTitle>
          <CardDescription>
            Overview of outbound voice configurations, recording preferences, and call limits.
          </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
          <div class="overflow-hidden rounded-lg border">
            <table class="min-w-full divide-y divide-border text-sm">
              <thead class="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                  <th class="px-4 py-3 text-left font-semibold">Profile</th>
                  <th class="px-4 py-3 text-left font-semibold">Status</th>
                  <th class="px-4 py-3 text-left font-semibold">Call Limit</th>
                  <th class="px-4 py-3 text-left font-semibold">Created</th>
                  <th class="px-4 py-3 text-left font-semibold">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="profile in profileRows"
                  :key="profile.id"
                  class="border-t bg-background transition hover:bg-muted/40"
                >
                  <td class="px-4 py-4 align-top">
                    <div class="font-medium text-foreground">
                      {{ profile.name }}
                    </div>
                    <p class="text-xs font-mono text-muted-foreground">
                      {{ profile.telnyx_profile_id }}
                    </p>
                    <p v-if="profile.tags" class="mt-2 text-xs text-muted-foreground">
                      Tags: {{ profile.tags }}
                    </p>
                  </td>
                  <td class="px-4 py-4 align-top">
                    <Badge :variant="profile.enabled ? 'secondary' : 'destructive'">
                      {{ profile.enabled ? 'Enabled' : 'Disabled' }}
                    </Badge>
                    <p class="mt-1 text-xs text-muted-foreground capitalize">
                      Recording: {{ profile.call_recording_type || 'default' }}
                    </p>
                  </td>
                  <td class="px-4 py-4 align-top">
                    <div class="font-medium">
                      {{ formatCallLimit(profile.concurrent_call_limit) }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                      Max rate: {{ profile.max_destination_rate ? `${profile.max_destination_rate}¢/min` : 'Not set' }}
                    </p>
                  </td>
                  <td class="px-4 py-4 align-top text-sm text-muted-foreground">
                    {{ formatDate(profile.created_at) }}
                  </td>
                  <td class="px-4 py-4 align-top">
                    <div class="flex flex-wrap items-center gap-2">
                      <Link
                        :href="route('outbound-voice-profiles.show', profile.id)"
                        class="text-sm font-medium text-primary transition hover:underline"
                      >
                        View
                      </Link>
                      <Link
                        :href="route('outbound-voice-profiles.edit', profile.id)"
                        class="text-sm font-medium text-primary transition hover:underline"
                      >
                        Edit
                      </Link>
                      <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="text-destructive hover:text-destructive focus-visible:ring-destructive"
                        :disabled="deletingId === profile.id"
                        @click="deleteProfile(profile.id)"
                      >
                        <Loader2
                          v-if="deletingId === profile.id"
                          class="mr-1 h-3.5 w-3.5 animate-spin"
                        />
                        Delete
                      </Button>
                    </div>
                  </td>
                </tr>
                <tr v-if="profileRows.length === 0">
                  <td colspan="5" class="py-12 text-center text-sm text-muted-foreground">
                    You have not created any outbound voice profiles yet.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="pagination.links && pagination.links.length > 1"
            class="flex flex-wrap items-center justify-between gap-2"
          >
            <p class="text-xs text-muted-foreground">
              Showing {{ profileRows.length }} of {{ pagination.total }} profiles
            </p>
            <div class="flex flex-wrap gap-2">
              <Link
                v-for="(link, index) in pagination.links"
                :key="index"
                :href="link.url || '#'"
                :aria-disabled="!link.url"
                class="rounded-md border px-3 py-1.5 text-xs font-medium transition"
                :class="[
                  link.active
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'border-border bg-background text-muted-foreground hover:bg-muted/40',
                  !link.url && 'pointer-events-none opacity-60'
                ]"
                v-html="link.label"
              />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </DashboardLayout>
</template>
