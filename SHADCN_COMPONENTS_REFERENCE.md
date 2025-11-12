# Shadcn Components Quick Reference

## Available Components

### Button
```vue
<Button>Click me</Button>
<Button variant="secondary">Secondary</Button>
<Button variant="outline">Outline</Button>
<Button variant="ghost">Ghost</Button>
<Button variant="destructive">Danger</Button>
<Button variant="link">Link</Button>
<Button size="sm">Small</Button>
<Button size="lg">Large</Button>
<Button size="icon"><Icon /></Button>
```

**Props:**
- `variant`: `'default' | 'secondary' | 'outline' | 'ghost' | 'destructive' | 'link'`
- `size`: `'default' | 'sm' | 'lg' | 'icon'`

---

### Card
```vue
<Card>
  <CardHeader>
    <CardTitle>Title</CardTitle>
    <CardDescription>Description</CardDescription>
  </CardHeader>
  <CardContent>
    <p>Content goes here</p>
  </CardContent>
</Card>
```

**Components:**
- `Card` - Container
- `CardHeader` - Header section
- `CardTitle` - Title text
- `CardDescription` - Subtitle text
- `CardContent` - Main content area

---

### Avatar
```vue
<Avatar>
  <AvatarImage src="/avatar.jpg" />
  <AvatarFallback>JD</AvatarFallback>
</Avatar>

<!-- Custom size -->
<Avatar class="h-12 w-12">
  <AvatarFallback>AB</AvatarFallback>
</Avatar>
```

**Components:**
- `Avatar` - Container (default size: h-10 w-10)
- `AvatarImage` - Image element
- `AvatarFallback` - Fallback content (initials, icon, etc.)

---

### Badge
```vue
<Badge>Default</Badge>
<Badge variant="secondary">Secondary</Badge>
<Badge variant="destructive">Danger</Badge>
<Badge variant="outline">Outline</Badge>
```

**Props:**
- `variant`: `'default' | 'secondary' | 'destructive' | 'outline'`

---

### Input
```vue
<Input v-model="value" placeholder="Enter text..." />
<Input type="email" placeholder="Email" />
<Input type="password" placeholder="Password" />
<Input disabled placeholder="Disabled" />
```

**Props:**
- `type`: Input type (default: 'text')
- `modelValue`: Two-way binding value
- `class`: Additional classes

---

### Label
```vue
<Label for="email">Email Address</Label>
<Input id="email" type="email" />

<!-- Form example -->
<div class="space-y-2">
  <Label for="name">Name</Label>
  <Input id="name" v-model="form.name" />
</div>
```

---

### Separator
```vue
<!-- Horizontal (default) -->
<Separator />

<!-- Vertical -->
<Separator orientation="vertical" />

<!-- With custom spacing -->
<div class="my-4">
  <Separator />
</div>
```

**Props:**
- `orientation`: `'horizontal' | 'vertical'`

---

## Common Patterns

### Form with Labels and Inputs
```vue
<div class="space-y-4">
  <div class="space-y-2">
    <Label for="name">Name</Label>
    <Input id="name" v-model="form.name" placeholder="John Doe" />
  </div>
  
  <div class="space-y-2">
    <Label for="email">Email</Label>
    <Input id="email" type="email" v-model="form.email" placeholder="john@example.com" />
  </div>
  
  <Button class="w-full">Submit</Button>
</div>
```

### Stats Card
```vue
<Card>
  <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
    <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
    <DollarSign class="h-4 w-4 text-muted-foreground" />
  </CardHeader>
  <CardContent>
    <div class="text-2xl font-bold">$45,231.89</div>
    <p class="text-xs text-muted-foreground">
      +20.1% from last month
    </p>
  </CardContent>
</Card>
```

### User Menu with Avatar
```vue
<div class="flex items-center gap-3">
  <Avatar class="h-8 w-8">
    <AvatarImage :src="user.avatar" />
    <AvatarFallback>{{ user.initials }}</AvatarFallback>
  </Avatar>
  <div>
    <p class="text-sm font-medium">{{ user.name }}</p>
    <p class="text-xs text-muted-foreground">{{ user.email }}</p>
  </div>
</div>
```

### Action Card
```vue
<Card class="hover:shadow-lg transition-shadow">
  <CardHeader>
    <div class="flex items-center gap-2">
      <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
        <Users class="h-6 w-6 text-blue-600 dark:text-blue-400" />
      </div>
      <div>
        <CardTitle>Users</CardTitle>
        <CardDescription>Manage your team</CardDescription>
      </div>
    </div>
  </CardHeader>
  <CardContent>
    <p class="text-sm text-muted-foreground mb-4">
      Add, edit, and manage user permissions.
    </p>
    <Link :href="route('users.index')">
      <Button class="w-full">
        View Users
        <ArrowUpRight :size="16" class="ml-2" />
      </Button>
    </Link>
  </CardContent>
</Card>
```

### Status Badge
```vue
<Badge>
  <Activity :size="14" class="mr-1" />
  Active
</Badge>

<Badge variant="secondary">
  <Clock :size="14" class="mr-1" />
  Pending
</Badge>

<Badge variant="destructive">
  <AlertCircle :size="14" class="mr-1" />
  Error
</Badge>
```

---

## Color Classes

### Text Colors
- `text-foreground` - Primary text
- `text-muted-foreground` - Secondary text
- `text-primary` - Brand color text
- `text-destructive` - Error text

### Background Colors
- `bg-background` - Main background
- `bg-card` - Card background
- `bg-primary` - Brand background
- `bg-secondary` - Secondary background
- `bg-muted` - Muted background
- `bg-accent` - Accent background
- `bg-destructive` - Error background

### Border Colors
- `border-border` - Default border
- `border-input` - Input border

---

## Responsive Grid Layouts

### 2-Column on Desktop
```vue
<div class="grid gap-6 md:grid-cols-2">
  <Card>...</Card>
  <Card>...</Card>
</div>
```

### 3-Column on Large Screens
```vue
<div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
  <Card>...</Card>
  <Card>...</Card>
  <Card>...</Card>
</div>
```

### 4-Column Stats
```vue
<div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
  <Card>...</Card>
  <Card>...</Card>
  <Card>...</Card>
  <Card>...</Card>
</div>
```

---

## Icons with Lucide

### Common Icons
```vue
<script setup>
import { 
  Home, User, Settings, Bell, Search,
  Phone, Mail, Calendar, Clock, Check,
  X, Plus, Minus, ChevronDown, ChevronUp,
  ArrowRight, ArrowLeft, Edit, Trash, Save
} from 'lucide-vue-next'
</script>

<template>
  <!-- Default size (24px) -->
  <Home />
  
  <!-- Custom size -->
  <User :size="16" />
  <Settings :size="20" />
  
  <!-- With color -->
  <Bell class="text-primary" />
  <Phone class="text-muted-foreground" />
</template>
```

Browse all icons: https://lucide.dev/icons

---

## Utility Function: `cn()`

Merge Tailwind classes properly:

```vue
<script setup>
import { cn } from '@/lib/utils'

const buttonClass = cn(
  'base-class',
  'another-class',
  condition && 'conditional-class',
  props.disabled && 'disabled-class'
)
</script>
```

---

## Dark Mode Classes

Elements automatically adapt in dark mode. Customize:

```vue
<!-- Light: white background, Dark: gray-900 -->
<div class="bg-white dark:bg-gray-900">

<!-- Light: blue-600, Dark: blue-400 -->
<p class="text-blue-600 dark:text-blue-400">

<!-- Responsive dark mode -->
<div class="bg-blue-100 dark:bg-blue-900">
```

---

## Tips

1. **Always use semantic color variables** (`bg-card`, `text-foreground`)
2. **Use consistent spacing** (4, 6, 8, 12, 16, 24 for px values)
3. **Make components responsive** (use `md:`, `lg:` prefixes)
4. **Keep accessibility in mind** (use proper labels, ARIA attributes)
5. **Test in dark mode** (if enabled)

---

## Adding More Components

To add more shadcn-vue components, visit:
https://www.shadcn-vue.com/docs/components

Copy the component code and adapt to your project structure in `resources/js/Components/ui/`.

