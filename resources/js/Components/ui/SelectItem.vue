<script setup>
import {
  SelectItem as RadixSelectItem,
  SelectItemText,
  SelectItemIndicator
} from 'radix-vue'
import { Check } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps({
  value: {
    type: [String, Number],
    required: true
  },
  class: {
    type: String,
    default: ''
  }
})
</script>

<template>
  <RadixSelectItem
    :value="props.value"
    :class="cn(
      'relative flex w-full select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
      props.class
    )"
  >
    <SelectItemIndicator class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
      <Check class="h-4 w-4" />
    </SelectItemIndicator>
    <SelectItemText>
      <slot />
    </SelectItemText>
  </RadixSelectItem>
</template>

