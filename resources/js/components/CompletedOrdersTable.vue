<template>
    <section class="flex flex-col gap-4 px-6 py-6">
        <div
            v-if="!meta?.sourceColumnPresent"
            class="flex items-start gap-3 rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-amber-200"
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
            <div class="grid gap-4 lg:col-span-6 lg:grid-cols-2">
                <label class="flex flex-col gap-1 text-xs uppercase tracking-wide text-slate-400">
                    Arama
                    <input
                        v-model.trim="searchTerm"
                        type="search"
                        placeholder="Order ID / musteri / telefon"
                        class="w-full rounded-lg border border-slate-700/70 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/40"
                    />
                </label>
                <label class="flex flex-col gap-1 text-xs uppercase tracking-wide text-slate-400">
                    Odeme tipi
                    <select
                        v-model="selectedPayment"
                        class="w-full rounded-lg border border-slate-700/70 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/40"
                    >
                        <option value="">Tumu</option>
                        <option v-for="payment in paymentOptions" :key="payment" :value="payment">{{ payment }}</option>
                    </select>
                </label>
            </div>
            <div class="grid gap-4 lg:col-span-4 lg:grid-cols-2">
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
            <div class="grid gap-4 lg:col-span-2">
                <label class="flex flex-col gap-1 text-xs uppercase tracking-wide text-slate-400">
                    Durum
                    <select
                        v-model="selectedStatus"
                        class="w-full rounded-lg border border-slate-700/70 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/40"
                    >
                        <option value="">Tumu</option>
                        <option value="Pending">Pending</option>
                        <option value="Dispatched">Dispatched</option>
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-xs uppercase tracking-wide text-slate-400">
                    Sayfa basina
                    <select
                        v-model.number="pageSize"
                        class="w-full rounded-lg border border-slate-700/70 bg-slate-950/70 px-3 py-2 text-sm text-slate-100 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/40"
                    >
                        <option v-for="size in pageSizeOptions" :key="size" :value="size">{{ size }}</option>
                    </select>
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
                        <th scope="col" class="cursor-pointer px-4 py-3 text-left" @click="toggleSort('timestamp')">
                            Tarih
                            <SortIndicator v-if="sortField === 'timestamp'" :direction="sortDirection" />
                        </th>
                        <th scope="col" class="px-4 py-3 text-left">Musteri</th>
                        <th scope="col" class="px-4 py-3 text-left">Telefon</th>
                        <th scope="col" class="px-4 py-3 text-left">Adres</th>
                        <th scope="col" class="px-4 py-3 text-left">Odeme</th>
                        <th scope="col" class="cursor-pointer px-4 py-3 text-right" @click="toggleSort('totalPrice')">
                            Tutar
                            <SortIndicator v-if="sortField === 'totalPrice'" :direction="sortDirection" />
                        </th>
                        <th scope="col" class="px-4 py-3 text-left">Durum</th>
                        <th scope="col" class="px-4 py-3 text-left">Aksiyon</th>
                    </tr>
                </thead>
                <tbody v-if="loading" class="divide-y divide-slate-800/70">
                    <tr v-for="index in pageSize" :key="index">
                        <td colspan="9" class="px-4 py-4">
                            <div class="h-4 w-full animate-pulse rounded bg-slate-800/60"></div>
                        </td>
                    </tr>
                </tbody>
                <tbody v-else-if="paginatedOrders.length === 0" class="divide-y divide-slate-800/70">
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-400">
                            Kriterlere uygun kayit bulunamadi.
                        </td>
                    </tr>
                </tbody>
                <tbody v-else class="divide-y divide-slate-800/70 bg-slate-900/30 text-sm text-slate-200">
                    <tr v-for="order in paginatedOrders" :key="order.orderId">
                        <td class="px-4 py-3 font-mono text-xs text-slate-300">{{ order.orderId }}</td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col">
                                <span>{{ formatTimestamp(order) }}</span>
                                <span class="text-xs text-slate-500">{{ order.raw?.timestamp }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col">
                                <span class="font-medium text-slate-100">{{ order.customerFullName || '-' }}</span>
                                <span class="text-xs text-slate-500">
                                    {{ order.customerName }} {{ order.customerSurname }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ order.customerPhone || '-' }}</td>
                        <td class="px-4 py-3 text-slate-300">
                            <span class="line-clamp-2">{{ order.customerAddress || '-' }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-200">{{ order.paymentType || '-' }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-100">
                            {{ formatPrice(order.totalPrice, order.raw?.totalPrice) }}
                        </td>
                        <td class="px-4 py-3">
                            <span :class="statusBadgeClass(order.status)">
                                {{ order.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg bg-emerald-500/90 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-emerald-950 transition hover:bg-emerald-400 disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="order.status === 'Dispatched' || dispatchingId === order.orderId"
                                @click="onDispatch(order.orderId)"
                            >
                                <svg
                                    v-if="dispatchingId === order.orderId"
                                    class="h-4 w-4 animate-spin"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    aria-hidden="true"
                                >
                                    <circle
                                        class="opacity-30"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        stroke-width="3"
                                    />
                                    <path
                                        d="M22 12a10 10 0 0 1-10 10"
                                        stroke="currentColor"
                                        stroke-width="3"
                                        stroke-linecap="round"
                                    />
                                </svg>
                                Gonderildi
                            </button>
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
                <span class="px-2 text-slate-500">
                    {{ currentPage }} / {{ totalPages }}
                </span>
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
    dispatchingId: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['retry', 'dispatch']);

const searchTerm = ref('');
const selectedPayment = ref('');
const selectedStatus = ref('');
const startDate = ref('');
const endDate = ref('');
const pageSize = ref(25);
const currentPage = ref(1);
const pageSizeOptions = [25, 50, 100];
const sortField = ref('timestamp');
const sortDirection = ref('desc');

watch([searchTerm, selectedPayment, selectedStatus, startDate, endDate, pageSize], () => {
    currentPage.value = 1;
});

watch(
    () => props.orders,
    () => {
        currentPage.value = 1;
    }
);

const paymentOptions = computed(() => {
    const values = new Set(
        props.orders
            .map((order) => order.paymentType)
            .filter((value) => value && value.trim() !== '')
    );

    return Array.from(values).sort((a, b) => a.localeCompare(b));
});

const channelLabel = computed(() => props.channel.toLowerCase());

const filteredOrders = computed(() => {
    const searchText = searchTerm.value.toLowerCase();
    const start = startDate.value ? new Date(startDate.value) : null;
    const end = endDate.value ? new Date(endDate.value) : null;
    const requiresSourceFilter = Boolean(props.meta?.sourceColumnPresent);

    return props.orders.filter((order) => {
        if (requiresSourceFilter) {
            const orderSource = (order.source || '').toString().toLowerCase();
            if (orderSource && orderSource !== channelLabel.value) {
                return false;
            }
        }

        if (selectedPayment.value) {
            if ((order.paymentType || '').toLowerCase() !== selectedPayment.value.toLowerCase()) {
                return false;
            }
        }

        if (selectedStatus.value && order.status !== selectedStatus.value) {
            return false;
        }

        if (searchText) {
            const haystack = [
                order.orderId,
                order.customerName,
                order.customerSurname,
                order.customerFullName,
                order.customerPhone,
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();

            if (!haystack.includes(searchText)) {
                return false;
            }
        }

        if (start || end) {
            if (!order.timestamp) {
                return false;
            }

            const orderDate = new Date(order.timestamp);

            if (start && orderDate < start) {
                return false;
            }

            if (end) {
                const endOfDay = new Date(end);
                endOfDay.setHours(23, 59, 59, 999);

                if (orderDate > endOfDay) {
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
        if (sortField.value === 'timestamp') {
            const timeA = a.timestamp ? new Date(a.timestamp).getTime() : 0;
            const timeB = b.timestamp ? new Date(b.timestamp).getTime() : 0;
            return sortDirection.value === 'asc' ? timeA - timeB : timeB - timeA;
        }

        if (sortField.value === 'totalPrice') {
            const priceA = typeof a.totalPrice === 'number' ? a.totalPrice : parseFloat(a.totalPrice) || 0;
            const priceB = typeof b.totalPrice === 'number' ? b.totalPrice : parseFloat(b.totalPrice) || 0;
            return sortDirection.value === 'asc' ? priceA - priceB : priceB - priceA;
        }

        return 0;
    });

    return sorted;
});

const totalPages = computed(() => Math.max(1, Math.ceil(sortedOrders.value.length / pageSize.value)));

watch(totalPages, (value) => {
    if (currentPage.value > value) {
        currentPage.value = value;
    }
});

const paginatedOrders = computed(() => {
    const start = (currentPage.value - 1) * pageSize.value;
    const end = start + pageSize.value;

    return sortedOrders.value.slice(start, end);
});

const paginationLabel = computed(() => {
    if (sortedOrders.value.length === 0) {
        return '0 kayit';
    }

    const start = (currentPage.value - 1) * pageSize.value + 1;
    const end = Math.min(start + pageSize.value - 1, sortedOrders.value.length);

    return `${start} - ${end} / ${sortedOrders.value.length} kayit`;
});

function toggleSort(field) {
    if (sortField.value === field) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortField.value = field;
        sortDirection.value = 'desc';
    }
}

function formatTimestamp(order) {
    if (!order.timestamp) {
        return '-';
    }

    try {
        const formatter = new Intl.DateTimeFormat('tr-TR', {
            dateStyle: 'medium',
            timeStyle: 'short',
        });

        return formatter.format(new Date(order.timestamp));
    } catch (error) {
        return order.raw?.timestamp || order.timestamp;
    }
}

function formatPrice(value, fallback) {
    if (typeof value === 'number' && Number.isFinite(value)) {
        const formatter = new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
            currencyDisplay: 'symbol',
            minimumFractionDigits: 2,
        });

        return formatter.format(value);
    }

    if (fallback) {
        return fallback;
    }

    return '-';
}

function statusBadgeClass(status) {
    if (status === 'Dispatched') {
        return 'inline-flex items-center rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-300';
    }

    return 'inline-flex items-center rounded-full bg-slate-700/40 px-3 py-1 text-xs font-semibold text-slate-200';
}

function onDispatch(orderId) {
    emit('dispatch', orderId);
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

