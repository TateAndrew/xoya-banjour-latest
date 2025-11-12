<script setup>
import { TooltipContent, TooltipPortal } from 'radix-vue'
import { cn } from '@/lib/utils'

defineProps({
  side: {
    type: String,
    default: 'top',
    validator: (value) => ['top', 'right', 'bottom', 'left'].includes(value)
  },
  class: {
    type: String,
    default: ''
  }
})
</script>

<template>
  <TooltipPortal>
    <TooltipContent
      :side="side"
      :side-offset="4"
      :class="cn(
        'z-50 overflow-hidden rounded-md border bg-popover px-3 py-1.5 text-sm text-popover-foreground shadow-md animate-in fade-in-0 zoom-in-95 data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=closed]:zoom-out-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2',
        $props.class
      )"
    >
      <slot />
    </TooltipContent>
  </TooltipPortal>
</template>

