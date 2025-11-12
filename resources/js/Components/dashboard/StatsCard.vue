<script setup>
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import { TrendingUp, TrendingDown } from 'lucide-vue-next'

defineProps({
  title: {
    type: String,
    required: true
  },
  value: {
    type: [String, Number],
    required: true
  },
  icon: {
    type: Object,
    required: true
  },
  change: {
    type: String,
    default: null
  },
  changeType: {
    type: String,
    default: 'increase',
    validator: (value) => ['increase', 'decrease'].includes(value)
  },
  description: {
    type: String,
    default: null
  }
})
</script>

<template>
  <Card class="hover:shadow-md transition-shadow">
    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
      <CardTitle class="text-sm font-medium">{{ title }}</CardTitle>
      <component :is="icon" class="h-4 w-4 text-muted-foreground" />
    </CardHeader>
    <CardContent>
      <div class="text-2xl font-bold">{{ value }}</div>
      <p v-if="change || description" class="text-xs text-muted-foreground flex items-center mt-1">
        <TrendingUp v-if="changeType === 'increase' && change" :size="12" class="mr-1 text-green-500" />
        <TrendingDown v-if="changeType === 'decrease' && change" :size="12" class="mr-1 text-red-500" />
        <span v-if="change" :class="changeType === 'increase' ? 'text-green-500' : 'text-red-500'">
          {{ change }}
        </span>
        <span v-if="change && description" class="ml-1">{{ description }}</span>
        <span v-else-if="description">{{ description }}</span>
      </p>
    </CardContent>
  </Card>
</template>

