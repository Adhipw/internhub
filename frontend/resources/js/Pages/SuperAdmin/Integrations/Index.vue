<script setup lang="ts">
import logger from '@/Lib/logger';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import Button from '@/Components/Button.vue';
import { 
  Puzzle, Plus, Save, Trash2, RefreshCw,
  Lock, ShieldCheck, Loader2, AlertCircle
} from 'lucide-vue-next';
import Modal from '@/Components/Modal.vue';
import { ref, reactive, onMounted } from 'vue';
import api from '@/Services/api';
import { useLangStore } from '@/Stores/lang';

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const props = defineProps<{
    integrations?: any[];
}>();

const integrations = ref<any[]>(props.integrations || []);
const loading = ref(false);
const processing = ref<number | null | boolean>(null);

import { router as inertiaRouter } from '@inertiajs/vue3';

// Create Integration
const showCreateModal = ref(false);
const createForm = reactive({
    name: '',
    provider: 'openai',
    is_active: true,
    settings: {}
});

const openCreateModal = () => {
    createForm.name = '';
    createForm.provider = 'openai';
    createForm.is_active = true;
    createForm.settings = {};
    showCreateModal.value = true;
};

const submitCreate = async () => {
    processing.value = true;
    try {
        await api.post('/super-admin/integrations', createForm);
        showCreateModal.value = false;
        inertiaRouter.reload({ only: ['integrations'] });
    } catch (error) {
        logger.error('Failed to create integration:', error);
        alert('Gagal membuat integrasi baru.');
    } finally {
        processing.value = null;
    }
};

const updateIntegration = async (integration: any) => {
    processing.value = integration.id;
    try {
        await api.patch(`/super-admin/integrations/${integration.id}`, {
            name: integration.name,
            is_active: integration.is_active,
            settings: integration.settings
        });
        inertiaRouter.reload({ only: ['integrations'] });
    } catch (error) {
        logger.error('Failed to update integration:', error);
    } finally {
        processing.value = null;
    }
};

const deleteIntegration = async (id: number) => {
    if (!confirm('Apakah Anda yakin ingin menghapus integrasi ini?')) return;
    
    processing.value = id;
    try {
        await api.delete(`/super-admin/integrations/${id}`);
        inertiaRouter.reload({ only: ['integrations'] });
    } catch (error) {
        logger.error('Failed to delete integration:', error);
    } finally {
        processing.value = null;
    }
};

const syncIntegration = async (id: number) => {
    processing.value = id;
    try {
        await api.post(`/super-admin/integrations/${id}/sync`);
        alert('Sinkronisasi berhasil dimulai.');
    } catch (error) {
        logger.error('Sync failed:', error);
    } finally {
        processing.value = null;
    }
};

const getProviderIcon = (provider: string) => ShieldCheck;


</script>

<template>
  <DashboardLayout>
    <div v-if="loading" class="flex justify-center py-20">
      <Loader2 class="w-12 h-12 text-primary-600 animate-spin" />
    </div>

    <div v-else class="space-y-10">
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">Integrasi Eksternal</h1>
          <p class="text-slate-500 dark:text-slate-400">Hubungkan InterHub dengan layanan pihak ketiga melalui API yang aman.</p>
        </div>
        
        <button 
          @click="openCreateModal"
          class="flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-2xl text-sm font-bold hover:bg-primary-700 transition-all shadow-lg shadow-primary-500/20 active:scale-95"
        >
          <Plus class="w-4 h-4" />
          Integrasi Baru
        </button>
      </div>

      <div class="grid grid-cols-1 gap-8">
        <Card v-for="integration in integrations" :key="integration.id" class="p-8 border-none shadow-sm group">
           <div class="flex flex-col lg:flex-row gap-10">
              <!-- Sidebar Info -->
              <div class="lg:w-64 space-y-6">
                 <div class="w-16 h-16 bg-slate-50 dark:bg-slate-900 rounded-[1.5rem] flex items-center justify-center text-primary-600">
                    <component :is="getProviderIcon(integration.provider)" class="w-8 h-8" />
                 </div>
                 <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ integration.name }}</h3>
                    <p class="text-xs text-slate-400 font-mono mt-1">{{ integration.provider.toUpperCase() }}</p>
                 </div>
                 <div class="flex items-center gap-2">
                    <span :class="[
                      'px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest',
                      integration.is_active ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'
                    ]">
                       {{ integration.is_active ? 'Aktif' : 'Non-aktif' }}
                    </span>
                 </div>
              </div>

              <!-- Config Form -->
              <div class="flex-1 space-y-8">
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-2">
                       <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nama Integrasi</label>
                       <input 
                         v-model="integration.name"
                         type="text" 
                         class="w-full bg-slate-50 dark:bg-slate-900 border-none rounded-xl text-sm p-4 focus:ring-2 focus:ring-primary-500/20"
                       />
                    </div>
                    <div class="space-y-2">
                       <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Provider</label>
                       <div class="p-4 bg-slate-50 dark:bg-slate-900 rounded-xl text-sm text-slate-500 font-bold flex items-center gap-2">
                          <Lock class="w-4 h-4 text-slate-300" />
                          {{ integration.provider }}
                       </div>
                    </div>
                 </div>

                 <!-- Settings -->
                 <div class="flex items-center justify-between pt-4 border-t border-slate-50 dark:border-slate-800">
                    <div class="flex gap-3">
                       <button 
                         @click="updateIntegration(integration)"
                         :disabled="processing === integration.id"
                         class="flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-xl text-xs font-bold hover:bg-primary-700 transition-all"
                       >
                          <Loader2 v-if="processing === integration.id" class="w-4 h-4 animate-spin" />
                          <Save v-else class="w-4 h-4" />
                          Simpan
                       </button>
                       <button 
                         @click="deleteIntegration(integration.id)"
                         :disabled="processing === integration.id"
                         class="flex items-center gap-2 px-6 py-3 bg-red-50 text-red-600 rounded-xl text-xs font-bold hover:bg-red-600 hover:text-white transition-all"
                       >
                          <Trash2 class="w-4 h-4" />
                          Hapus
                       </button>
                    </div>
                    <button 
                      @click="syncIntegration(integration.id)"
                      :disabled="processing === integration.id"
                      class="flex items-center gap-2 px-6 py-3 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-xl text-xs font-bold hover:bg-primary-600 hover:text-white transition-all"
                    >
                       <RefreshCw :class="['w-4 h-4', processing === integration.id ? 'animate-spin' : '']" />
                       Tes Koneksi / Sync
                    </button>
                 </div>
              </div>
           </div>
        </Card>

        <div v-if="integrations.length === 0" class="p-20 text-center bg-slate-50 dark:bg-slate-900 rounded-[3rem] border border-dashed border-slate-200">
           <Puzzle class="w-16 h-16 text-slate-200 mx-auto mb-6" />
           <p class="text-slate-500 font-bold">Belum ada integrasi yang dikonfigurasi.</p>
        </div>
      </div>
    </div>

    <!-- Create Integration Modal -->
    <Modal :show="showCreateModal" @close="showCreateModal = false" max-width="md">
       <div class="p-8">
          <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-2">Integrasi Baru</h2>
          <p class="text-sm text-slate-500 mb-8">Hubungkan layanan AI atau Cloud baru.</p>

          <form @submit.prevent="submitCreate" class="space-y-6">
             <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Nama Layanan</label>
                <input v-model="createForm.name" type="text" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 focus:ring-2 focus:ring-primary-500/20" placeholder="Contoh: OpenAI Production" required />
             </div>

             <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Provider</label>
                <select v-model="createForm.provider" class="w-full bg-slate-50 dark:bg-slate-800 border-none rounded-2xl p-4 focus:ring-2 focus:ring-primary-500/20 appearance-none">
                   <option value="openai">OpenAI (ChatGPT)</option>
                   <option value="google">Google Cloud</option>
                   <option value="aws">AWS (S3/EC2)</option>
                   <option value="github">GitHub API</option>
                </select>
             </div>

             <div class="flex gap-4 pt-4">
                <button type="button" @click="showCreateModal = false" class="flex-1 px-6 py-4 bg-slate-100 text-slate-600 rounded-2xl font-bold">Batal</button>
                <button type="submit" :disabled="processing === true" class="flex-1 px-6 py-4 bg-primary-600 text-white rounded-2xl font-black shadow-lg shadow-primary-500/20 flex items-center justify-center gap-2">
                   <Loader2 v-if="processing === true" class="w-4 h-4 animate-spin" />
                   Buat Sekarang
                </button>
             </div>
          </form>
       </div>
    </Modal>
  </DashboardLayout>
</template>
