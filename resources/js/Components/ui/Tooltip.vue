<script setup>
import { TooltipProvider, TooltipRoot } from 'radix-vue'

defineProps({
  delayDuration: {
    type: Number,
    default: 200
  }
})
</script>

<template>
  <TooltipProvider :delay-duration="delayDuration">
    <TooltipRoot>
      <slot />
    </TooltipRoot>
  </TooltipProvider>
</template>

