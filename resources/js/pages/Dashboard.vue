<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Eye, Package, TrendingUp, Users } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';

interface Order {
    id: number;
    order_number: string;
    customer_name: string;
    customer_email: string;
    product_name: string;
    product_color?: string;
    quantity: number;
    total_amount: number;
    currency: string;
    order_status: string;
    created_at: string;
}

interface Visitor {
    id: number;
    ip_address: string;
    country?: string;
    city?: string;
    device_type?: string;
    browser?: string;
    page_url?: string;
    created_at: string;
}

interface DeviceStat {
    device_type: string;
    count: number;
}

interface Props {
    stats: {
        totalOrders: number;
        totalRevenue: number;
        totalVisitors: number;
        totalVisits: number;
        todayVisitors: number;
        todayVisits: number;
    };
    recentOrders: Order[];
    deviceStats: DeviceStat[];
    recentVisitors: Visitor[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const formatCurrency = (amount: number, currency: string = 'USD') => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getStatusBadgeVariant = (status: string) => {
    switch (status.toLowerCase()) {
        case 'completed':
        case 'delivered':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'processing':
        case 'shipped':
            return 'outline';
        default:
            return 'secondary';
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
                    <p class="text-muted-foreground">
                        Welcome back! Here's what's happening with your store.
                    </p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Visitors</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.totalVisitors }}</div>
                        <p class="text-xs text-muted-foreground">
                            Unique visitors
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Visits</CardTitle>
                        <Eye class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.totalVisits }}</div>
                        <p class="text-xs text-muted-foreground">
                            All page views
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Orders</CardTitle>
                        <Package class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.totalOrders }}</div>
                        <p class="text-xs text-muted-foreground">
                            All time orders
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Revenue</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ formatCurrency(stats.totalRevenue) }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            From all orders
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Average Order</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">
                            {{ formatCurrency(stats.totalOrders > 0 ? stats.totalRevenue / stats.totalOrders : 0) }}
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Per order value
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Recent Orders</CardTitle>
                        <Package class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ recentOrders.length }}</div>
                        <p class="text-xs text-muted-foreground">
                            Last 5 orders
                        </p>
                    </CardContent>
                </Card>
            </div>

            <!-- Recent Orders Table -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Recent Orders</CardTitle>
                            <CardDescription>
                                Latest orders from your store
                            </CardDescription>
                        </div>
                        <Button as-child>
                            <Link href="/orders">
                                <Eye class="h-4 w-4 mr-2" />
                                View All
                            </Link>
                        </Button>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Order #</TableHead>
                                    <TableHead>Customer</TableHead>
                                    <TableHead>Product</TableHead>
                                    <TableHead>Total</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Date</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="order in recentOrders" :key="order.id">
                                    <TableCell class="font-medium">
                                        {{ order.order_number }}
                                    </TableCell>
                                    <TableCell>
                                        <div>
                                            <div class="font-medium">{{ order.customer_name }}</div>
                                            <div class="text-sm text-muted-foreground">{{ order.customer_email }}</div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div>
                                            <div class="font-medium">{{ order.product_name }}</div>
                                            <div v-if="order.product_color" class="text-sm text-muted-foreground">
                                                Color: {{ order.product_color }}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="font-medium">
                                        {{ formatCurrency(order.total_amount, order.currency) }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusBadgeVariant(order.order_status)">
                                            {{ order.order_status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ formatDate(order.created_at) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>

            <!-- Recent Visitors Table -->
            <Card>
                <CardHeader>
                    <div class="flex items-center justify-between">
                        <div>
                            <CardTitle>Recent Visitors</CardTitle>
                            <CardDescription>
                                Latest visitors to your website
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>IP Address</TableHead>
                                    <TableHead>Device</TableHead>
                                    <TableHead>Browser</TableHead>
                                    <TableHead>Page</TableHead>
                                    <TableHead>Visit Time</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="visitor in recentVisitors" :key="visitor.id">
                                    <TableCell class="font-medium">
                                        {{ visitor.ip_address }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="outline">
                                            {{ visitor.device_type || 'Unknown' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-sm">
                                        {{ visitor.browser || 'Unknown' }}
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground max-w-xs truncate">
                                        {{ visitor.page_url || '/' }}
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ formatDate(visitor.created_at) }}
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
