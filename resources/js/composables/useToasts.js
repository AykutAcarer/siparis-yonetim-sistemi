import { ref } from 'vue';

let toastId = 0;

export function useToasts() {
    const toasts = ref([]);

    function pushToast({ message, type = 'info', duration = 5000, actionLabel = null, onAction = null }) {
        const id = ++toastId;
        const toast = { id, message, type, actionLabel, onAction };

        toasts.value = [...toasts.value, toast];

        if (duration) {
            setTimeout(() => dismissToast(id), duration);
        }

        return id;
    }

    function dismissToast(id) {
        toasts.value = toasts.value.filter((toast) => toast.id !== id);
    }

    function clearToasts() {
        toasts.value = [];
    }

    return {
        toasts,
        pushToast,
        dismissToast,
        clearToasts,
    };
}

