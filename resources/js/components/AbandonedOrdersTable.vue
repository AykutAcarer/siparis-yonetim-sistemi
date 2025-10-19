<template>
    <section class="flex flex-col gap-4 px-6 py-6">
        <div
            v-if="!meta?.sourceColumnPresent"
            class="flex items-start gap-3 rounded-xl border border-sky-500/40 bg-sky-500/10 px-4 py-3 text-sky-100"
        >
            <svg class="mt-1 h-4 w-4 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path
                    fill-rule="evenodd"
                    d="M10 .5a9.5 9.5 0 1 0 0 19 9.5 9.5 0 0 0 0-19ZM9 6a1 1 0 1 1 2 0v4a1 1 0 0 1-2 0V6Zm1 9a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4Z"
                    clip-rule="evenodd"
                />
            </svg>
            <p class="text-sm leading-5">
                Bu gorunum, kaynak sutunu bulunmadigi icin tum kayitlari listeliyor.
            </p>
        </div>

        <div class="grid gap-4 rounded-xl border border-slate-800/80 bg-slate-900/30 p-4 lg:grid-cols-12 lg:gap-6">
            <label class="flex flex-col gap-1 text-xs uppercase tracking-wide text-slate-400 lg:col-span-4">
                Arama
                <input
                    v-model.trim="searchTerm"
                    type="search"
                    placeholder="Order ID ara"
                    class="w-full rounded-lg border border-slate-700/70 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/40"
                />
            </label>
            <label class="flex flex-col gap-1 text-xs uppercase tracking-wide text-slate-400 lg:col-span-3">
                Durum
                <select
                    v-model="selectedStatus"
                    class="w-full rounded-lg border border-slate-700/70 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/40"
                >
                    <option value="">Tumu</option>
                    <option v-for="status in statusOptions" :key="status" :value="status">
                        {{ status }}
                    </option>
                </select>
            </label>
            <div class="grid gap-4 lg:col-span-5 lg:grid-cols-2">
                <label class="flex flex-col gap-1 text-xs uppercase tracking-wide text-slate-400">
                    Baslangic tarihi
                    <input
                        v-model="startDate"
                        type="date"
                        class="w-full rounded-lg border border-slate-700/70 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/40"
                    />
                </label>
                <label class="flex flex-col gap-1 text-xs uppercase tracking-wide text-slate-400">
                    Bitis tarihi
                    <input
                        v-model="endDate"
                        type="date"
                        class="w-full rounded-lg border border-slate-700/70 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/40"
                    />
                </label>
            </div>
        </div>

        <div
            v-if="error"
            class="flex flex-col items-start gap-3 rounded-xl border border-rose-500/40 bg-rose-500/10 px-4 py-4 text-sm text-rose-100"
        >
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 flex-none" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM9 6a1 1 0 1 1 2 0v4a1 1 0 0 1-2 0V6Zm1 9a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4Z"
                        clip-rule="evenodd"
                    />
                </svg>
                <span>{{ error }}</span>
            </div>
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg bg-slate-800/70 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-100 hover:bg-slate-700/70"
                @click="$emit('retry')"
            >
                Tekrar dene
            </button>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-800/80 bg-slate-950/40">
            <table class="min-w-full divide-y divide-slate-800/80">
                <thead class="bg-slate-900/60 text-xs uppercase tracking-wide text-slate-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 text-left">Order ID</th>
                        <th scope="col" class="cursor-pointer px-4 py-3 text-left" @click="toggleSort()">
                            Tarih
                            <SortIndicator :direction="sortDirection" />
                        </th>
                        <th scope="col" class="px-4 py-3 text-left">Durum</th>
                        <th v-if="meta?.sourceColumnPresent" scope="col" class="px-4 py-3 text-left">Kaynak</th>
                    </tr>
                </thead>
                <tbody v-if="loading" class="divide-y divide-slate-800/70">
                    <tr v-for="index in 12" :key="index">
                        <td colspan="4" class="px-4 py-4">
                            <div class="h-4 w-full animate-pulse rounded bg-slate-800/60"></div>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else-if="paginatedOrders.length === 0" class="divide-y divide-slate-800/70">
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-400">
                            Kriterlere uygun kayit bulunamadi.
                        </td>
                    </tr>
                </tbody>
                <tbody v-else class="divide-y divide-slate-800/70 bg-slate-900/30 text-sm text-slate-200">
                    <tr v-for="order in paginatedOrders" :key="order.orderId">
                        <td class="px-4 py-3 font-mono text-xs text-slate-300">{{ order.orderId }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col">
                                <span>{{ formatTimestamp(order.timestamp, order.raw?.timestamp) }}</span>
                                <span class="text-xs text-slate-500">{{ order.raw?.timestamp }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full bg-rose-500/15 px-3 py-1 text-xs font-semibold text-rose-300">
                                {{ order.orderStatus || '-' }}
                            </span>
                        </td>
                        <td v-if="meta?.sourceColumnPresent" class="px-4 py-3 text-slate-300">
                            {{ order.source || '-' }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="paginatedOrders.length > 0" class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-400">
            <span>{{ paginationLabel }}</span>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-lg border border-slate-700/70 px-3 py-2 font-semibold text-slate-200 transition hover:bg-slate-800/40 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="currentPage === 1"
                    @click="currentPage--"
                >
                    Onceki
                </button>
                <span class="px-2 text-slate-500">{{ currentPage }} / {{ totalPages }}</span>
                <button
                    type="button"
                    class="rounded-lg border border-slate-700/70 px-3 py-2 font-semibold text-slate-200 transition hover:bg-slate-800/40 disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="currentPage === totalPages"
                    @click="currentPage++"
                >
                    Sonraki
                </button>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
    orders: {
        type: Array,
        default: () => [],
    },
    meta: {
        type: Object,
        default: () => ({}),
    },
    loading: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: null,
    },
    channel: {
        type: String,
        required: true,
    },
});

defineEmits(['retry']);

const searchTerm = ref('');
const selectedStatus = ref('');
const startDate = ref('');
const endDate = ref('');
const currentPage = ref(1);
const pageSize = 25;
const sortDirection = ref('desc');

watch([searchTerm, selectedStatus, startDate, endDate], () => {
    currentPage.value = 1;
});

watch(
    () => props.orders,
    () => {
        currentPage.value = 1;
    }
);

const channelLabel = computed(() => props.channel.toLowerCase());

const statusOptions = computed(() => {
    const values = new Set(
        props.orders
            .map((order) => order.orderStatus)
            .filter((value) => value && value.trim() !== '')
    );

    return Array.from(values).sort((a, b) => a.localeCompare(b));
});

const filteredOrders = computed(() => {
    const searchText = searchTerm.value.toLowerCase();
    const start = startDate.value ? new Date(startDate.value) : null;
    const end = endDate.value ? new Date(endDate.value) : null;
    const needsSourceFilter = Boolean(props.meta?.sourceColumnPresent);

    return props.orders.filter((order) => {
        if (needsSourceFilter) {
            const src = (order.source || '').toString().toLowerCase();
            if (src && src !== channelLabel.value) {
                return false;
            }
        }

        if (selectedStatus.value && order.orderStatus !== selectedStatus.value) {
            return false;
        }

        if (searchText) {
            const haystack = (order.orderId || '').toString().toLowerCase();
            if (!haystack.includes(searchText)) {
                return false;
            }
        }

        if (start || end) {
            if (!order.timestamp) {
                return false;
            }

            const date = new Date(order.timestamp);

            if (start && date < start) {
                return false;
            }

            if (end) {
                const endOfDay = new Date(end);
                endOfDay.setHours(23, 59, 59, 999);

                if (date > endOfDay) {
                    return false;
                }
            }
        }

        return true;
    });
});

const sortedOrders = computed(() => {
    const sorted = [...filteredOrders.value];

    sorted.sort((a, b) => {
        const timeA = a.timestamp ? new Date(a.timestamp).getTime() : 0;
        const timeB = b.timestamp ? new Date(b.timestamp).getTime() : 0;

        return sortDirection.value === 'asc' ? timeA - timeB : timeB - timeA;
    });

    return sorted;
});

const totalPages = computed(() => Math.max(1, Math.ceil(sortedOrders.value.length / pageSize)));

watch(totalPages, (value) => {
    if (currentPage.value > value) {
        currentPage.value = value;
    }
});

const paginatedOrders = computed(() => {
    const start = (currentPage.value - 1) * pageSize;
    const end = start + pageSize;

    return sortedOrders.value.slice(start, end);
});

const paginationLabel = computed(() => {
    if (sortedOrders.value.length === 0) {
        return '0 kayit';
    }

    const start = (currentPage.value - 1) * pageSize + 1;
    const end = Math.min(start + pageSize - 1, sortedOrders.value.length);

    return `${start} - ${end} / ${sortedOrders.value.length} kayit`;
});

function toggleSort() {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
}

function formatTimestamp(timestamp, fallback) {
    if (!timestamp) {
        return fallback ?? '-';
    }

    try {
        const formatter = new Intl.DateTimeFormat('tr-TR', {
            dateStyle: 'medium',
            timeStyle: 'short',
        });

        return formatter.format(new Date(timestamp));
    } catch (error) {
        return fallback ?? timestamp;
    }
}
</script>

<script>
export default {
    components: {
        SortIndicator: {
            props: {
                direction: {
                    type: String,
                    required: true,
                },
            },
            template: `
                <span class="ml-2 inline-flex flex-col text-slate-500">
                    <svg v-if="direction === 'asc'" class="h-3 w-3" viewBox="0 0 12 12" fill="none">
                        <path d="M6 3l3 4H3l3-4z" fill="currentColor" />
                    </svg>
                    <svg v-else class="h-3 w-3" viewBox="0 0 12 12" fill="none">
                        <path d="M6 9L3 5h6L6 9z" fill="currentColor" />
                    </svg>
                </span>
            `,
        },
    },
};
</script>

