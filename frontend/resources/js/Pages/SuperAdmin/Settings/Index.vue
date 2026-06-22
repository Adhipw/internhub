<script setup lang="ts">
import logger from '@/Lib/logger';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import { 
  Settings, Flag, Save, Power, 
  AlertCircle, Lock, Info, ExternalLink,
  Loader2
} from 'lucide-vue-next';
import { ref, onMounted, reactive } from 'vue';
import api from '@/Services/api';
import { useToastStore } from '@/Stores/toast';
import { router as inertiaRouter } from '@inertiajs/vue3';

const toast = useToastStore();

const props = defineProps<{
    settings?: any[];
    featureFlags?: any[];
}>();

const settings = ref<any[]>(props.settings || []);
const featureFlags = ref<any[]>(props.featureFlags || []);
const loading = ref(false);
const processing = ref<number | null>(null);

const updateSetting = async (setting: any) => {
    processing.value = setting.id;
    try {
        await api.patch(`/super-admin/settings/${setting.id}`, {
            value: setting.value
        });
        toast.success('Pengaturan berhasil diperbarui.');
        inertiaRouter.reload({ only: ['settings'] });
    } catch (error) {
        logger.error('Failed to update setting:', error);
        toast.error('Gagal memperbarui pengaturan.');
    } finally {
        processing.value = null;
    }
};

const toggleFeature = async (flag: any) => {
    const originalState = flag.is_enabled;
    flag.is_enabled = !flag.is_enabled; // Optimistic update
    
    try {
        await api.post(`/super-admin/feature-flags/${flag.id}/toggle`);
        toast.success(`${flag.name} berhasil diperbarui.`);
        inertiaRouter.reload({ only: ['featureFlags'] });
    } catch (error) {
        flag.is_enabled = originalState; // Rollback
        logger.error('Failed to toggle feature:', error);
        toast.error('Gagal mengubah fitur.');
    }
};
</script>

<template>
  <DashboardLayout>
    <div class="space-y-10">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">System Configuration</h1>
          <p class="text-slate-500 dark:text-slate-400">Kelola pengaturan global dan ketersediaan fitur platform.</p>
        </div>
      </div>

      <div v-if="loading" class="flex justify-center py-20">
          <Loader2 class="w-12 h-12 text-primary-600 animate-spin" />
      </div>

      <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-10 animate-fade-in">
        <!-- Feature Flags -->
        <div class="space-y-6">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest flex items-center gap-2 px-4">
             <Flag class="w-4 h-4 text-primary-600" />
             Feature Flags
          </h3>
          <div class="grid grid-cols-1 gap-4">
            <Card v-for="flag in featureFlags" :key="flag.id" class="p-6 border-none shadow-sm flex items-center justify-between group bg-white dark:bg-slate-900/50">
               <div>
                  <h4 class="font-bold text-slate-900 dark:text-white mb-1">{{ flag.name }}</h4>
                  <p class="text-xs text-slate-500">{{ flag.description }}</p>
                  <p class="text-[10px] font-mono text-slate-400 mt-2">{{ flag.key }}</p>
               </div>
               <button 
                  @click="toggleFeature(flag)"
                  class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-600 focus:ring-offset-2"
                  :class="flag.is_enabled ? 'bg-primary-600' : 'bg-slate-200 dark:bg-slate-800'"
               >
                  <span 
                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                    :class="flag.is_enabled ? 'translate-x-5' : 'translate-x-0'"
                  ></span>
               </button>
            </Card>
            <div v-if="featureFlags.length === 0" class="p-10 text-center bg-slate-50 dark:bg-slate-900 rounded-[2rem] border border-dashed border-slate-200">
               <p class="text-sm text-slate-400">Tidak ada feature flag yang dikonfigurasi.</p>
            </div>
          </div>
        </div>

        <!-- System Settings -->
        <div class="space-y-6">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest flex items-center gap-2 px-4">
             <Settings class="w-4 h-4 text-primary-600" />
             System Settings
          </h3>
          <div class="space-y-4">
            <Card v-for="setting in settings" :key="setting.id" class="p-8 border-none shadow-sm space-y-4 bg-white dark:bg-slate-900/50">
               <div class="flex items-start justify-between">
                  <div>
                     <h4 class="font-bold text-slate-900 dark:text-white mb-1 flex items-center gap-2 text-sm">
                        {{ setting.key.replace(/_/g, ' ').toUpperCase() }}
                        <Lock v-if="setting.is_sensitive" class="w-3.5 h-3.5 text-orange-500" />
                     </h4>
                     <p class="text-[10px] text-slate-500">{{ setting.description }}</p>
                  </div>
                  <span class="px-2 py-1 bg-slate-50 dark:bg-slate-900 rounded text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                     {{ setting.group }}
                  </span>
               </div>

               <div class="flex items-end gap-3">
                  <div class="flex-1 space-y-1">
                     <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Value</label>
                     <input 
                        v-model="setting.value"
                        :type="setting.is_sensitive ? 'password' : 'text'"
                        class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-xl text-sm p-4 focus:ring-2 focus:ring-primary-500/20"
                     />
                  </div>
                  <button 
                    @click="updateSetting(setting)"
                    :disabled="processing === setting.id"
                    class="p-4 bg-primary-600 text-white rounded-xl hover:bg-primary-700 transition-all active-press disabled:opacity-50"
                  >
                     <Loader2 v-if="processing === setting.id" class="w-5 h-5 animate-spin" />
                     <Save v-else class="w-5 h-5" />
                  </button>
               </div>
            </Card>
            
            <div v-if="settings.length === 0" class="p-10 text-center bg-slate-50 dark:bg-slate-900 rounded-[2rem] border border-dashed border-slate-200">
               <p class="text-sm text-slate-400">Tidak ada pengaturan sistem yang ditemukan.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.5s ease-out forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
