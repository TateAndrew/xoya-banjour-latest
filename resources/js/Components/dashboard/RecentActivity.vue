<script setup>
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardDescription from '@/Components/ui/CardDescription.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import Avatar from '@/Components/ui/Avatar.vue'
import AvatarFallback from '@/Components/ui/AvatarFallback.vue'
import { formatDistanceToNow } from 'date-fns'

defineProps({
  activities: {
    type: Array,
    default: () => []
  }
})

const getInitials = (name) => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>Recent Activity</CardTitle>
      <CardDescription>Latest actions in your account</CardDescription>
    </CardHeader>
    <CardContent>
      <div class="space-y-4">
        <div
          v-for="activity in activities"
          :key="activity.id"
          class="flex items-center gap-4"
        >
          <Avatar class="h-9 w-9">
            <AvatarFallback>{{ getInitials(activity.user) }}</AvatarFallback>
          </Avatar>
          <div class="flex-1 space-y-1">
            <p class="text-sm font-medium leading-none">
              {{ activity.user }}
            </p>
            <p class="text-sm text-muted-foreground">
              {{ activity.action }}
            </p>
          </div>
          <div class="text-sm text-muted-foreground">
            {{ formatDistanceToNow(new Date(activity.time), { addSuffix: true }) }}
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>

