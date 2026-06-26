<script setup lang="ts">
import { computed } from 'vue';
import { 
    Inbox, 
    SearchX, 
    FileWarning, 
    ShieldAlert,
    LayoutList,
    Clock
} from 'lucide-vue-next';

interface Props {
    type?: 'empty' | 'search' | 'error' | 'unauthorized' | 'loading';
    title?: string;
    description?: string;
    actionLabel?: string;
    actionIcon?: unknown;
}

const props = withDefaults(defineProps<Props>(), {
    type: 'empty',
    title: undefined,
    description: undefined,
    actionLabel: undefined,
    actionIcon: undefined
});

const emit = defineEmits<{
    (e: 'action'): void;
}>();

const icon = computed(() => {
    switch (props.type) {
        case 'search': return SearchX;
        case 'error': return FileWarning;
        case 'unauthorized': return ShieldAlert;
        case 'loading': return Clock;
        default: return Inbox;
    }
});
</script>

<template>
    <div class="flex flex-col items-center justify-center py-20 px-4 text-center animate-reveal relative overflow-hidden">
        <!-- Floating decorative elements -->
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute top-10 left-1/4 w-32 h-32 bg-primary-400/20 dark:bg-primary-600/20 rounded-full blur-2xl opacity-50 animate-float" style="animation-delay: 0s;"></div>
            <div class="absolute bottom-10 right-1/4 w-40 h-40 bg-accent-400/20 dark:bg-accent-600/20 rounded-full blur-2xl opacity-50 animate-float" style="animation-delay: 2s;"></div>
        </div>

        <div class="relative mb-10 group">
            <div class="absolute inset-0 bg-gradient-to-r from-primary-200 to-primary-100 dark:from-primary-900/40 dark:to-primary-800/20 rounded-2xl blur-2xl opacity-60 scale-125 -glow group-hover:opacity-100 transition-opacity duration-700"></div>
            <div class="relative w-28 h-28 glass-premium rounded-2xl flex items-center justify-center transform group-hover:scale-110 group-hover:-rotate-3 group-hover:translate-y-[-5px] transition-all duration-500 ease-out z-10">
                <component :is="icon" class="w-14 h-14 text-primary-600 dark:text-primary-400 group-hover:text-primary-500 transition-colors" />
            </div>
            <div class="absolute -bottom-3 -right-3 w-12 h-12 bg-slate-900 dark:bg-neutral-800 rounded-full flex items-center justify-center border-4 border-white dark:border-neutral-950 z-20 group-hover:scale-110 transition-transform duration-300 delay-100 shadow-premium-sm">
                <LayoutList class="w-5 h-5 text-white" />
            </div>
        </div>

        <h3 class="text-3xl font-bold text-slate-900 dark:text-white mb-4 tracking-tight z-10">
            {{ title || 'Belum Ada Data' }}
        </h3>
        <p class="text-slate-500 dark:text-slate-400 max-w-md mx-auto mb-10 leading-relaxed z-10 font-medium">
            {{ description || 'Data yang Anda cari tidak ditemukan atau belum tersedia saat ini.' }}
        </p>

        <button 
            v-if="actionLabel"
            class="relative inline-flex items-center gap-3 bg-slate-900 dark:bg-neutral-100 text-white dark:text-slate-900 px-8 py-4 rounded-full font-bold hover:bg-primary-600 dark:hover:bg-primary-400 hover:text-white transition-all shadow-premium-md hover:shadow-premium-lg group z-10 overflow-hidden"
            @click="emit('action')"
        >
            <div class="absolute inset-0 bg-white/20 group-hover:translate-x-full transition-transform duration-700 -skew-x-12 -translate-x-full"></div>
            <component :is="actionIcon" v-if="actionIcon" class="w-5 h-5 group-hover:scale-110 transition-transform" />
            {{ actionLabel }}
        </button>

        <div class="z-10 mt-4 relative">
            <slot name="actions"></slot>
        </div>
    </div>
</template>
