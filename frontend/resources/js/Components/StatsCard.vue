<script setup lang="ts">
import { computed } from 'vue';
import type { Component } from 'vue';

interface Props {
  label: string;
  value: string | number;
  icon: Component;
  color?: string;
  trend?: {
    value: string | number;
    isUp: boolean;
  };
  loading?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  color: 'primary',
});

const colorClasses = {
  primary: 'text-primary-600 bg-primary-500/10 border-primary-500/10',
  success: 'text-emerald-600 bg-emerald-500/10 border-emerald-500/10',
  warning: 'text-amber-600 bg-amber-500/10 border-amber-500/10',
  danger: 'text-rose-600 bg-rose-500/10 border-rose-500/10',
  info: 'text-sky-600 bg-sky-500/10 border-sky-500/10',
  purple: 'text-purple-600 bg-purple-500/10 border-purple-500/10',
};

const trendClass = computed(() => {
  if (!props.trend) return '';
  return props.trend.isUp ? 'text-emerald-600 bg-emerald-50 dark:bg-emerald-900/20' : 'text-rose-600 bg-rose-50 dark:bg-rose-900/20';
});
</script>

<template>
  <div class="relative group">
    <!-- Sophisticated Glow Effect -->
    <div 
      class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-700 blur-2xl -z-10"
      :class="[color === 'primary' ? 'from-primary-500/20' : '']"
    ></div>

    <div class="card-premium p-10 !rounded-2xl relative overflow-hidden h-full flex flex-col justify-between">
      <!-- Background Abstract Pattern -->
      <div class="absolute -right-10 -bottom-10 w-40 h-40 opacity-[0.03] group-hover:opacity-[0.08] group-hover:scale-125 transition-all duration-700">
        <component :is="icon" class="w-full h-full rotate-12" />
      </div>

      <div class="relative z-10">
        <div :class="['w-16 h-16 rounded-[1.75rem] flex items-center justify-center mb-8 transition-all duration-500 group-hover:scale-110 group-hover:rotate-6 shadow-sm', colorClasses[color as keyof typeof colorClasses]]">
          <component :is="icon" class="w-8 h-8" />
        </div>

        <div class="space-y-1">
          <p class="text-[11px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-[0.25em]">{{ label }}</p>
          <div class="flex items-end gap-3">
            <h3 class="text-5xl font-bold text-slate-900 dark:text-white tracking-tighter leading-none">
              <template v-if="loading">---</template>
              <template v-else>{{ value }}</template>
            </h3>
            
            <div v-if="trend && !loading" :class="['px-2.5 py-1 rounded-full text-[10px] font-bold flex items-center gap-1 mb-1', trendClass]">
              <span v-if="trend.isUp">↑</span>
              <span v-else>↓</span>
              {{ trend.value }}
            </div>
          </div>
        </div>
      </div>

      <!-- Action Indicator (Subtle) -->
      <div class="mt-8 flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity duration-500">
        <div class="w-1.5 h-1.5 rounded-full bg-primary-600"></div>
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Detail Insight</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
@reference "../../css/app.css";

.card-premium {
  @apply bg-white dark:bg-neutral-900 border border-neutral-100 dark:border-neutral-800 shadow-premium-sm hover:shadow-premium-lg transition-all duration-500;
}
</style>
