<script setup>
import { AvatarImage } from 'radix-vue'
import { cn } from '@/lib/utils'

defineProps({
  class: {
    type: String,
    default: ''
  },
  src: {
    type: String,
    required: true
  }
})
</script>

<template>
  <AvatarImage :class="cn('aspect-square h-full w-full', $props.class)" :src="src" />
</template>

