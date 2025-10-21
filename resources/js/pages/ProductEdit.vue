<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ArrowLeft, Save } from 'lucide-vue-next';
import { ref } from 'vue';

interface Product {
    id: number;
    name: string;
    description: string;
    main_image: string;
    secondary_images: string[];
    features: string[];
    colors: string[];
    quantity: number;
    price_before: number;
    price_after: number;
    is_active: boolean;
    paypal_full_payment_url?: string;
    paypal_cod_payment_url?: string;
}

const props = defineProps<{
    product: Product;
}>();

const form = useForm({
    name: props.product.name,
    description: props.product.description,
    main_image: null,
    secondary_images: [],
    features: props.product.features || [],
    colors: props.product.colors || [],
    quantity: props.product.quantity,
    price_before: props.product.price_before,
    price_after: props.product.price_after,
    is_active: props.product.is_active,
    paypal_full_payment_url: props.product.paypal_full_payment_url || '',
    paypal_cod_payment_url: props.product.paypal_cod_payment_url || '',
    _method: 'PUT'
});

const mainImagePreview = ref(props.product.main_image);
const secondaryImagesPreviews = ref(props.product.secondary_images || []);
const featuresInput = ref('');
const colorsInput = ref('');

const handleMainImageUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.main_image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            mainImagePreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const handleSecondaryImagesUpload = (event) => {
    const files = Array.from(event.target.files);
    files.forEach((file) => {
        form.secondary_images.push(file);
        const reader = new FileReader();
        reader.onload = (e) => {
            secondaryImagesPreviews.value.push(e.target.result);
        };
        reader.readAsDataURL(file);
    });
};

const removeSecondaryImage = (index: number) => {
    form.secondary_images.splice(index, 1);
    secondaryImagesPreviews.value.splice(index, 1);
};

const addFeature = () => {
    if (featuresInput.value.trim()) {
        form.features.push(featuresInput.value.trim());
        featuresInput.value = '';
    }
};

const removeFeature = (index: number) => {
    form.features.splice(index, 1);
};

const addColor = () => {
    if (colorsInput.value.trim()) {
        form.colors.push(colorsInput.value.trim());
        colorsInput.value = '';
    }
};

const removeColor = (index: number) => {
    form.colors.splice(index, 1);
};

const submit = () => {
    form.post(`/dashboard/products/${props.product.id}`, {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Edit Product" />

    <AppLayout>
        <div class="container mx-auto p-6">
            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold">Edit Product</h1>
                    <p class="text-muted-foreground mt-1">Update product information</p>
                </div>
                <Button variant="outline" as="a" href="/dashboard/products">
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Back to Products
                </Button>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit">
                <div class="grid gap-6">
                    <!-- Basic Information -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Basic Information</CardTitle>
                            <CardDescription>Update the basic details of the product</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="name">Product Name *</Label>
                                <Input id="name" v-model="form.name" required />
                                <span v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</span>
                            </div>

                            <div class="space-y-2">
                                <Label for="description">Description *</Label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    required
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                <span v-if="form.errors.description" class="text-sm text-red-600">{{ form.errors.description }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Images -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Images</CardTitle>
                            <CardDescription>Upload new images or keep existing ones (JPG, PNG, GIF, WEBP - Max 2MB)</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="main_image">Main Image (Leave empty to keep current)</Label>
                                <input
                                    id="main_image"
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                    @change="handleMainImageUpload"
                                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                <span v-if="form.errors.main_image" class="text-sm text-red-600">{{ form.errors.main_image }}</span>

                                <!-- Current/New Main Image Preview -->
                                <div v-if="mainImagePreview" class="mt-2">
                                    <p class="text-sm text-muted-foreground mb-1">Current Image:</p>
                                    <img :src="mainImagePreview" alt="Main preview" class="w-32 h-32 object-cover rounded-md border" />
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="secondary_images">Add More Secondary Images (Multiple)</Label>
                                <input
                                    id="secondary_images"
                                    type="file"
                                    accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
                                    @change="handleSecondaryImagesUpload"
                                    multiple
                                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                />

                                <!-- Current Secondary Images Previews -->
                                <div v-if="secondaryImagesPreviews.length" class="mt-2">
                                    <p class="text-sm text-muted-foreground mb-2">Current Images:</p>
                                    <div class="grid grid-cols-4 gap-2">
                                        <div v-for="(preview, index) in secondaryImagesPreviews" :key="index" class="relative">
                                            <img :src="preview" alt="Secondary preview" class="w-full h-24 object-cover rounded-md border" />
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                size="sm"
                                                class="absolute top-1 right-1 h-6 w-6 p-0"
                                                @click="removeSecondaryImage(index)"
                                            >
                                                ×
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Pricing & Inventory -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Pricing & Inventory</CardTitle>
                            <CardDescription>Set product prices and stock quantity</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <Label for="price_before">Price Before *</Label>
                                    <Input id="price_before" type="number" step="0.01" v-model="form.price_before" required />
                                    <span v-if="form.errors.price_before" class="text-sm text-red-600">{{ form.errors.price_before }}</span>
                                </div>

                                <div class="space-y-2">
                                    <Label for="price_after">Price After *</Label>
                                    <Input id="price_after" type="number" step="0.01" v-model="form.price_after" required />
                                    <span v-if="form.errors.price_after" class="text-sm text-red-600">{{ form.errors.price_after }}</span>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label for="quantity">Quantity *</Label>
                                <Input id="quantity" type="number" v-model="form.quantity" required />
                                <span v-if="form.errors.quantity" class="text-sm text-red-600">{{ form.errors.quantity }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Payment Links -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Payment Links</CardTitle>
                            <CardDescription>Add PayPal payment URLs for different payment methods</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label for="paypal_full_payment_url">Full Payment URL</Label>
                                <Input
                                    id="paypal_full_payment_url"
                                    type="url"
                                    v-model="form.paypal_full_payment_url"
                                    placeholder="https://www.paypal.com/ncp/payment/..."
                                />
                                <span v-if="form.errors.paypal_full_payment_url" class="text-sm text-red-600">{{ form.errors.paypal_full_payment_url }}</span>
                                <p class="text-sm text-gray-500">URL for customers who want to pay the full amount immediately</p>
                            </div>

                            <div class="space-y-2">
                                <Label for="paypal_cod_payment_url">Cash on Delivery Payment URL</Label>
                                <Input
                                    id="paypal_cod_payment_url"
                                    type="url"
                                    v-model="form.paypal_cod_payment_url"
                                    placeholder="https://www.paypal.com/ncp/payment/..."
                                />
                                <span v-if="form.errors.paypal_cod_payment_url" class="text-sm text-red-600">{{ form.errors.paypal_cod_payment_url }}</span>
                                <p class="text-sm text-gray-500">URL for customers who want to pay shipping fee now and full amount on delivery</p>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Additional Details -->
                    <Card>
                        <CardHeader>
                            <CardTitle>Additional Details</CardTitle>
                            <CardDescription>Add features and colors</CardDescription>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-2">
                                <Label>Features</Label>
                                <div class="flex gap-2">
                                    <Input v-model="featuresInput" placeholder="Enter a feature" @keyup.enter="addFeature" />
                                    <Button type="button" @click="addFeature">Add</Button>
                                </div>
                                <div v-if="form.features.length" class="mt-2 space-y-1">
                                    <div v-for="(feature, index) in form.features" :key="index" class="flex items-center justify-between bg-muted p-2 rounded">
                                        <span class="text-sm">{{ feature }}</span>
                                        <Button type="button" variant="ghost" size="sm" @click="removeFeature(index)">Remove</Button>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label>Colors</Label>
                                <div class="flex gap-2">
                                    <Input v-model="colorsInput" placeholder="Enter a color" @keyup.enter="addColor" />
                                    <Button type="button" @click="addColor">Add</Button>
                                </div>
                                <div v-if="form.colors.length" class="mt-2 space-y-1">
                                    <div v-for="(color, index) in form.colors" :key="index" class="flex items-center justify-between bg-muted p-2 rounded">
                                        <span class="text-sm">{{ color }}</span>
                                        <Button type="button" variant="ghost" size="sm" @click="removeColor(index)">Remove</Button>
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <input type="checkbox" id="is_active" v-model="form.is_active" class="rounded" />
                                <Label for="is_active">Product is active</Label>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Submit Button -->
                    <div class="flex justify-end gap-4">
                        <Button type="button" variant="outline" as="a" href="/dashboard/products">
                            Cancel
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Save class="h-4 w-4 mr-2" />
                            Update Product
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

