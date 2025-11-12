<script setup>
import { onMounted } from 'vue'
import { useTheme } from '@/composables/useTheme'
import { Sun, Moon } from 'lucide-vue-next'
import Switch from '@/Components/ui/Switch.vue'

const { theme, toggleTheme, initTheme } = useTheme()

onMounted(() => {
  initTheme()
})
</script>

<template>
  <div class="flex items-center gap-2">
    <Sun :size="16" class="text-muted-foreground" />
    <Switch 
      :checked="theme === 'dark'" 
      @update:checked="toggleTheme"
    />
    <Moon :size="16" class="text-muted-foreground" />
  </div>
</template>

