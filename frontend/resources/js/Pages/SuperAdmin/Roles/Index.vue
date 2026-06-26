<script setup lang="ts">
import logger from '@/Lib/logger';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import { 
  Shield, 
  Key, 
  Plus, 
  Save, 
  Trash2, 
  Info,
  CheckCircle,
  Loader2
} from 'lucide-vue-next';
import { ref, computed, onMounted, reactive, watch } from 'vue';
import api from '@/Services/api';
import { useToastStore } from '@/Stores/toast';
import { useLangStore } from '@/Stores/lang';
import { router as inertiaRouter } from '@inertiajs/vue3';
const toast = useToastStore();
const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const props = defineProps<{
    roles?: any[];
    permissions?: any[];
}>();

const roles = computed(() => props.roles || []);
const permissions = computed(() => props.permissions || []);
const loading = ref(false);
const selectedRole = ref<number | null>(null);

const newRole = reactive({
    name: '',
    processing: false
});
const permissionForm = reactive({
    permissions: [] as string[],
    processing: false
});
const selectRole = (role: any) => {
    selectedRole.value = role.id;
    permissionForm.permissions = role.permissions.map((p: any) => p.name);
};

const createRole = () => {
    if (!newRole.name) return;
    newRole.processing = true;
    
    inertiaRouter.post('/super-admin/roles', { name: newRole.name }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            newRole.name = '';
            toast.success('Role berhasil dibuat');
        },
        onError: (errors: any) => {
            toast.error(errors.name || 'Gagal membuat role');
        },
        onFinish: () => {
            newRole.processing = false;
        }
    });
};

const syncPermissions = () => {
    if (!selectedRole.value) return;
    permissionForm.processing = true;

    inertiaRouter.post(`/super-admin/roles/${selectedRole.value}/permissions`, {
        permissions: permissionForm.permissions
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            toast.success('Izin role berhasil diperbarui');
            
            // Re-sync the permission form with the updated role data
            const updatedRole = roles.value.find(r => r.id === selectedRole.value);
            if (updatedRole) {
                permissionForm.permissions = updatedRole.permissions.map((p: any) => p.name);
            }
        },
        onError: (errors: any) => {
            logger.error('Failed to sync permissions:', errors);
            toast.error('Gagal memperbarui izin');
        },
        onFinish: () => {
            permissionForm.processing = false;
        }
    });
};

const deleteRole = async (id: number) => {
    if (!confirm('Hapus role ini?')) return;
    inertiaRouter.delete(`/api/v1/super-admin/roles-data/${id}`, {
        onSuccess: () => {
            toast.success('Role dihapus');
            if (selectedRole.value === id) selectedRole.value = null;
        },
        onError: () => toast.error('Gagal menghapus role')
    });
};

onMounted(() => {
    if (roles.value.length > 0 && !selectedRole.value) {
        selectRole(roles.value[0]);
    }
});

const togglePermission = (name: string) => {
    const idx = permissionForm.permissions.indexOf(name);
    if (idx > -1) {
        permissionForm.permissions.splice(idx, 1);
    } else {
        permissionForm.permissions.push(name);
    }
};
</script>

<template>
  <DashboardLayout>
    <div v-if="loading" class="h-[60vh] flex items-center justify-center">
        <Loader2 class="w-12 h-12 text-primary-600 animate-spin" />
    </div>
    
    <div v-else class="space-y-12 animate-fade-in pb-20">
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-8">
        <div>
          <h1 class="text-4xl font-bold text-slate-900 dark:text-white mb-2 tracking-tight">{{ t('sidebar.role_permission') }}</h1>
          <p class="text-slate-500 dark:text-slate-400 font-medium">{{ t('admin.roles.desc') || 'Kelola tingkat akses dan izin granular untuk setiap peran pengguna.' }}</p>
        </div>
        
        <form class="flex items-center gap-3" @submit.prevent="createRole">
          <input 
            v-model="newRole.name"
            type="text" 
            :placeholder="t('admin.roles.new_placeholder') || 'Nama role baru...'" 
            class="px-6 py-4 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl text-sm font-bold focus:outline-none focus:ring-4 focus:ring-primary-500/10 w-64 transition-all"
          />
          <button 
            type="submit"
            :disabled="newRole.processing"
            class="flex items-center gap-2 px-8 py-4 bg-primary-600 text-white rounded-2xl text-sm font-bold hover:bg-primary-700 transition-all shadow-lg shadow-primary-500/20 active:scale-95 disabled:opacity-50"
          >
            <Plus v-if="!newRole.processing" class="w-5 h-5" />
            <Loader2 v-else class="w-5 h-5 animate-spin" />
            {{ t('admin.roles.add_btn') || 'Tambah' }}
          </button>
        </form>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-12">
        <!-- Roles List -->
        <div class="lg:col-span-4 space-y-6">
           <h3 class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] px-4">{{ t('admin.roles.list_title') || 'Daftar Role' }}</h3>
           <div class="space-y-3">
              <div 
                v-for="role in roles" 
                :key="role.id"
                class="relative group"
              >
                <button 
                  :class="[
                    'w-full text-left px-8 py-6 rounded-[2rem] transition-all flex items-center justify-between group overflow-hidden relative',
                    selectedRole === role.id 
                      ? 'bg-slate-900 text-white shadow-2xl shadow-slate-900/20 dark:bg-primary-600' 
                      : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-100 dark:border-slate-800'
                  ]"
                  @click="selectRole(role)"
                >
                  <div class="flex items-center gap-4 relative z-10">
                     <div
:class="[
                        'w-10 h-10 rounded-xl flex items-center justify-center transition-colors',
                        selectedRole === role.id ? 'bg-white/10 text-white' : 'bg-primary-50 text-primary-600 dark:bg-primary-900/20'
                     ]">
                        <Shield class="w-5 h-5" />
                     </div>
                     <span class="font-bold text-sm tracking-tight capitalize">{{ role.name.replace(/_/g, ' ') }}</span>
                  </div>
                  
                  <div v-if="selectedRole === role.id" class="absolute right-0 top-0 h-full w-24 bg-gradient-to-l from-white/10 to-transparent"></div>
                </button>
                <button 
                  v-if="role.name !== 'super_admin'"
                  class="absolute right-6 top-1/2 -translate-y-1/2 p-3 text-rose-500 opacity-0 group-hover:opacity-100 transition-all hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-2xl"
                  @click.stop="deleteRole(role.id)"
                >
                  <Trash2 class="w-5 h-5" />
                </button>
              </div>
           </div>
        </div>

        <!-- Permissions Matrix -->
        <div class="lg:col-span-8">
           <Card v-if="selectedRole" class="!p-10 border-none shadow-2xl !rounded-2xl min-h-[500px] relative overflow-hidden bg-white dark:bg-slate-900">
              <div class="absolute top-0 right-0 w-64 h-64 bg-primary-500/5 blur-2xl opacity-30 rounded-full -mr-32 -mt-32"></div>
              
              <div class="flex flex-col md:flex-row md:items-center justify-between mb-12 gap-6 relative z-10">
                 <div>
                    <h2 class="text-2xl font-bold text-slate-900 dark:text-white leading-tight">
                       {{ t('admin.roles.permissions_for') || 'Izin Role:' }} 
                       <span class="text-primary-600 capitalize">{{ roles.find(r => r.id === selectedRole)?.name.replace(/_/g, ' ') }}</span>
                    </h2>
                    <p class="text-xs font-bold text-slate-400 mt-2 uppercase tracking-widest">{{ t('admin.roles.permissions_desc') || 'Centang izin yang ingin diberikan pada role ini.' }}</p>
                 </div>
                 <button 
                   :disabled="permissionForm.processing"
                   class="flex items-center gap-3 px-10 py-5 bg-slate-900 dark:bg-primary-600 text-white rounded-[1.5rem] text-sm font-bold hover:bg-slate-800 transition-all shadow-xl shadow-slate-900/20 active:scale-95 disabled:opacity-50"
                   @click="syncPermissions"
                 >
                    <Save v-if="!permissionForm.processing" class="w-5 h-5" />
                    <Loader2 v-else class="w-5 h-5 animate-spin" />
                    {{ t('admin.roles.save_btn') || 'Simpan Perubahan' }}
                 </button>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 relative z-10">
                 <div 
                   v-for="permission in permissions" 
                   :key="permission.id"
                   class="flex items-center gap-4 p-5 rounded-[1.5rem] border border-slate-50 dark:border-slate-800 hover:bg-slate-50/50 dark:hover:bg-slate-800/50 transition-all cursor-pointer group relative"
                   @click="togglePermission(permission.name)"
                 >
                    <div 
                      :class="[
                        'w-6 h-6 rounded-lg border-2 flex items-center justify-center transition-all duration-300',
                        permissionForm.permissions.includes(permission.name)
                          ? 'bg-primary-600 border-primary-600 scale-110 shadow-lg shadow-primary-500/30'
                          : 'border-slate-200 dark:border-slate-700'
                      ]"
                    >
                       <CheckCircle v-if="permissionForm.permissions.includes(permission.name)" class="w-4 h-4 text-white" />
                    </div>
                    <div>
                       <p class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-tight">{{ permission.name.replace(/_/g, ' ') }}</p>
                       <p class="text-[9px] font-bold text-slate-400 font-mono mt-0.5">{{ permission.name }}</p>
                    </div>
                 </div>
              </div>
           </Card>

           <div v-else class="h-full flex items-center justify-center bg-slate-50/50 dark:bg-slate-900/50 rounded-2xl border-4 border-dashed border-slate-100 dark:border-slate-800">
              <div class="text-center p-20">
                 <div class="w-24 h-24 bg-white dark:bg-slate-800 rounded-[2rem] shadow-sm flex items-center justify-center mx-auto mb-8">
                    <Key class="w-10 h-10 text-slate-200" />
                 </div>
                 <p class="text-lg font-bold text-slate-400 uppercase tracking-widest">{{ t('admin.roles.select_prompt') || 'Pilih role untuk mengelola izin.' }}</p>
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
