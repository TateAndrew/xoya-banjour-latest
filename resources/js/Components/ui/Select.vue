<script setup>
import { SelectRoot } from 'radix-vue'

const props = defineProps({
  modelValue: {
    type: [String, Number, Object, Boolean, Array, null],
    default: undefined
  },
  defaultValue: {
    type: [String, Number, Object, Boolean, Array, null],
    default: undefined
  },
  disabled: {
    type: Boolean,
    default: false
  },
  name: {
    type: String,
    default: undefined
  }
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <SelectRoot
    :model-value="props.modelValue"
    :default-value="props.defaultValue"
    :disabled="props.disabled"
    :name="props.name"
    @update:model-value="emit('update:modelValue', $event)"
  >
    <slot />
  </SelectRoot>
</template>

