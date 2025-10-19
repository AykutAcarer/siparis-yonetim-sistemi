import { ref } from 'vue';
import axios from 'axios';

const STORAGE_KEY = 'orders-dashboard:dispatched-ids';

function normalizeError(error, fallbackMessage) {
    if (error?.response?.data?.message) {
        return error.response.data.message;
    }

    if (error?.message) {
        return error.message;
    }

    return fallbackMessage;
}

function loadPersistedDispatches() {
    if (typeof window === 'undefined') {
        return [];
    }

    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return [];
        }

        const parsed = JSON.parse(raw);

        return Array.isArray(parsed) ? parsed : [];
    } catch (error) {
        console.warn('Failed to read dispatched order cache', error);

        return [];
    }
}

function persistDispatches(orderIds) {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.localStorage.setItem(STORAGE_KEY, JSON.stringify(orderIds));
    } catch (error) {
        console.warn('Failed to persist dispatched order cache', error);
    }
}

export function useOrders() {
    const completedOrders = ref([]);
    const completedMeta = ref({
        usesMockData: false,
        sourceColumnPresent: false,
        fetchedAt: null,
    });
    const completedLoading = ref(false);
    const completedError = ref(null);

    const dispatchedOrderIds = ref(loadPersistedDispatches());

    const abandonedOrders = ref([]);
    const abandonedMeta = ref({
        usesMockData: false,
        sourceColumnPresent: false,
        fetchedAt: null,
    });
    const abandonedLoading = ref(false);
    const abandonedError = ref(null);

    async function loadCompleted({ silent = false } = {}) {
        if (!silent) {
            completedLoading.value = true;
        }

        completedError.value = null;

        try {
            const response = await axios.get('/api/orders/completed');
            const payload = response.data ?? {};

            const metaDispatched = Array.isArray(payload?.meta?.dispatchedIds)
                ? payload.meta.dispatchedIds
                : [];

            metaDispatched.forEach((id) => rememberDispatched(id));

            const orders = Array.isArray(payload.data) ? payload.data : [];

            completedOrders.value = applyDispatchOverrides(orders);
            completedMeta.value = {
                usesMockData: Boolean(payload?.meta?.usesMockData),
                sourceColumnPresent: Boolean(payload?.meta?.sourceColumnPresent),
                fetchedAt: payload?.meta?.fetchedAt ?? null,
                dispatchedIds: metaDispatched,
            };
        } catch (error) {
            completedError.value = normalizeError(error, 'Completed orders could not be loaded.');
        } finally {
            completedLoading.value = false;
        }
    }

    async function loadAbandoned({ silent = false } = {}) {
        if (!silent) {
            abandonedLoading.value = true;
        }

        abandonedError.value = null;

        try {
            const response = await axios.get('/api/orders/abandoned');
            const payload = response.data ?? {};

            abandonedOrders.value = Array.isArray(payload.data) ? payload.data : [];
            abandonedMeta.value = {
                usesMockData: Boolean(payload?.meta?.usesMockData),
                sourceColumnPresent: Boolean(payload?.meta?.sourceColumnPresent),
                fetchedAt: payload?.meta?.fetchedAt ?? null,
            };
        } catch (error) {
            abandonedError.value = normalizeError(error, 'Abandoned orders could not be loaded.');
        } finally {
            abandonedLoading.value = false;
        }
    }

    async function refreshAll() {
        await Promise.all([loadCompleted({ silent: true }), loadAbandoned({ silent: true })]);
    }

    async function dispatchOrder(orderId) {
        try {
            const response = await axios.post(`/api/orders/${encodeURIComponent(orderId)}/dispatch`);
            const payload = response.data?.data ?? {};

            rememberDispatched(orderId);

            const index = completedOrders.value.findIndex((order) => order.orderId === orderId);

            if (index !== -1) {
                completedOrders.value[index] = {
                    ...completedOrders.value[index],
                    status: payload.status ?? 'Dispatched',
                    dispatchedAt: payload.dispatchedAt ?? null,
                };

                completedOrders.value = [...completedOrders.value];
            }

            return payload;
        } catch (error) {
            const message = normalizeError(error, 'Dispatch request failed.');
            const enrichedError = new Error(message);
            enrichedError.orderId = orderId;
            enrichedError.cause = error;

            throw enrichedError;
        }
    }

    function rememberDispatched(orderId) {
        const normalized = String(orderId || '').trim();

        if (!normalized) {
            return;
        }

        if (!dispatchedOrderIds.value.includes(normalized)) {
            dispatchedOrderIds.value = [...dispatchedOrderIds.value, normalized];
            persistDispatches(dispatchedOrderIds.value);
        }
    }

    function applyDispatchOverrides(list) {
        if (!Array.isArray(list)) {
            return [];
        }

        return list.map((order) => {
            if (!order || !order.orderId) {
                return order;
            }

            const identifier = String(order.orderId).trim();

            if (!identifier) {
                return order;
            }

            if (order.status === 'Dispatched') {
                rememberDispatched(identifier);

                return order;
            }

            if (dispatchedOrderIds.value.includes(identifier)) {
                return {
                    ...order,
                    status: 'Dispatched',
                };
            }

            return order;
        });
    }

    return {
        completed: completedOrders,
        completedMeta,
        completedLoading,
        completedError,
        abandoned: abandonedOrders,
        abandonedMeta,
        abandonedLoading,
        abandonedError,
        loadCompleted,
        loadAbandoned,
        refreshAll,
        dispatchOrder,
    };
}
