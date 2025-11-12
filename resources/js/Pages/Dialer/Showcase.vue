<script setup>
import { Head, Link } from '@inertiajs/vue3'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import Card from '@/Components/ui/Card.vue'
import CardHeader from '@/Components/ui/CardHeader.vue'
import CardTitle from '@/Components/ui/CardTitle.vue'
import CardDescription from '@/Components/ui/CardDescription.vue'
import CardContent from '@/Components/ui/CardContent.vue'
import Button from '@/Components/ui/Button.vue'
import Badge from '@/Components/ui/Badge.vue'
import { Phone, Sparkles, ArrowRight, Check } from 'lucide-vue-next'
</script>

<template>
  <Head title="Dialer Design Showcase" />

  <DashboardLayout>
    <template #header>
      <div>
        <h1 class="text-3xl font-bold tracking-tight">Dialer Design Showcase</h1>
        <p class="text-muted-foreground">Compare the original and modern dialer interfaces</p>
      </div>
    </template>

    <div class="space-y-8">
      <!-- Introduction -->
      <Card>
        <CardHeader>
          <CardTitle class="flex items-center gap-2">
            <Sparkles :size="24" class="text-yellow-500" />
            New Modern Design Available!
          </CardTitle>
          <CardDescription>
            Experience the redesigned dialer with modern components, improved UX, and beautiful animations
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-4">
              <h3 class="text-lg font-semibold">Key Improvements</h3>
              <ul class="space-y-3">
                <li class="flex items-start gap-3">
                  <Check :size="20" class="text-green-600 mt-0.5 flex-shrink-0" />
                  <div>
                    <p class="font-medium">Tabbed Interface</p>
                    <p class="text-sm text-muted-foreground">Organized into Dialer, Keypad, and Settings tabs</p>
                  </div>
                </li>
                <li class="flex items-start gap-3">
                  <Check :size="20" class="text-green-600 mt-0.5 flex-shrink-0" />
                  <div>
                    <p class="font-medium">Visual Status Indicators</p>
                    <p class="text-sm text-muted-foreground">Animated call status with color-coded badges</p>
                  </div>
                </li>
                <li class="flex items-start gap-3">
                  <Check :size="20" class="text-green-600 mt-0.5 flex-shrink-0" />
                  <div>
                    <p class="font-medium">Modern Components</p>
                    <p class="text-sm text-muted-foreground">Built with shadcn-vue for consistency</p>
                  </div>
                </li>
                <li class="flex items-start gap-3">
                  <Check :size="20" class="text-green-600 mt-0.5 flex-shrink-0" />
                  <div>
                    <p class="font-medium">Improved Keypad</p>
                    <p class="text-sm text-muted-foreground">Full-featured numeric keypad with letter labels</p>
                  </div>
                </li>
                <li class="flex items-start gap-3">
                  <Check :size="20" class="text-green-600 mt-0.5 flex-shrink-0" />
                  <div>
                    <p class="font-medium">Better Recent Calls</p>
                    <p class="text-sm text-muted-foreground">Rich cards with hover effects and quick actions</p>
                  </div>
                </li>
                <li class="flex items-start gap-3">
                  <Check :size="20" class="text-green-600 mt-0.5 flex-shrink-0" />
                  <div>
                    <p class="font-medium">Dark Mode Ready</p>
                    <p class="text-sm text-muted-foreground">Fully supports dark theme out of the box</p>
                  </div>
                </li>
              </ul>
            </div>

            <div class="space-y-4">
              <h3 class="text-lg font-semibold">Design Highlights</h3>
              <div class="space-y-3">
                <div class="p-4 rounded-lg border bg-card">
                  <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center">
                      <Phone :size="20" class="text-green-600" />
                    </div>
                    <div>
                      <p class="font-medium">Status Animations</p>
                      <Badge variant="secondary" class="text-xs">New</Badge>
                    </div>
                  </div>
                  <p class="text-sm text-muted-foreground">Pulsing animations during calls, color-coded states</p>
                </div>

                <div class="p-4 rounded-lg border bg-card">
                  <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center">
                      <Sparkles :size="20" class="text-blue-600" />
                    </div>
                    <div>
                      <p class="font-medium">Modern UI</p>
                      <Badge variant="secondary" class="text-xs">Improved</Badge>
                    </div>
                  </div>
                  <p class="text-sm text-muted-foreground">Clean cards, consistent spacing, professional look</p>
                </div>

                <div class="p-4 rounded-lg border bg-card">
                  <div class="flex items-center gap-3 mb-2">
                    <div class="w-10 h-10 rounded-full bg-purple-100 dark:bg-purple-900 flex items-center justify-center">
                      <ArrowRight :size="20" class="text-purple-600" />
                    </div>
                    <div>
                      <p class="font-medium">Better UX</p>
                      <Badge variant="secondary" class="text-xs">Enhanced</Badge>
                    </div>
                  </div>
                  <p class="text-sm text-muted-foreground">Intuitive navigation, clear call-to-actions</p>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Version Comparison -->
      <div class="grid md:grid-cols-2 gap-6">
        <!-- Original Dialer -->
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <div>
                <CardTitle>Original Dialer</CardTitle>
                <CardDescription>Current functional WebRTC dialer</CardDescription>
              </div>
              <Badge variant="outline">Current</Badge>
            </div>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-900 rounded-lg flex items-center justify-center">
              <Phone :size="48" class="text-muted-foreground" />
            </div>
            <div class="space-y-2">
              <h4 class="font-semibold">Features:</h4>
              <ul class="text-sm space-y-1 text-muted-foreground">
                <li>• WebRTC calling functionality</li>
                <li>• Basic call controls</li>
                <li>• Recent calls sidebar</li>
                <li>• Settings panel</li>
                <li>• Transcription support</li>
              </ul>
            </div>
            <Link :href="route('dialer')">
              <Button variant="outline" class="w-full">
                View Original
                <ArrowRight :size="16" class="ml-2" />
              </Button>
            </Link>
          </CardContent>
        </Card>

        <!-- Modern Dialer -->
        <Card class="border-2 border-primary">
          <CardHeader>
            <div class="flex items-center justify-between">
              <div>
                <CardTitle class="flex items-center gap-2">
                  Modern Dialer
                  <Sparkles :size="20" class="text-yellow-500" />
                </CardTitle>
                <CardDescription>Redesigned with modern components</CardDescription>
              </div>
              <Badge>New</Badge>
            </div>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="aspect-video bg-gradient-to-br from-blue-100 to-purple-200 dark:from-blue-900 dark:to-purple-900 rounded-lg flex items-center justify-center">
              <div class="text-center">
                <Phone :size="48" class="text-primary mx-auto mb-2" />
                <Badge variant="secondary">Modern UI</Badge>
              </div>
            </div>
            <div class="space-y-2">
              <h4 class="font-semibold">Enhanced Features:</h4>
              <ul class="text-sm space-y-1 text-muted-foreground">
                <li>• All original features included</li>
                <li>• Tabbed interface (Dialer/Keypad/Settings)</li>
                <li>• Animated status indicators</li>
                <li>• Modern shadcn-vue components</li>
                <li>• Improved visual hierarchy</li>
                <li>• Better touch targets</li>
                <li>• Dark mode optimized</li>
              </ul>
            </div>
            <Link :href="route('dialer.modern')">
              <Button class="w-full">
                Try Modern Version
                <ArrowRight :size="16" class="ml-2" />
              </Button>
            </Link>
          </CardContent>
        </Card>
      </div>

      <!-- Feature Comparison Table -->
      <Card>
        <CardHeader>
          <CardTitle>Feature Comparison</CardTitle>
          <CardDescription>Detailed comparison of both versions</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b">
                  <th class="text-left py-3 px-4 font-semibold">Feature</th>
                  <th class="text-center py-3 px-4 font-semibold">Original</th>
                  <th class="text-center py-3 px-4 font-semibold">Modern</th>
                </tr>
              </thead>
              <tbody class="divide-y">
                <tr>
                  <td class="py-3 px-4">WebRTC Calling</td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                </tr>
                <tr>
                  <td class="py-3 px-4">Call Controls (Mute/Hold/Speaker)</td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                </tr>
                <tr>
                  <td class="py-3 px-4">Recent Calls</td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                </tr>
                <tr class="bg-green-50 dark:bg-green-900/20">
                  <td class="py-3 px-4 font-medium">Tabbed Interface</td>
                  <td class="text-center py-3 px-4 text-muted-foreground">—</td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                </tr>
                <tr class="bg-green-50 dark:bg-green-900/20">
                  <td class="py-3 px-4 font-medium">Animated Status Indicators</td>
                  <td class="text-center py-3 px-4 text-muted-foreground">—</td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                </tr>
                <tr class="bg-green-50 dark:bg-green-900/20">
                  <td class="py-3 px-4 font-medium">Modern shadcn Components</td>
                  <td class="text-center py-3 px-4 text-muted-foreground">—</td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                </tr>
                <tr class="bg-green-50 dark:bg-green-900/20">
                  <td class="py-3 px-4 font-medium">Enhanced Visual Feedback</td>
                  <td class="text-center py-3 px-4 text-muted-foreground">—</td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                </tr>
                <tr class="bg-green-50 dark:bg-green-900/20">
                  <td class="py-3 px-4 font-medium">Full-featured Keypad Tab</td>
                  <td class="text-center py-3 px-4 text-muted-foreground">—</td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                </tr>
                <tr>
                  <td class="py-3 px-4">Dark Mode Support</td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                </tr>
                <tr>
                  <td class="py-3 px-4">Transcription</td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                  <td class="text-center py-3 px-4"><Check :size="20" class="text-green-600 mx-auto" /></td>
                </tr>
              </tbody>
            </table>
          </div>
        </CardContent>
      </Card>

      <!-- Call to Action -->
      <Card class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-950 dark:to-purple-950 border-2">
        <CardContent class="pt-6">
          <div class="text-center space-y-4">
            <Sparkles :size="48" class="text-yellow-500 mx-auto" />
            <h2 class="text-2xl font-bold">Ready to Experience the Modern Dialer?</h2>
            <p class="text-muted-foreground max-w-2xl mx-auto">
              Try the redesigned interface with improved UX, modern components, and beautiful animations.
              All your existing functionality is preserved with a fresh, professional look.
            </p>
            <div class="flex flex-wrap justify-center gap-4 pt-4">
              <Link :href="route('dialer.modern')">
                <Button size="lg" class="gap-2">
                  <Sparkles :size="20" />
                  Try Modern Dialer
                </Button>
              </Link>
              <Link :href="route('dialer')">
                <Button variant="outline" size="lg" class="gap-2">
                  <Phone :size="20" />
                  View Original
                </Button>
              </Link>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  </DashboardLayout>
</template>

