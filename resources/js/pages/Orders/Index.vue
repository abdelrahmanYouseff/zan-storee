<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Eye, Package, TrendingUp } from 'lucide-vue-next';

interface Order {
    id: number;
    order_number: string;
    customer_name: string;
    customer_email: string;
    product_name: string;
    product_color?: string;
    quantity: number;
    unit_price: number;
    total_amount: number;
    currency: string;
    payment_status: string;
    order_status: string;
    created_at: string;
    user?: {
        name: string;
        email: string;
    };
}

interface Props {
    orders: {
        data: Order[];
        links: any[];
        meta: any;
    };
    stats: {
        totalOrders: number;
        totalRevenue: number;
    };
}

const props = defineProps<Props>();

const getStatusBadgeVariant = (status: string) => {
    switch (status.toLowerCase()) {
        case 'completed':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'processing':
            return 'outline';
        case 'shipped':
            return 'default';
        case 'delivered':
            return 'default';
        default:
            return 'secondary';
    }
};

const getPaymentStatusBadgeVariant = (status: string) => {
    switch (status.toLowerCase()) {
        case 'completed':
            return 'default';
        case 'pending':
            return 'secondary';
        case 'failed':
            return 'destructive';
        default:
            return 'secondary';
    }
};

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
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head title="Orders" />

    <AppSidebarLayout>
        <template #breadcrumbs>
            <div class="flex items-center space-x-2 text-sm text-muted-foreground">
                <span>Dashboard</span>
                <span>/</span>
                <span class="text-foreground">Orders</span>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Orders</h1>
                    <p class="text-muted-foreground">
                        Manage and track customer orders
                    </p>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
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
            </div>

            <!-- Orders Table -->
            <Card>
                <CardHeader>
                    <CardTitle>Recent Orders</CardTitle>
                    <CardDescription>
                        A list of all orders in your store
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Order #</TableHead>
                                    <TableHead>Customer</TableHead>
                                    <TableHead>Product</TableHead>
                                    <TableHead>Quantity</TableHead>
                                    <TableHead>Total</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Payment</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="order in orders.data" :key="order.id">
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
                                    <TableCell>{{ order.quantity }}</TableCell>
                                    <TableCell class="font-medium">
                                        {{ formatCurrency(order.total_amount, order.currency) }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusBadgeVariant(order.order_status)">
                                            {{ order.order_status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getPaymentStatusBadgeVariant(order.payment_status)">
                                            {{ order.payment_status }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell class="text-sm text-muted-foreground">
                                        {{ formatDate(order.created_at) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <Button variant="outline" size="sm">
                                            <Eye class="h-4 w-4 mr-2" />
                                            View
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppSidebarLayout>
</template>
