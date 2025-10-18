<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
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
import { Package, Plus, Edit, Trash2, Eye } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Product {
    id: number;
    name: string;
    description: string;
    price_before: number;
    price_after: number;
    quantity: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

interface Props {
    products: Product[];
}

const props = defineProps<Props>();

const breadcrumbs = [
    {
        title: 'Products',
        href: '/dashboard/products',
    },
];

const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(amount);
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getDiscountPercentage = (priceBefore: number, priceAfter: number) => {
    return Math.round(((priceBefore - priceAfter) / priceBefore) * 100);
};

const deleteProduct = (productId: number) => {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        router.delete(`/dashboard/products/${productId}`, {
            onSuccess: () => {
                // Product deleted successfully
            },
            onError: (errors) => {
                console.error('Error deleting product:', errors);
            }
        });
    }
};
</script>

<template>
    <Head title="Products" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Products</h1>
                    <p class="text-muted-foreground">
                        Manage your product inventory and pricing.
                    </p>
                </div>
                <Button as-child>
                    <Link href="/dashboard/products/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Product
                    </Link>
                </Button>
            </div>

            <!-- Products Table -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Package class="h-5 w-5" />
                        All Products
                    </CardTitle>
                    <CardDescription>
                        A list of all products in your store.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="rounded-md border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Product</TableHead>
                                    <TableHead>Price</TableHead>
                                    <TableHead>Discount</TableHead>
                                    <TableHead>Stock</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="product in products" :key="product.id">
                                    <TableCell class="font-medium">
                                        <div>
                                            <div class="font-semibold">{{ product.name }}</div>
                                            <div class="text-sm text-muted-foreground line-clamp-2">
                                                {{ product.description }}
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <div class="flex flex-col">
                                            <span class="font-semibold">{{ formatCurrency(product.price_after) }}</span>
                                            <span class="text-sm text-muted-foreground line-through">
                                                {{ formatCurrency(product.price_before) }}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {{ getDiscountPercentage(product.price_before, product.price_after) }}% off
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        <span :class="product.quantity > 0 ? 'text-green-600' : 'text-red-600'">
                                            {{ product.quantity }}
                                        </span>
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="product.is_active ? 'default' : 'secondary'">
                                            {{ product.is_active ? 'Active' : 'Inactive' }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {{ formatDate(product.created_at) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <Button variant="ghost" size="sm" as-child>
                                                <Link :href="`/product/${product.id}`">
                                                    <Eye class="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button variant="ghost" size="sm">
                                                <Edit class="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                class="text-red-600 hover:text-red-700"
                                                @click="deleteProduct(product.id)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </div>
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
