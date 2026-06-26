<script setup lang="ts">
import { useToastStore } from '@/Stores/toast';
import { CheckCircle2, XCircle, Info, AlertTriangle, X, RefreshCw, RotateCcw } from 'lucide-vue-next';

const toastStore = useToastStore();

const runAction = async (toastId: number, handler: () => void | Promise<void>) => {
    toastStore.remove(toastId);
    await handler();
};
</script>

<template>
    <div class="fixed top-6 right-6 z-[9999] flex flex-col gap-4 w-full max-w-sm pointer-events-none">
        <transition-group 
            name="toast" 
            enter-active-class="transition duration-500 ease-out"
            enter-from-class="translate-x-full opacity-0 scale-95"
            enter-to-class="translate-x-0 opacity-100 scale-100"
            leave-active-class="transition duration-300 ease-in"
            leave-from-class="translate-x-0 opacity-100 scale-100"
            leave-to-class="translate-x-full opacity-0 scale-95"
        >
            <div 
                v-for="toast in toastStore.toasts" 
                :key="toast.id"
                class="pointer-events-auto glass p-5 rounded-[1.5rem] shadow-premium flex items-start gap-4 border-l-4"
                :class="{
                    'border-emerald-500': toast.type === 'success',
                    'border-rose-500': toast.type === 'error',
                    'border-blue-500': toast.type === 'info',
                    'border-amber-500': toast.type === 'warning',
                }"
            >
                <div class="shrink-0 mt-1">
                    <CheckCircle2 v-if="toast.type === 'success'" class="w-6 h-6 text-emerald-500" />
                    <XCircle v-else-if="toast.type === 'error'" class="w-6 h-6 text-rose-500" />
                    <AlertTriangle v-else-if="toast.type === 'warning'" class="w-6 h-6 text-amber-500" />
                    <Info v-else class="w-6 h-6 text-blue-500" />
                </div>
                
                <div class="flex-1 min-w-0 space-y-3">
                    <p class="text-sm font-bold text-slate-900 dark:text-white leading-relaxed">
                        {{ toast.message }}
                    </p>
                    <div v-if="toast.actions?.length" class="flex flex-wrap gap-2">
                        <button
                            v-for="action in toast.actions"
                            :key="action.label"
                            class="inline-flex items-center gap-2 rounded-lg px-3 py-2 text-xs font-bold transition-colors"
                            :class="{
                                'bg-slate-900 text-white hover:bg-blue-500': action.variant === 'primary' || !action.variant,
                                'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-white/10 dark:text-white dark:hover:bg-white/15': action.variant === 'secondary',
                                'bg-rose-600 text-white hover:bg-rose-500': action.variant === 'danger',
                            }"
                            @click="runAction(toast.id, action.handler)"
                        >
                            <RefreshCw v-if="action.variant === 'primary' || !action.variant" class="w-3.5 h-3.5" />
                            <RotateCcw v-else-if="action.variant === 'secondary' || action.variant === 'danger'" class="w-3.5 h-3.5" />
                            <Info v-else class="w-3.5 h-3.5" />
                            <span>{{ action.label }}</span>
                        </button>
                    </div>
                </div>

                <button class="shrink-0 p-1 hover:bg-slate-100 dark:hover:bg-white/10 rounded-full transition-colors" @click="toastStore.remove(toast.id)">
                    <X class="w-4 h-4 text-slate-400" />
                </button>
            </div>
        </transition-group>
    </div>
</template>

<style scoped>
.toast-move {
    transition: transform 0.5s ease;
}
</style>
