<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
    Users,
    Eye,
    TrendingUp,
    Globe,
    Search,
    Calendar,
    MapPin,
    Monitor
} from 'lucide-vue-next';

interface Visitor {
    id: number;
    ip_address: string;
    country: string;
    country_code: string;
    city: string;
    region: string;
    user_agent: string;
    page_visited: string;
    referrer: string;
    created_at: string;
}

interface Stats {
    total_visitors: number;
    total_visits: number;
    today_visitors: number;
    today_visits: number;
}

interface TopCountry {
    country: string;
    country_code: string;
    visits: number;
}

interface Props {
    visitors: {
        data: Visitor[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    stats: Stats;
    topCountries: TopCountry[];
    filters: {
        search: string;
        country: string;
        per_page: number;
    };
}

const props = defineProps<Props>();

const searchQuery = ref(props.filters.search || '');
const selectedCountry = ref(props.filters.country || '');

const search = () => {
    router.get('/dashboard/visitors', {
        search: searchQuery.value,
        country: selectedCountry.value,
        per_page: props.filters.per_page,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedCountry.value = '';
    router.get('/dashboard/visitors');
};

const filterByCountry = (country: string) => {
    selectedCountry.value = country;
    search();
};

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getCountryFlag = (countryCode: string) => {
    if (!countryCode || countryCode === 'LC') return '🌐';
    return String.fromCodePoint(...[...countryCode.toUpperCase()].map(c => c.charCodeAt(0) + 127397));
};

const truncateUrl = (url: string, maxLength: number = 50) => {
    if (!url) return '-';
    return url.length > maxLength ? url.substring(0, maxLength) + '...' : url;
};

const changePage = (page: number) => {
    router.get('/dashboard/visitors', {
        page,
        search: searchQuery.value,
        country: selectedCountry.value,
        per_page: props.filters.per_page,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Visitors Analytics" />

        <div class="p-6 space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Visitors Analytics</h1>
                    <p class="text-gray-500 dark:text-gray-400 mt-1">Track and analyze your website visitors</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Visitors</CardTitle>
                        <Users class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_visitors.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Unique IP addresses</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Total Visits</CardTitle>
                        <Eye class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.total_visits.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground mt-1">All page views</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Today's Visitors</CardTitle>
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.today_visitors.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Unique today</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle class="text-sm font-medium">Today's Visits</CardTitle>
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                    </CardHeader>
                    <CardContent>
                        <div class="text-2xl font-bold">{{ stats.today_visits.toLocaleString() }}</div>
                        <p class="text-xs text-muted-foreground mt-1">Page views today</p>
                    </CardContent>
                </Card>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Visitors Table -->
                <div class="lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Visitors</CardTitle>
                            <CardDescription>View all visitors and their locations</CardDescription>

                            <!-- Search and Filters -->
                            <div class="flex gap-2 mt-4">
                                <div class="relative flex-1">
                                    <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        v-model="searchQuery"
                                        placeholder="Search by IP, country, city..."
                                        class="pl-9"
                                        @keyup.enter="search"
                                    />
                                </div>
                                <Button @click="search" variant="default">
                                    Search
                                </Button>
                                <Button @click="clearFilters" variant="outline">
                                    Clear
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="overflow-x-auto">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>IP Address</TableHead>
                                            <TableHead>Location</TableHead>
                                            <TableHead>Page</TableHead>
                                            <TableHead>Time</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow v-for="visitor in visitors.data" :key="visitor.id">
                                            <TableCell class="font-mono text-sm">
                                                {{ visitor.ip_address }}
                                            </TableCell>
                                            <TableCell>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-2xl">{{ getCountryFlag(visitor.country_code) }}</span>
                                                    <div>
                                                        <div class="font-medium">{{ visitor.country || 'Unknown' }}</div>
                                                        <div class="text-xs text-gray-500">
                                                            {{ visitor.city || 'Unknown' }}{{ visitor.region ? ', ' + visitor.region : '' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <a
                                                    :href="visitor.page_visited"
                                                    target="_blank"
                                                    class="text-blue-600 hover:underline text-sm"
                                                    :title="visitor.page_visited"
                                                >
                                                    {{ truncateUrl(visitor.page_visited) }}
                                                </a>
                                            </TableCell>
                                            <TableCell class="text-sm text-gray-500">
                                                {{ formatDate(visitor.created_at) }}
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>

                            <!-- Pagination -->
                            <div v-if="visitors.last_page > 1" class="flex items-center justify-between mt-4">
                                <div class="text-sm text-gray-500">
                                    Showing {{ ((visitors.current_page - 1) * visitors.per_page) + 1 }} to
                                    {{ Math.min(visitors.current_page * visitors.per_page, visitors.total) }} of
                                    {{ visitors.total }} results
                                </div>
                                <div class="flex gap-2">
                                    <Button
                                        @click="changePage(visitors.current_page - 1)"
                                        :disabled="visitors.current_page === 1"
                                        variant="outline"
                                        size="sm"
                                    >
                                        Previous
                                    </Button>
                                    <Button
                                        @click="changePage(visitors.current_page + 1)"
                                        :disabled="visitors.current_page === visitors.last_page"
                                        variant="outline"
                                        size="sm"
                                    >
                                        Next
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Top Countries Sidebar -->
                <div>
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2">
                                <Globe class="h-5 w-5" />
                                Top Countries
                            </CardTitle>
                            <CardDescription>Most visits by country</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="space-y-3">
                                <div
                                    v-for="country in topCountries"
                                    :key="country.country"
                                    class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                                    @click="filterByCountry(country.country)"
                                >
                                    <div class="flex items-center gap-3">
                                        <span class="text-2xl">{{ getCountryFlag(country.country_code) }}</span>
                                        <div>
                                            <div class="font-medium text-sm">{{ country.country }}</div>
                                            <div class="text-xs text-gray-500">{{ country.visits }} visits</div>
                                        </div>
                                    </div>
                                    <Badge variant="secondary">{{ country.visits }}</Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

