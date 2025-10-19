<template>
    <div class="flex flex-col gap-4 border-b border-slate-800/80 bg-slate-900/40 px-6 pb-4 pt-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="inline-flex space-x-2 rounded-lg bg-slate-800/60 p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    class="rounded-md px-4 py-2 text-sm font-semibold transition"
                    :class="[
                        modelValue === tab.id
                            ? 'bg-slate-950 text-slate-100 shadow'
                            : 'text-slate-400 hover:text-slate-100',
                    ]"
                    @click="selectTab(tab.id)"
                >
                    <span>{{ tab.label }}</span>
                    <span class="ml-2 rounded-full bg-slate-700/70 px-2 py-[2px] text-xs font-normal text-slate-300">
                        {{ counts?.[tab.id] ?? 0 }}
                    </span>
                </button>
            </div>
            <div class="flex items-center gap-3 text-xs text-slate-400">
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-800/70 px-3 py-2 text-sm font-semibold text-slate-100 transition hover:bg-slate-700/70 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="refreshing"
                    @click="$emit('refresh')"
                >
                    <svg
                        class="h-4 w-4"
                        viewBox="0 0 20 20"
                        fill="none"
                        aria-hidden="true"
                        :class="{ 'animate-spin': refreshing }"
                    >
                        <path
                            d="M4 4v4h4"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M16 16v-4h-4"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                        <path
                            d="M4.5 15.5A7 7 0 0 0 16 12"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                        />
                        <path
                            d="M15.5 4.5A7 7 0 0 0 4 8"
                            stroke="currentColor"
                            stroke-width="1.5"
                            stroke-linecap="round"
                        />
                    </svg>
                    Yenile
                </button>
                <span class="hidden sm:inline-flex items-center gap-1">
                    <svg class="h-3 w-3 text-slate-500" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true">
                        <circle cx="6" cy="6" r="5" opacity="0.4" />
                    </svg>
                    <span>Otomatik yenileme: 60sn</span>
                </span>
                <span v-if="lastUpdated" class="hidden text-slate-500 sm:inline">
                    Son güncelleme:
                    <time :datetime="lastUpdated">{{ formattedLastUpdated }}</time>
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        required: true,
    },
    counts: {
        type: Object,
        default: () => ({}),
    },
    refreshing: {
        type: Boolean,
        default: false,
    },
    lastUpdated: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue', 'refresh']);

const tabs = [
    { id: 'completed', label: 'Completed' },
    { id: 'abandoned', label: 'Abandoned' },
];

function selectTab(id) {
    emit('update:modelValue', id);
}

const formattedLastUpdated = computed(() => {
    if (!props.lastUpdated) {
        return null;
    }

    try {
        const formatter = new Intl.DateTimeFormat('tr-TR', {
            dateStyle: 'short',
            timeStyle: 'short',
        });

        return formatter.format(new Date(props.lastUpdated));
    } catch (error) {
        return props.lastUpdated;
    }
});
</script>

