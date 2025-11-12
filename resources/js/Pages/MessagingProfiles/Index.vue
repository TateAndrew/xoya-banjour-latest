<script setup>
import { computed, ref } from 'vue'
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
import {
  MessageSquare,
  ShieldCheck,
  Globe2,
  Activity,
  RefreshCw,
  LinkIcon,
  Trash2,
  PlusCircle
} from 'lucide-vue-next'

const props = defineProps({
  messagingProfiles: {
    type: Object,
    required: true
  }
})

const deletingId = ref(null)

const profiles = computed(() => props.messagingProfiles?.data || [])
const totalProfiles = computed(() => profiles.value.length)
const enabledProfiles = computed(() =>
  profiles.value.filter((profile) => profile.enabled).length
)
const webhookProfiles = computed(() =>
  profiles.value.filter((profile) => !!profile.webhook_url).length
)
const globalProfiles = computed(() =>
  profiles.value.filter((profile) =>
    Array.isArray(profile.whitelisted_destinations) &&
    profile.whitelisted_destinations.includes('*')
  ).length
)

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

const deleteProfile = async (profile) => {
  if (!confirm(`Delete messaging profile "${profile.name}"? This action cannot be undone.`)) {
    return
  }

  deletingId.value = profile.id
  try {
    await axios.delete(`/messaging-profiles/${profile.id}`)
    alert('Messaging profile deleted.')
    window.location.reload()
  } catch (error) {
    console.error('Delete error', error)
    alert('Failed to delete messaging profile. Please try again.')
  } finally {
    deletingId.value = null
  }
}

const statusVariant = (enabled) => (enabled ? 'secondary' : 'destructive')
</script>

<template>
  <DashboardLayout>
    <Head title="Messaging Profiles" />

    <template #header>
      <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h1 class="text-3xl font-bold tracking-tight">Messaging Profiles</h1>
          <p class="text-sm text-muted-foreground">
            Manage Telnyx messaging profiles, destinations, and webhook endpoints.
          </p>
        </div>
        <Link
          :href="route('messaging-profiles.create')"
          class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90"
        >
          <PlusCircle class="h-4 w-4" />
          New Messaging Profile
        </Link>
      </div>
    </template>

    <div class="space-y-6 pb-12">
      <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Total profiles</CardTitle>
            <MessageSquare class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ totalProfiles }}</div>
            <p class="text-xs text-muted-foreground">Across all messaging use cases</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Enabled</CardTitle>
            <ShieldCheck class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ enabledProfiles }}</div>
            <p class="text-xs text-muted-foreground">Ready to send traffic</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Webhook configured</CardTitle>
            <LinkIcon class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ webhookProfiles }}</div>
            <p class="text-xs text-muted-foreground">Profiles posting delivery updates</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium">Global coverage</CardTitle>
            <Globe2 class="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div class="text-2xl font-semibold">{{ globalProfiles }}</div>
            <p class="text-xs text-muted-foreground">Whitelisted for all countries</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader class="flex flex-wrap items-center justify-between gap-4">
          <div>
            <CardTitle class="text-lg font-semibold">Profiles</CardTitle>
            <CardDescription>
              Review configuration status, webhook endpoints, and destination coverage.
            </CardDescription>
          </div>
          <Button variant="outline" class="gap-2" @click="window.location.reload()">
            <RefreshCw class="h-4 w-4" />
            Refresh
          </Button>
        </CardHeader>
        <CardContent>
          <div
            v-if="!profiles.length"
            class="flex flex-col items-center justify-center gap-4 rounded-lg border border-dashed border-border bg-muted/40 px-8 py-16 text-center"
          >
            <MessageSquare class="h-10 w-10 text-muted-foreground" />
            <div class="space-y-1">
              <h3 class="text-lg font-semibold">No messaging profiles yet.</h3>
              <p class="text-sm text-muted-foreground">
                Create a profile to map numbers to messaging capabilities and webhooks.
              </p>
            </div>
            <Link
              :href="route('messaging-profiles.create')"
              class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90"
            >
              <PlusCircle class="h-4 w-4" />
              Create Messaging Profile
            </Link>
          </div>

          <div v-else class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
              <thead class="bg-muted/80 text-xs uppercase tracking-wide text-muted-foreground">
                <tr>
                  <th class="px-4 py-3 text-left font-medium">Profile</th>
                  <th class="px-4 py-3 text-left font-medium">Status</th>
                  <th class="px-4 py-3 text-left font-medium">Destinations</th>
                  <th class="px-4 py-3 text-left font-medium">Webhook</th>
                  <th class="px-4 py-3 text-left font-medium">Created</th>
                  <th class="px-4 py-3 text-left font-medium">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="profile in profiles"
                  :key="profile.id"
                  class="border-t bg-background/80 transition hover:bg-muted/40"
                >
                  <td class="px-4 py-4 align-top">
                    <p class="font-medium text-foreground">{{ profile.name }}</p>
                    <p class="text-xs text-muted-foreground">
                      Telnyx ID • {{ profile.telnyx_profile_id || 'N/A' }}
                    </p>
                  </td>
                  <td class="px-4 py-4 align-top">
                    <Badge :variant="statusVariant(profile.enabled)" class="capitalize">
                      {{ profile.enabled ? 'Enabled' : 'Disabled' }}
                    </Badge>
                  </td>
                  <td class="px-4 py-4 align-top text-xs text-muted-foreground">
                    <span
                      v-if="profile.whitelisted_destinations?.includes('*')"
                      class="inline-flex items-center gap-1 font-medium text-foreground"
                    >
                      <Globe2 class="h-3.5 w-3.5" />
                      All Countries
                    </span>
                    <span v-else>
                      {{ (profile.whitelisted_destinations || []).join(', ') || 'N/A' }}
                    </span>
                  </td>
                  <td class="px-4 py-4 align-top text-xs text-muted-foreground">
                    <Badge variant="outline">
                      {{ profile.webhook_url ? 'Configured' : 'Not set' }}
                    </Badge>
                  </td>
                  <td class="px-4 py-4 align-top text-xs text-muted-foreground">
                    {{ formatDate(profile.created_at) }}
                  </td>
                  <td class="px-4 py-4 align-top">
                    <div class="flex flex-wrap gap-2">
                      <Link
                        :href="route('messaging-profiles.show', profile.id)"
                        class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium shadow-sm transition hover:bg-accent hover:text-accent-foreground"
                      >
                        <Activity class="h-3.5 w-3.5" />
                        View
                      </Link>
                      <Link
                        :href="route('messaging-profiles.edit', profile.id)"
                        class="inline-flex items-center gap-2 rounded-md border border-input bg-background px-3 py-1.5 text-xs font-medium shadow-sm transition hover:bg-accent hover:text-accent-foreground"
                      >
                        <ShieldCheck class="h-3.5 w-3.5" />
                        Edit
                      </Link>
                      <Button
                        variant="destructive"
                        size="sm"
                        class="gap-2"
                        :disabled="deletingId === profile.id"
                        @click="deleteProfile(profile)"
                      >
                        <Loader2 v-if="deletingId === profile.id" class="h-3.5 w-3.5 animate-spin" />
                        <Trash2 v-else class="h-3.5 w-3.5" />
                        Delete
                      </Button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div
            v-if="props.messagingProfiles.links && props.messagingProfiles.links.length > 3"
            class="border-t px-4 py-3"
          >
            <div class="flex justify-end gap-2">
              <Link
                v-for="(link, index) in props.messagingProfiles.links"
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
  </DashboardLayout>
</template>

