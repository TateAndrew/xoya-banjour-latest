<script setup>
import { ref, computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { 
  LayoutDashboard, 
  Phone, 
  Video, 
  MessageSquare, 
  PhoneCall,
  Settings,
  Users,
  Shield,
  Key,
  FileAudio,
  FileText,
  Menu,
  X,
  Search,
  Bell,
  ChevronDown,
  LogOut,
  User,
  CreditCard,
  PhoneIncoming
} from 'lucide-vue-next'
import Avatar from '@/Components/ui/Avatar.vue'
import AvatarImage from '@/Components/ui/AvatarImage.vue'
import AvatarFallback from '@/Components/ui/AvatarFallback.vue'
import ThemeToggle from '@/Components/ThemeToggle.vue'
import { cn } from '@/lib/utils'

const page = usePage()
const sidebarOpen = ref(true)
const mobileMenuOpen = ref(false)
const userMenuOpen = ref(false)

const navigation = [
  { 
    name: 'Dashboard', 
    href: 'dashboard', 
    icon: LayoutDashboard,
    current: () => route().current('dashboard')
  },
  { 
    name: 'Dialer', 
    href: 'dialer', 
    icon: Phone,
    current: () => route().current('dialer')
  },
  { 
    name: 'SMS Messenger', 
    href: 'messenger.index', 
    icon: MessageSquare,
    current: () => route().current('messenger.*')
  },
  { 
    name: 'Video Calls', 
    href: 'video-calls.index', 
    icon: Video,
    current: () => route().current('video-calls.*') || route().current('video-call.*')
  },
]

const callManagement = [
  {
    name: 'Recordings',
    href: 'recordings.index',
    icon: FileAudio,
    current: () => route().current('recordings.*')
  },
  {
    name: 'Transcriptions',
    href: 'transcriptions.index',
    icon: FileText,
    current: () => route().current('transcriptions.*')
  },
]

const configuration = [
  {
    name: 'Phone Numbers',
    href: 'phone-numbers.index',
    icon: PhoneCall,
    current: () => route().current('phone-numbers.*')
  },
  {
    name: 'SIP Trunks',
    href: 'sip-trunks.index',
    icon: PhoneIncoming,
    current: () => route().current('sip-trunks.*')
  },
  {
    name: 'Messaging Profiles',
    href: 'messaging-profiles.index',
    icon: MessageSquare,
    current: () => route().current('messaging-profiles.*')
  },
  {
    name: 'Voice Profiles',
    href: 'outbound-voice-profiles.index',
    icon: Phone,
    current: () => route().current('outbound-voice-profiles.*')
  },
]

const administration = [
  {
    name: 'Users',
    href: 'users.index',
    icon: Users,
    current: () => route().current('users.*')
  },
  {
    name: 'Roles',
    href: 'roles.index',
    icon: Shield,
    current: () => route().current('roles.*')
  },
  {
    name: 'Permissions',
    href: 'permissions.index',
    icon: Key,
    current: () => route().current('permissions.*')
  },
]

const userInitials = computed(() => {
  const name = page.props.auth.user.name
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
})
</script>

<template>
  <div class="min-h-screen bg-background">
    <!-- Mobile menu overlay -->
    <div 
      v-show="mobileMenuOpen"
      class="fixed inset-0 z-40 bg-black/50 lg:hidden"
      @click="mobileMenuOpen = false"
    />

    <!-- Mobile sidebar -->
    <aside
      :class="cn(
        'fixed inset-y-0 left-0 z-50 w-72 transform bg-card border-r transition-transform duration-300 ease-in-out lg:hidden',
        mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'
      )"
    >
      <div class="flex h-full flex-col">
        <!-- Mobile header -->
        <div class="flex h-16 items-center justify-between border-b px-6">
          <h2 class="text-xl font-bold">Xoya Banjour</h2>
          <button @click="mobileMenuOpen = false" class="text-muted-foreground hover:text-foreground">
            <X :size="24" />
          </button>
        </div>

        <!-- Mobile navigation -->
        <nav class="flex-1 space-y-1 overflow-y-auto p-4">
          <!-- Main Navigation -->
          <div class="space-y-1">
            <Link
              v-for="item in navigation"
              :key="item.name"
              :href="route(item.href)"
              :class="cn(
                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                item.current()
                  ? 'bg-primary text-primary-foreground'
                  : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
              )"
              @click="mobileMenuOpen = false"
            >
              <component :is="item.icon" :size="20" />
              <span>{{ item.name }}</span>
            </Link>
          </div>

          <!-- Call Management -->
          <div class="pt-4">
            <h3 class="mb-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
              Call Management
            </h3>
            <div class="space-y-1">
              <Link
                v-for="item in callManagement"
                :key="item.name"
                :href="route(item.href)"
                :class="cn(
                  'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                  item.current()
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                )"
                @click="mobileMenuOpen = false"
              >
                <component :is="item.icon" :size="20" />
                <span>{{ item.name }}</span>
              </Link>
            </div>
          </div>

          <!-- Configuration -->
          <div class="pt-4">
            <h3 class="mb-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
              Configuration
            </h3>
            <div class="space-y-1">
              <Link
                v-for="item in configuration"
                :key="item.name"
                :href="route(item.href)"
                :class="cn(
                  'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                  item.current()
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                )"
                @click="mobileMenuOpen = false"
              >
                <component :is="item.icon" :size="20" />
                <span>{{ item.name }}</span>
              </Link>
            </div>
          </div>

          <!-- Administration -->
          <div class="pt-4">
            <h3 class="mb-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
              Administration
            </h3>
            <div class="space-y-1">
              <Link
                v-for="item in administration"
                :key="item.name"
                :href="route(item.href)"
                :class="cn(
                  'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                  item.current()
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                )"
                @click="mobileMenuOpen = false"
              >
                <component :is="item.icon" :size="20" />
                <span>{{ item.name }}</span>
              </Link>
            </div>
          </div>
        </nav>
      </div>
    </aside>

    <!-- Desktop sidebar -->
    <aside
      :class="cn(
        'fixed inset-y-0 left-0 z-30 hidden border-r bg-card transition-all duration-300 lg:block',
        sidebarOpen ? 'w-72' : 'w-20'
      )"
    >
      <div class="flex h-full flex-col">
        <!-- Logo -->
        <div class="flex h-16 items-center border-b px-6">
          <h2 v-if="sidebarOpen" class="text-xl font-bold">Xoya Banjour</h2>
          <h2 v-else class="text-xl font-bold">XB</h2>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 space-y-1 overflow-y-auto p-4">
          <!-- Main Navigation -->
          <div class="space-y-1">
            <Link
              v-for="item in navigation"
              :key="item.name"
              :href="route(item.href)"
              :class="cn(
                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                item.current()
                  ? 'bg-primary text-primary-foreground'
                  : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
              )"
              :title="!sidebarOpen ? item.name : ''"
            >
              <component :is="item.icon" :size="20" />
              <span v-if="sidebarOpen">{{ item.name }}</span>
            </Link>
          </div>

          <!-- Call Management -->
          <div class="pt-4">
            <h3 v-if="sidebarOpen" class="mb-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
              Call Management
            </h3>
            <div v-else class="mb-2 h-px bg-border" />
            <div class="space-y-1">
              <Link
                v-for="item in callManagement"
                :key="item.name"
                :href="route(item.href)"
                :class="cn(
                  'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                  item.current()
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                )"
                :title="!sidebarOpen ? item.name : ''"
              >
                <component :is="item.icon" :size="20" />
                <span v-if="sidebarOpen">{{ item.name }}</span>
              </Link>
            </div>
          </div>

          <!-- Configuration -->
          <div class="pt-4">
            <h3 v-if="sidebarOpen" class="mb-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
              Configuration
            </h3>
            <div v-else class="mb-2 h-px bg-border" />
            <div class="space-y-1">
              <Link
                v-for="item in configuration"
                :key="item.name"
                :href="route(item.href)"
                :class="cn(
                  'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                  item.current()
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                )"
                :title="!sidebarOpen ? item.name : ''"
              >
                <component :is="item.icon" :size="20" />
                <span v-if="sidebarOpen">{{ item.name }}</span>
              </Link>
            </div>
          </div>

          <!-- Administration -->
          <div class="pt-4">
            <h3 v-if="sidebarOpen" class="mb-2 px-3 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
              Administration
            </h3>
            <div v-else class="mb-2 h-px bg-border" />
            <div class="space-y-1">
              <Link
                v-for="item in administration"
                :key="item.name"
                :href="route(item.href)"
                :class="cn(
                  'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                  item.current()
                    ? 'bg-primary text-primary-foreground'
                    : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                )"
                :title="!sidebarOpen ? item.name : ''"
              >
                <component :is="item.icon" :size="20" />
                <span v-if="sidebarOpen">{{ item.name }}</span>
              </Link>
            </div>
          </div>
        </nav>

        <!-- Toggle button -->
        <div class="border-t p-4">
          <button
            @click="sidebarOpen = !sidebarOpen"
            class="flex w-full items-center justify-center rounded-lg px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-accent hover:text-accent-foreground"
          >
            <Menu :size="20" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <div :class="cn('lg:pl-72 transition-all duration-300', !sidebarOpen && 'lg:pl-20')">
      <!-- Top header -->
      <header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b bg-card px-4 lg:px-6">
        <!-- Mobile menu button -->
        <button
          @click="mobileMenuOpen = true"
          class="lg:hidden text-muted-foreground hover:text-foreground"
        >
          <Menu :size="24" />
        </button>

        <!-- Search -->
        <div class="flex-1 max-w-md">
          <div class="relative">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <input
              type="search"
              placeholder="Search..."
              class="w-full rounded-lg border border-input bg-background pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
            />
          </div>
        </div>

        <!-- Right side -->
        <div class="flex items-center gap-4">
          <!-- Theme Toggle -->
          <ThemeToggle />
          
          <!-- Notifications -->
          <button class="relative text-muted-foreground hover:text-foreground">
            <Bell :size="20" />
            <span class="absolute -top-1 -right-1 h-4 w-4 rounded-full bg-destructive text-[10px] font-medium text-destructive-foreground flex items-center justify-center">
              3
            </span>
          </button>

          <!-- User menu -->
          <div class="relative">
            <button
              @click="userMenuOpen = !userMenuOpen"
              class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-accent"
            >
              <Avatar class="h-8 w-8">
                <AvatarFallback>{{ userInitials }}</AvatarFallback>
              </Avatar>
              <div class="hidden text-left lg:block">
                <p class="text-sm font-medium">{{ $page.props.auth.user.name }}</p>
                <p class="text-xs text-muted-foreground">{{ $page.props.auth.user.email }}</p>
              </div>
              <ChevronDown :size="16" class="text-muted-foreground hidden lg:block" />
            </button>

            <!-- Dropdown menu -->
            <div
              v-show="userMenuOpen"
              @click="userMenuOpen = false"
              class="absolute right-0 mt-2 w-56 rounded-lg border bg-card shadow-lg"
            >
              <div class="p-2">
                <Link
                  :href="route('profile.edit')"
                  class="flex items-center gap-3 rounded-md px-3 py-2 text-sm hover:bg-accent"
                >
                  <User :size="16" />
                  <span>Profile</span>
                </Link>
                <Link
                  :href="route('billing.index')"
                  class="flex items-center gap-3 rounded-md px-3 py-2 text-sm hover:bg-accent"
                >
                  <CreditCard :size="16" />
                  <span>Billing</span>
                </Link>
                <Link
                  :href="route('profile.edit')"
                  class="flex items-center gap-3 rounded-md px-3 py-2 text-sm hover:bg-accent"
                >
                  <Settings :size="16" />
                  <span>Settings</span>
                </Link>
                <div class="my-1 h-px bg-border" />
                <Link
                  :href="route('logout')"
                  method="post"
                  as="button"
                  class="flex w-full items-center gap-3 rounded-md px-3 py-2 text-sm text-destructive hover:bg-accent"
                >
                  <LogOut :size="16" />
                  <span>Log out</span>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </header>

      <!-- Page heading -->
      <header v-if="$slots.header" class="bg-card border-b">
        <div class="px-4 py-6 lg:px-6">
          <slot name="header" />
        </div>
      </header>

      <!-- Page content -->
      <main class="p-4 lg:p-6">
        <slot />
      </main>
    </div>
  </div>
</template>

