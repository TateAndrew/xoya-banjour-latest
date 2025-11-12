<script setup>
import { SelectValue as RadixSelectValue } from 'radix-vue'

const props = defineProps({
  placeholder: {
    type: String,
    default: ''
  }
})
</script>

<template>
  <RadixSelectValue :placeholder="props.placeholder">
    <slot />
  </RadixSelectValue>
</template>

