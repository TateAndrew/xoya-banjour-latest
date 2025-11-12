# Shadcn Dashboard Layout Guide

## Overview

Your Xoya Banjour application now features a modern SaaS dashboard layout built with shadcn-vue components. This guide will help you understand and work with the new layout system.

## What's New

### 1. **Modern Dashboard Layout** (`DashboardLayout.vue`)
   - Collapsible sidebar navigation
   - Top header with search and user menu
   - Responsive mobile menu
   - Clean, modern design system
   - Dark mode support (infrastructure ready)

### 2. **Shadcn-Vue Component Library**
   - Button
   - Card (with Header, Title, Description, Content)
   - Avatar (with Image and Fallback)
   - Badge
   - All components follow shadcn design principles

### 3. **Beautiful Icons**
   - Replaced with Lucide Vue icons
   - Consistent, professional look
   - Better accessibility

## File Structure

```
resources/js/
├── Components/
│   └── ui/
│       ├── Avatar.vue
│       ├── AvatarImage.vue
│       ├── AvatarFallback.vue
│       ├── Badge.vue
│       ├── Button.vue
│       ├── Card.vue
│       ├── CardHeader.vue
│       ├── CardTitle.vue
│       ├── CardDescription.vue
│       └── CardContent.vue
├── Layouts/
│   ├── DashboardLayout.vue (NEW)
│   ├── AuthenticatedLayout.vue (OLD)
│   └── GuestLayout.vue
├── lib/
│   └── utils.js (cn helper function)
└── Pages/
    └── Dashboard.vue (Updated)
```

## Using the Dashboard Layout

### Basic Usage

```vue
<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <Head title="My Page" />

  <DashboardLayout>
    <template #header>
      <h1 class="text-3xl font-bold">Page Title</h1>
      <p class="text-muted-foreground">Page description</p>
    </template>

    <!-- Your page content here -->
    <div>Content goes here</div>
  </DashboardLayout>
</template>
```

## Using Shadcn Components

### Button Component

```vue
<Button>Default Button</Button>
<Button variant="secondary">Secondary</Button>
<Button variant="outline">Outline</Button>
<Button variant="ghost">Ghost</Button>
<Button variant="destructive">Danger</Button>
<Button size="sm">Small</Button>
<Button size="lg">Large</Button>
```

### Card Component

```vue
<Card>
  <CardHeader>
    <CardTitle>Card Title</CardTitle>
    <CardDescription>Card description goes here</CardDescription>
  </CardHeader>
  <CardContent>
    <p>Your content here</p>
  </CardContent>
</Card>
```

### Avatar Component

```vue
<Avatar>
  <AvatarImage src="/path/to/image.jpg" />
  <AvatarFallback>JD</AvatarFallback>
</Avatar>
```

### Badge Component

```vue
<Badge>Default</Badge>
<Badge variant="secondary">Secondary</Badge>
<Badge variant="destructive">Danger</Badge>
<Badge variant="outline">Outline</Badge>
```

## Navigation Configuration

The sidebar navigation is configured in `DashboardLayout.vue`. There are four main sections:

### 1. Main Navigation
```javascript
const navigation = [
  { 
    name: 'Dashboard', 
    href: 'dashboard', 
    icon: LayoutDashboard,
    current: () => route().current('dashboard')
  },
  // Add more items...
]
```

### 2. Call Management
```javascript
const callManagement = [
  {
    name: 'Recordings',
    href: 'recordings.index',
    icon: FileAudio,
    current: () => route().current('recordings.*')
  },
  // Add more items...
]
```

### 3. Configuration
```javascript
const configuration = [
  {
    name: 'Phone Numbers',
    href: 'phone-numbers.index',
    icon: PhoneCall,
    current: () => route().current('phone-numbers.*')
  },
  // Add more items...
]
```

### 4. Administration
```javascript
const administration = [
  {
    name: 'Users',
    href: 'users.index',
    icon: Users,
    current: () => route().current('users.*')
  },
  // Add more items...
]
```

## Theming & Colors

The design system uses CSS custom properties for easy theming. Colors are defined in `resources/css/app.css`:

### Available Color Variables
- `--background` - Main background color
- `--foreground` - Main text color
- `--card` - Card background
- `--primary` - Primary brand color
- `--secondary` - Secondary color
- `--muted` - Muted elements
- `--accent` - Accent color
- `--destructive` - Error/danger color
- `--border` - Border color
- `--ring` - Focus ring color

### Dark Mode
Dark mode is ready to use. Toggle with:
```javascript
document.documentElement.classList.toggle('dark')
```

## Utility Function

The `cn()` function merges Tailwind classes properly:

```javascript
import { cn } from '@/lib/utils'

// Usage
const buttonClass = cn(
  'base-class',
  condition && 'conditional-class',
  props.className
)
```

## Responsive Design

The layout is fully responsive:
- **Mobile (<lg)**: Hamburger menu with drawer
- **Desktop (≥lg)**: Collapsible sidebar
- **Sidebar States**: 
  - Expanded: 288px (w-72)
  - Collapsed: 80px (w-20)

## Migrating Existing Pages

To migrate your existing pages to the new layout:

1. Replace `AuthenticatedLayout` with `DashboardLayout`:
```vue
// Old
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

// New
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
```

2. Update the header slot:
```vue
<!-- Old -->
<template #header>
  <h2>Title</h2>
</template>

<!-- New -->
<template #header>
  <div>
    <h1 class="text-3xl font-bold tracking-tight">Title</h1>
    <p class="text-muted-foreground">Description</p>
  </div>
</template>
```

3. Replace old components with shadcn components:
```vue
// Old
<div class="bg-white p-6 rounded-lg">Content</div>

// New
<Card>
  <CardHeader>
    <CardTitle>Title</CardTitle>
  </CardHeader>
  <CardContent>Content</CardContent>
</Card>
```

## Adding New Icons

Import from `lucide-vue-next`:

```vue
<script setup>
import { Home, Settings, User } from 'lucide-vue-next'
</script>

<template>
  <Home :size="20" />
  <Settings :size="16" class="text-muted-foreground" />
</template>
```

Browse all icons: https://lucide.dev/icons

## Best Practices

1. **Consistent Spacing**: Use the spacing scale (4px increments)
   ```vue
   <div class="p-4 gap-6">
   ```

2. **Use Semantic Colors**: Use color variables instead of hardcoded colors
   ```vue
   <!-- Good -->
   <div class="bg-card text-foreground">

   <!-- Avoid -->
   <div class="bg-white text-black">
   ```

3. **Responsive Design**: Always test mobile layouts
   ```vue
   <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
   ```

4. **Accessibility**: Use semantic HTML and proper ARIA labels
   ```vue
   <button aria-label="Close menu">
   ```

## Troubleshooting

### Build Errors
If you encounter build errors:
```bash
npm install
npm run build
```

### Missing Icons
Import the icon from lucide-vue-next:
```vue
import { IconName } from 'lucide-vue-next'
```

### Styling Issues
Check that Tailwind classes are properly configured and rebuild:
```bash
npm run build
```

## Resources

- **Shadcn Vue**: https://www.shadcn-vue.com/
- **Radix Vue**: https://www.radix-vue.com/
- **Lucide Icons**: https://lucide.dev/
- **Tailwind CSS**: https://tailwindcss.com/

## Next Steps

1. Migrate remaining pages to use `DashboardLayout`
2. Implement dark mode toggle
3. Add more shadcn-vue components as needed:
   - Dialog/Modal
   - Dropdown Menu
   - Select
   - Input
   - Tabs
   - And more!

## Support

For issues or questions about the dashboard layout, refer to:
- Shadcn-vue documentation
- This project's component source code
- Lucide icons documentation

