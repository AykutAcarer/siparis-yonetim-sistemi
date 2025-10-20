<template>
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <ToastStack :toasts="toasts" :on-dismiss="dismissToast" />
        <div class="flex h-screen overflow-hidden">
            <ChannelSidebar v-model="selectedChannel" :channels="channels" />
            <main class="flex-1 overflow-y-auto">
                <OrdersTabs
                    v-model="activeTab"
                    :counts="tabCounts"
                    :refreshing="isRefreshing"
                    :last-updated="lastUpdatedAt"
                    @refresh="handleRefresh"
                />

                <div v-if="showMockBanner" class="mx-6 mt-4 rounded-xl border border-sky-500/30 bg-sky-500/10 px-4 py-3">
                    <p class="text-sm text-sky-100">
                        Google Sheets erisilemedigi icin mock veriler goruntuleniyor.
                    </p>
                </div>
                <div
                    v-else-if="showFallbackBanner"
                    class="mx-6 mt-4 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3"
                >
                    <p class="text-sm text-amber-100">
                        Secilen kanal icin sheet tanimlanmadigi icin tablo bos gosteriliyor. Lutfen kanal icin
                        Google Sheets kimligi tanimlayin.
                    </p>
                </div>

                <CompletedOrdersTable
                    v-if="activeTab === 'completed'"
                    :orders="orders.completed.value"
                    :meta="orders.completedMeta.value"
                    :loading="orders.completedLoading.value"
                    :error="orders.completedError.value"
                    :channel="selectedChannel"
                    :dispatching-id="dispatchingOrderId"
                    @retry="orders.loadCompleted"
                    @dispatch="handleDispatch"
                />

                <AbandonedOrdersTable
                    v-else
                    :orders="orders.abandoned.value"
                    :meta="orders.abandonedMeta.value"
                    :loading="orders.abandonedLoading.value"
                    :error="orders.abandonedError.value"
                    :channel="selectedChannel"
                    @retry="orders.loadAbandoned"
                />
            </main>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AbandonedOrdersTable from './components/AbandonedOrdersTable.vue';
import ChannelSidebar from './components/ChannelSidebar.vue';
import CompletedOrdersTable from './components/CompletedOrdersTable.vue';
import OrdersTabs from './components/OrdersTabs.vue';
import ToastStack from './components/ToastStack.vue';
import { useOrders } from './composables/useOrders';
import { useToasts } from './composables/useToasts';

const orders = useOrders();
const { toasts, pushToast, dismissToast } = useToasts();

const selectedChannel = ref('telegram');
const activeTab = ref('completed');
const isRefreshing = ref(false);
const dispatchingOrderId = ref(null);

const channels = [
    {
        id: 'telegram',
        label: 'Telegram',
        subtitle: 'Chat order flow',
        short: 'TG',
        badgeClass: 'border-sky-500/60 bg-sky-500/15 text-sky-300',
    },
    {
        id: 'whatsapp',
        label: 'Whatsapp',
        subtitle: 'Automation queue',
        short: 'WA',
        badgeClass: 'border-emerald-500/60 bg-emerald-500/15 text-emerald-300',
    },
    {
        id: 'voice',
        label: 'Voice',
        subtitle: 'Inbound calls',
        short: 'VO',
        badgeClass: 'border-amber-500/60 bg-amber-500/15 text-amber-300',
    },
];

const channelNameMap = {
    telegram: 'telegram',
    whatsapp: 'whatsapp',
    voice: 'voice',
};

function baseChannelFilter(items, meta, channelId) {
    const label = channelNameMap[channelId] ?? channelId.toLowerCase();
    const needsSource = Boolean(meta?.sourceColumnPresent);
    const resolvedChannel = meta?.channel ?? null;

    if (resolvedChannel && resolvedChannel !== channelId) {
        return [];
    }

    if (!needsSource) {
        return items;
    }

    return items.filter((item) => {
        const source = (item.source || '').toString().toLowerCase();

        if (!source) {
            return true;
        }

        return source === label;
    });
}

const tabCounts = computed(() => {
    const completed = baseChannelFilter(
        orders.completed.value,
        orders.completedMeta.value,
        selectedChannel.value
    ).length;
    const abandoned = baseChannelFilter(
        orders.abandoned.value,
        orders.abandonedMeta.value,
        selectedChannel.value
    ).length;

    return { completed, abandoned };
});

const showMockBanner = computed(
    () => orders.completedMeta.value.usesMockData || orders.abandonedMeta.value.usesMockData
);

const showFallbackBanner = computed(
    () => orders.completedMeta.value.channelFallback || orders.abandonedMeta.value.channelFallback
);

const lastUpdatedAt = computed(() => {
    const times = [
        orders.completedMeta.value.fetchedAt,
        orders.abandonedMeta.value.fetchedAt,
    ]
        .filter(Boolean)
        .map((value) => new Date(value));

    if (times.length === 0) {
        return null;
    }

    return new Date(Math.max(...times.map((date) => date.getTime()))).toISOString();
});

let refreshHandle = null;

async function initialise() {
    orders.setChannel(selectedChannel.value);
    orders.completed.value = [];
    orders.abandoned.value = [];
    orders.completedLoading.value = true;
    orders.abandonedLoading.value = true;
    await handleRefresh({ silent: true });
}

function scheduleAutoRefresh() {
    clearAutoRefresh();
    refreshHandle = setInterval(() => {
        handleRefresh({ silent: true });
    }, 60_000);
}

function clearAutoRefresh() {
    if (refreshHandle) {
        clearInterval(refreshHandle);
        refreshHandle = null;
    }
}

async function handleRefresh(options = {}) {
    const silent = Boolean(options?.silent);

    scheduleAutoRefresh();

    if (isRefreshing.value) {
        return;
    }

    isRefreshing.value = true;

    try {
        await orders.refreshAll({ silent });

        if (!silent) {
            pushToast({
                type: 'info',
                message: 'Veriler guncellendi.',
                duration: 2500,
            });
        }
    } catch (error) {
        pushToast({
            type: 'error',
            message: error?.message ?? 'Veriler yenilenirken bir hata olustu.',
            duration: 4500,
        });
    } finally {
        isRefreshing.value = false;
    }
}

async function handleDispatch(orderId) {
    if (dispatchingOrderId.value) {
        return;
    }

    dispatchingOrderId.value = orderId;

    try {
        await orders.dispatchOrder(orderId);

        pushToast({
            type: 'success',
            message: `#${orderId} siparisi gonderildi.`,
            duration: 4000,
        });
    } catch (error) {
        pushToast({
            type: 'error',
            message: error.message ?? 'Gonderim basarisiz oldu.',
            actionLabel: 'Tekrar dene',
            onAction: () => handleDispatch(orderId),
            duration: 0,
        });
    } finally {
        dispatchingOrderId.value = null;
    }
}

async function waitForIdle() {
    while (isRefreshing.value) {
        await new Promise((resolve) => setTimeout(resolve, 50));
    }
}

watch(selectedChannel, async (channel) => {
    const changed = orders.setChannel(channel);

    if (!changed) {
        return;
    }

    orders.completed.value = [];
    orders.abandoned.value = [];
    orders.completedLoading.value = true;
    orders.abandonedLoading.value = true;

    await waitForIdle();
    await handleRefresh({ silent: true });
});

onMounted(async () => {
    await initialise();
});

onBeforeUnmount(clearAutoRefresh);
</script>
