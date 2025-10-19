<template>
    <aside class="flex h-full w-60 flex-col bg-slate-950/80 border-r border-slate-800/80">
        <div class="px-6 py-6">
            <h1 class="text-lg font-semibold uppercase tracking-[0.3em] text-slate-400">Channels</h1>
        </div>
        <nav class="flex-1 space-y-1 px-2">
            <button
                v-for="channel in channels"
                :key="channel.id"
                type="button"
                class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left transition"
                :class="[
                    modelValue === channel.id
                        ? 'bg-slate-800/80 text-slate-100 shadow-inner shadow-slate-900/60'
                        : 'text-slate-400 hover:bg-slate-900/80 hover:text-slate-100',
                ]"
                @click="selectChannel(channel.id)"
            >
                <span
                    class="flex h-9 w-9 items-center justify-center rounded-full border text-sm font-semibold uppercase"
                    :class="channel.badgeClass"
                    aria-hidden="true"
                >
                    {{ channel.short }}
                </span>
                <span class="flex flex-col">
                    <span class="text-sm font-medium">{{ channel.label }}</span>
                    <span class="text-xs text-slate-500">{{ channel.subtitle }}</span>
                </span>
            </button>
        </nav>
    </aside>
</template>

<script setup>
const props = defineProps({
    channels: {
        type: Array,
        required: true,
    },
    modelValue: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['update:modelValue']);

function selectChannel(id) {
    emit('update:modelValue', id);
}
</script>

