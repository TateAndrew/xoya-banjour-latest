<script setup>
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import Card from '@/Components/ui/Card.vue';
import CardHeader from '@/Components/ui/CardHeader.vue';
import CardTitle from '@/Components/ui/CardTitle.vue';
import CardDescription from '@/Components/ui/CardDescription.vue';
import CardContent from '@/Components/ui/CardContent.vue';
import Button from '@/Components/ui/Button.vue';
import Badge from '@/Components/ui/Badge.vue';
import Progress from '@/Components/ui/Progress.vue';
import Tabs from '@/Components/ui/Tabs.vue';
import TabsList from '@/Components/ui/TabsList.vue';
import TabsTrigger from '@/Components/ui/TabsTrigger.vue';
import TabsContent from '@/Components/ui/TabsContent.vue';
import StatsCard from '@/Components/dashboard/StatsCard.vue';
import RecentActivity from '@/Components/dashboard/RecentActivity.vue';
import { ref } from 'vue';
import { 
  Users, 
  Phone, 
  MessageSquare, 
  Video,
  PhoneCall,
  TrendingUp,
  Activity,
  Clock,
  ArrowUpRight,
  Target,
  DollarSign
} from 'lucide-vue-next';

const activeTab = ref('overview');

const recentActivities = [
  {
    id: 1,
    user: 'John Doe',
    action: 'Made an outbound call to +1 234-567-8900',
    time: new Date(Date.now() - 1000 * 60 * 5) // 5 minutes ago
  },
  {
    id: 2,
    user: 'Jane Smith',
    action: 'Sent SMS message to contact',
    time: new Date(Date.now() - 1000 * 60 * 15) // 15 minutes ago
  },
  {
    id: 3,
    user: 'Mike Johnson',
    action: 'Started a video conference',
    time: new Date(Date.now() - 1000 * 60 * 30) // 30 minutes ago
  },
  {
    id: 4,
    user: 'Sarah Williams',
    action: 'Purchased a new phone number',
    time: new Date(Date.now() - 1000 * 60 * 45) // 45 minutes ago
  },
  {
    id: 5,
    user: 'Tom Brown',
    action: 'Updated SIP trunk configuration',
    time: new Date(Date.now() - 1000 * 60 * 60) // 1 hour ago
  }
];
</script>

<template>
    <Head title="Dashboard" />

    <DashboardLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                    <p class="text-muted-foreground">Welcome back! Here's what's happening today.</p>
                </div>
                <Badge variant="secondary">
                    <Activity :size="14" class="mr-1" />
                    All Systems Operational
                </Badge>
            </div>
        </template>

        <!-- Tabs Navigation -->
        <Tabs v-model="activeTab" class="space-y-6">
            <TabsList>
                <TabsTrigger value="overview">Overview</TabsTrigger>
                <TabsTrigger value="analytics">Analytics</TabsTrigger>
                <TabsTrigger value="quick-actions">Quick Actions</TabsTrigger>
            </TabsList>

            <!-- Overview Tab -->
            <TabsContent value="overview" class="space-y-6">
                <!-- Stats Grid -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <StatsCard
                        title="Total Calls"
                        :value="1284"
                        :icon="Phone"
                        change="+12.5%"
                        description="from last month"
                        change-type="increase"
                    />
                    
                    <StatsCard
                        title="Active Users"
                        :value="42"
                        :icon="Users"
                        change="+8"
                        description="from last week"
                        change-type="increase"
                    />
                    
                    <StatsCard
                        title="Messages Sent"
                        value="3,621"
                        :icon="MessageSquare"
                        change="+18.2%"
                        description="from last month"
                        change-type="increase"
                    />
                    
                    <StatsCard
                        title="Avg. Call Duration"
                        value="4m 32s"
                        :icon="Clock"
                        change="+2.3%"
                        description="from last month"
                        change-type="increase"
                    />
                </div>

                <!-- Activity & Goals -->
                <div class="grid gap-6 md:grid-cols-2">
                    <RecentActivity :activities="recentActivities" />
                    
                    <!-- Monthly Goals -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Monthly Goals</CardTitle>
                            <CardDescription>Track your progress this month</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">Calls Made</span>
                                    <span class="text-muted-foreground">1,284 / 2,000</span>
                                </div>
                                <Progress :model-value="64" />
                            </div>
                            
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">Messages Sent</span>
                                    <span class="text-muted-foreground">3,621 / 5,000</span>
                                </div>
                                <Progress :model-value="72" />
                            </div>
                            
                            <div class="space-y-2">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium">Video Calls</span>
                                    <span class="text-muted-foreground">156 / 200</span>
                                </div>
                                <Progress :model-value="78" />
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </TabsContent>

            <!-- Analytics Tab -->
            <TabsContent value="analytics" class="space-y-6">
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <StatsCard
                        title="Revenue"
                        value="$12,345"
                        :icon="DollarSign"
                        change="+15.3%"
                        description="from last month"
                        change-type="increase"
                    />
                    
                    <StatsCard
                        title="Conversion Rate"
                        value="3.24%"
                        :icon="Target"
                        change="+0.8%"
                        description="from last week"
                        change-type="increase"
                    />
                    
                    <StatsCard
                        title="Total Users"
                        value="2,847"
                        :icon="Users"
                        change="+127"
                        description="from last month"
                        change-type="increase"
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Analytics Overview</CardTitle>
                        <CardDescription>Detailed performance metrics</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            Analytics dashboard coming soon. This will display charts and graphs showing your usage patterns, peak hours, and performance metrics.
                        </p>
                    </CardContent>
                </Card>
            </TabsContent>

            <!-- Quick Actions Tab -->
            <TabsContent value="quick-actions" class="space-y-6">

                <!-- Quick Actions Grid -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            <!-- User Management Card -->
            <Card class="hover:shadow-lg transition-shadow">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                            <Users class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <CardTitle>User Management</CardTitle>
                            <CardDescription>Manage users, roles, and permissions</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground mb-4">
                        Control access and manage your team members effectively.
                    </p>
                    <Link :href="route('users.index')">
                        <Button class="w-full">
                            Manage Users
                            <ArrowUpRight :size="16" class="ml-2" />
                        </Button>
                    </Link>
                </CardContent>
            </Card>

            <!-- Phone Numbers Card -->
            <Card class="hover:shadow-lg transition-shadow">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                            <PhoneCall class="h-6 w-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <CardTitle>Phone Numbers</CardTitle>
                            <CardDescription>Manage your phone numbers</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground mb-4">
                        Purchase, configure, and manage your phone numbers.
                    </p>
                    <Link :href="route('phone-numbers.index')">
                        <Button variant="secondary" class="w-full">
                            View Numbers
                            <ArrowUpRight :size="16" class="ml-2" />
                        </Button>
                    </Link>
                </CardContent>
            </Card>

            <!-- Messaging Card -->
            <Card class="hover:shadow-lg transition-shadow">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
                            <MessageSquare class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <CardTitle>Messaging</CardTitle>
                            <CardDescription>SMS and messaging profiles</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground mb-4">
                        Configure messaging settings and view conversations.
                    </p>
                    <Link :href="route('messenger.index')">
                        <Button variant="outline" class="w-full">
                            Open Messenger
                            <ArrowUpRight :size="16" class="ml-2" />
                        </Button>
                    </Link>
                </CardContent>
            </Card>

            <!-- Video Calls Card -->
            <Card class="hover:shadow-lg transition-shadow">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-orange-100 dark:bg-orange-900 rounded-lg">
                            <Video class="h-6 w-6 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div>
                            <CardTitle>Video Calls</CardTitle>
                            <CardDescription>Start or join video meetings</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground mb-4">
                        High-quality video conferencing powered by Jitsi.
                    </p>
                    <Link :href="route('video-calls.index')">
                        <Button variant="outline" class="w-full">
                            Start Video Call
                            <ArrowUpRight :size="16" class="ml-2" />
                        </Button>
                    </Link>
                </CardContent>
            </Card>

            <!-- Dialer Card -->
            <Card class="hover:shadow-lg transition-shadow">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-indigo-100 dark:bg-indigo-900 rounded-lg">
                            <Phone class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div>
                            <CardTitle>Dialer</CardTitle>
                            <CardDescription>Make outbound calls</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground mb-4">
                        Make calls directly from your browser.
                    </p>
                    <Link :href="route('dialer')">
                        <Button variant="outline" class="w-full">
                            Open Dialer
                            <ArrowUpRight :size="16" class="ml-2" />
                        </Button>
                    </Link>
                </CardContent>
            </Card>

            <!-- SIP Trunks Card -->
            <Card class="hover:shadow-lg transition-shadow">
                <CardHeader>
                    <div class="flex items-center gap-2">
                        <div class="p-2 bg-pink-100 dark:bg-pink-900 rounded-lg">
                            <Activity class="h-6 w-6 text-pink-600 dark:text-pink-400" />
                        </div>
                        <div>
                            <CardTitle>SIP Trunks</CardTitle>
                            <CardDescription>Configure SIP trunks</CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground mb-4">
                        Manage your SIP trunk configurations and settings.
                    </p>
                    <Link :href="route('sip-trunks.index')">
                        <Button variant="outline" class="w-full">
                            Manage Trunks
                            <ArrowUpRight :size="16" class="ml-2" />
                        </Button>
                    </Link>
                </CardContent>
            </Card>
                </div>
            </TabsContent>
        </Tabs>
    </DashboardLayout>
</template>
