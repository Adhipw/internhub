<script setup lang="ts">
import { ref } from 'vue';
import { Sparkles, X, Send, Loader2 } from 'lucide-vue-next';

defineProps<{
    title: string;
    isOpen: boolean;
    loading?: boolean;
}>();

const emit = defineEmits(['close', 'submit']);

const input = ref('');

const handleSubmit = () => {
    if (!input.value.trim()) return;
    emit('submit', input.value);
    input.value = '';
};
</script>

<template>
    <div v-if="isOpen" class="fixed inset-y-0 right-0 w-96 bg-white shadow-2xl border-l border-slate-100 z-[100] flex flex-col transform transition-transform duration-300">
        <!-- Header -->
        <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center">
                    <Sparkles class="w-4 h-4 text-white" />
                </div>
                <h3 class="font-bold text-slate-900">{{ title }}</h3>
            </div>
            <button class="p-2 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-white transition-all" @click="emit('close')">
                <X class="w-5 h-5" />
            </button>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
            <slot />
        </div>

        <!-- Input -->
        <div class="p-6 border-t border-slate-100">
            <div class="relative">
                <textarea 
                    v-model="input"
                    placeholder="Ask AI assistant..."
                    rows="3"
                    class="w-full px-4 py-3 bg-slate-50 border-none rounded-2xl text-sm focus:ring-2 focus:ring-blue-100 resize-none pr-12"
                    @keydown.enter.prevent="handleSubmit"
                ></textarea>
                <button 
                    :disabled="loading || !input.trim()"
                    class="absolute right-3 bottom-3 p-2 bg-slate-900 text-white rounded-xl hover:bg-slate-800 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="handleSubmit"
                >
                    <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
                    <Send v-else class="w-4 h-4" />
                </button>
            </div>
            <p class="mt-3 text-[10px] text-center text-slate-400 uppercase tracking-widest font-bold">
                AI assists, human decides
            </p>
        </div>
    </div>
</template>
