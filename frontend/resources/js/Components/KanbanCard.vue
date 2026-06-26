<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { 
  Building2, Calendar, MapPin, 
  ArrowUpRight, Clock, User 
} from 'lucide-vue-next';
import StatusBadge from './StatusBadge.vue';

interface Props {
  title: string;
  subtitle: string;
  status: string;
  date: string;
  image?: string;
  href?: string;
  showDecisionIndicator?: boolean;
}

const props = defineProps<Props>();

const statusBorderClass = computed(() => {
  switch (props.status) {
    case 'accepted': return 'border-emerald-500/20 bg-emerald-500/5';
    case 'rejected': return 'border-rose-500/20 bg-rose-500/5';
    case 'interview': return 'border-primary-500/20 bg-primary-500/5';
    default: return 'border-slate-100 dark:border-white/5 bg-white dark:bg-slate-900';
  }
});

const initials = computed(() => {
  return props.subtitle.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
});
</script>

<template>
  <div 
    class="group relative p-6 rounded-2xl border transition-all duration-500 hover:shadow-premium-xl active-press"
    :class="statusBorderClass"
  >
    <div class="flex items-start gap-5">
      <!-- High-Fidelity Avatar/Logo -->
      <div class="relative shrink-0">
        <div class="w-16 h-16 rounded-2xl overflow-hidden bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center justify-center group-hover:scale-105 transition-transform duration-500">
          <img v-if="image" loading="lazy" decoding="async" :src="image" class="w-full h-full object-cover p-1" />
          <div v-else class="text-lg font-bold text-slate-300">{{ initials }}</div>
        </div>
        <!-- Online/Status Indicator -->
        <div 
          v-if="showDecisionIndicator"
          class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full border-4 border-white dark:border-slate-900 z-10"
          :class="status === 'accepted' ? 'bg-emerald-500' : (status === 'rejected' ? 'bg-rose-500' : 'bg-primary-500')"
        ></div>
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between gap-2 mb-1">
          <h4 class="text-base font-bold text-slate-900 dark:text-white truncate group-hover:text-primary-600 transition-colors">
            {{ title }}
          </h4>
          <Link v-if="href" :href="href" class="p-2 rounded-full bg-slate-50 dark:bg-slate-800 text-slate-400 opacity-0 group-hover:opacity-100 group-hover:translate-x-1 group-hover:-translate-y-1 transition-all">
            <ArrowUpRight class="w-4 h-4" />
          </Link>
        </div>
        
        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mb-4 flex items-center gap-1.5">
          <Building2 class="w-3.5 h-3.5 opacity-50" />
          {{ subtitle }}
        </p>

        <div class="flex items-center justify-between pt-4 border-t border-slate-100 dark:border-white/5">
          <div class="flex items-center gap-3">
             <StatusBadge :status="status" size="sm" />
             <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-1">
               <Clock class="w-3 h-3" />
               {{ date }}
             </span>
          </div>
          
          <!-- Tactile Avatars (if applicable, placeholder for now) -->
          <div class="flex -space-x-2">
            <div class="w-6 h-6 rounded-full border-2 border-white dark:border-slate-900 bg-slate-200 flex items-center justify-center">
              <User class="w-3 h-3 text-slate-400" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Hover Indicator Line -->
    <div class="absolute left-1/2 -bottom-px -translate-x-1/2 w-0 h-1 bg-primary-600 rounded-full transition-all duration-500 group-hover:w-1/2"></div>
  </div>
</template>

