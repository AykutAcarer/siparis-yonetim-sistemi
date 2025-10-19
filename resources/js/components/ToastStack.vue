<template>
    <div
        class="pointer-events-none fixed inset-x-0 top-4 z-50 flex min-h-[40px] flex-col items-center gap-2 px-4"
        role="region"
        aria-live="assertive"
    >
        <transition-group name="toast" tag="div" class="flex w-full max-w-md flex-col gap-2">
            <div
                v-for="toast in toasts"
                :key="toast.id"
                class="pointer-events-auto rounded-xl border border-slate-700/70 bg-slate-900/90 p-4 shadow-2xl shadow-black/30 backdrop-blur"
                :class="toastClass(toast.type)"
            >
                <div class="flex items-start gap-3">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-100">{{ toast.message }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            v-if="toast.actionLabel && toast.onAction"
                            type="button"
                            class="text-xs font-semibold uppercase tracking-wide text-sky-400 underline-offset-2 hover:underline"
                            @click="handleAction(toast)"
                        >
                            {{ toast.actionLabel }}
                        </button>
                        <button
                            type="button"
                            class="rounded-full bg-slate-800/70 p-1 text-slate-300 hover:bg-slate-700"
                            @click="onDismiss(toast.id)"
                            aria-label="Close notification"
                        >
                            <span class="sr-only">Close</span>
                            <svg class="h-3.5 w-3.5" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                                <path
                                    d="M3 3l8 8m0-8l-8 8"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </transition-group>
    </div>
</template>

<script setup>
const props = defineProps({
    toasts: {
        type: Array,
        required: true,
    },
    onDismiss: {
        type: Function,
        required: true,
    },
});

const toastClass = (type) => {
    switch (type) {
        case 'success':
            return 'border-emerald-500/30 bg-emerald-500/10';
        case 'error':
            return 'border-rose-500/30 bg-rose-500/10';
        case 'info':
        default:
            return 'border-sky-500/30 bg-sky-500/10';
    }
};

function handleAction(toast) {
    if (toast.onAction) {
        toast.onAction();
    }

    props.onDismiss(toast.id);
}
</script>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-10px) scale(0.96);
}
</style>

