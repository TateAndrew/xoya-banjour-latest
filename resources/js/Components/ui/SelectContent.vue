<script setup>
import {
  SelectContent as RadixSelectContent,
  SelectViewport,
  SelectPortal,
  SelectScrollUpButton,
  SelectScrollDownButton
} from 'radix-vue'
import { ChevronDown, ChevronUp } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps({
  class: {
    type: String,
    default: ''
  }
})
</script>

<template>
  <SelectPortal>
    <RadixSelectContent
      :class="cn(
        'relative z-50 min-w-[8rem] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-top-[8px] data-[state=open]:slide-in-from-top-[8px]',
        props.class
      )"
    >
      <SelectScrollUpButton class="flex cursor-default items-center justify-center py-1 text-muted-foreground">
        <ChevronUp class="h-4 w-4" />
      </SelectScrollUpButton>
      <SelectViewport class="p-1">
        <slot />
      </SelectViewport>
      <SelectScrollDownButton class="flex cursor-default items-center justify-center py-1 text-muted-foreground">
        <ChevronDown class="h-4 w-4" />
      </SelectScrollDownButton>
    </RadixSelectContent>
  </SelectPortal>
</template>

