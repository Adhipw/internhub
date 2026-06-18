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
import { ref, onMounted, reactive } from 'vue';
import api from '@/Services/api';
import { useToastStore } from '@/Stores/toast';
import { useLangStore } from '@/Stores/lang';
const toast = useToastStore();
const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const props = defineProps<{
    roles?: any[];
    permissions?: any[];
}>();

const roles = ref<any[]>(props.roles || []);
const permissions = ref<any[]>(props.permissions || []);
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

const fetchData = async () => {
    loading.value = true;
    try {
        const [rolesRes, permissionsRes] = await Promise.all([
            api.get('/super-admin/roles-data'),
            api.get('/super-admin/permissions-data')
        ]);
        roles.value = rolesRes.data.data;
        permissions.value = permissionsRes.data.data;
        if (roles.value.length > 0 && !selectedRole.value) {
            selectRole(roles.value[0]);
        }
    } catch (error) {
        logger.error('Failed to fetch roles data:', error);
        toast.error('Gagal mengambil data role dan izin.');
    } finally {
        loading.value = false;
    }
};

const hydrateInitialData = () => {
    if (roles.value.length > 0 && !selectedRole.value) {
        selectRole(roles.value[0]);
    }
};

const selectRole = (role: any) => {
    selectedRole.value = role.id;
    permissionForm.permissions = role.permissions.map((p: any) => p.name);
};

const createRole = async () => {
    if (!newRole.name) return;
    newRole.processing = true;
    try {
        await api.post('/super-admin/roles-data', { name: newRole.name });
        newRole.name = '';
        toast.success('Role berhasil dibuat');
        await fetchData();
    } catch (e: any) {
        toast.error(e.response?.data?.message || 'Gagal membuat role');
    } finally {
        newRole.processing = false;
    }
};

const syncPermissions = async () => {
    if (!selectedRole.value) return;
    permissionForm.processing = true;
    try {
        await api.put(`/super-admin/roles-data/${selectedRole.value}`, {
            name: roles.value.find(r => r.id === selectedRole.value)?.name,
            permissions: permissionForm.permissions
        });
        toast.success('Izin role berhasil diperbarui');
        await fetchData();
    } catch (e: any) {
        toast.error('Gagal memperbarui izin');
    } finally {
        permissionForm.processing = false;
    }
};

const deleteRole = async (id: number) => {
    if (!confirm('Hapus role ini?')) return;
    try {
        await api.delete(`/super-admin/roles-data/${id}`);
        toast.success('Role dihapus');
        if (selectedRole.value === id) selectedRole.value = null;
        await fetchData();
    } catch (e: any) {
        toast.error(e.response?.data?.message || 'Gagal menghapus role');
    }
};

onMounted(() => {
    hydrateInitialData();

    if (roles.value.length === 0 && permissions.value.length === 0) {
        fetchData();
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
          <h1 class="text-4xl font-black text-slate-900 dark:text-white mb-2 tracking-tight">{{ t('sidebar.role_permission') }}</h1>
          <p class="text-slate-500 dark:text-slate-400 font-medium">{{ t('admin.roles.desc') || 'Kelola tingkat akses dan izin granular untuk setiap peran pengguna.' }}</p>
        </div>
        
        <form @submit.prevent="createRole" class="flex items-center gap-3">
          <input 
            v-model="newRole.name"
            type="text" 
            :placeholder="t('admin.roles.new_placeholder') || 'Nama role baru...'" 
            class="px-6 py-4 bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-2xl text-sm font-bold focus:outline-none focus:ring-4 focus:ring-primary-500/10 w-64 transition-all"
          />
          <button 
            type="submit"
            :disabled="newRole.processing"
            class="flex items-center gap-2 px-8 py-4 bg-primary-600 text-white rounded-2xl text-sm font-black hover:bg-primary-700 transition-all shadow-lg shadow-primary-500/20 active:scale-95 disabled:opacity-50"
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
           <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] px-4">{{ t('admin.roles.list_title') || 'Daftar Role' }}</h3>
           <div class="space-y-3">
              <div 
                v-for="role in roles" 
                :key="role.id"
                class="relative group"
              >
                <button 
                  @click="selectRole(role)"
                  :class="[
                    'w-full text-left px-8 py-6 rounded-[2rem] transition-all flex items-center justify-between group overflow-hidden relative',
                    selectedRole === role.id 
                      ? 'bg-slate-900 text-white shadow-2xl shadow-slate-900/20 dark:bg-primary-600' 
                      : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800 border border-slate-100 dark:border-slate-800'
                  ]"
                >
                  <div class="flex items-center gap-4 relative z-10">
                     <div :class="[
                        'w-10 h-10 rounded-xl flex items-center justify-center transition-colors',
                        selectedRole === role.id ? 'bg-white/10 text-white' : 'bg-primary-50 text-primary-600 dark:bg-primary-900/20'
                     ]">
                        <Shield class="w-5 h-5" />
                     </div>
                     <span class="font-black text-sm tracking-tight capitalize">{{ role.name.replace(/_/g, ' ') }}</span>
                  </div>
                  
                  <div v-if="selectedRole === role.id" class="absolute right-0 top-0 h-full w-24 bg-gradient-to-l from-white/10 to-transparent"></div>
                </button>
                <button 
                  v-if="role.name !== 'super_admin'"
                  @click.stop="deleteRole(role.id)"
                  class="absolute right-6 top-1/2 -translate-y-1/2 p-3 text-rose-500 opacity-0 group-hover:opacity-100 transition-all hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-2xl"
                >
                  <Trash2 class="w-5 h-5" />
                </button>
              </div>
           </div>
        </div>

        <!-- Permissions Matrix -->
        <div class="lg:col-span-8">
           <Card v-if="selectedRole" class="!p-10 border-none shadow-2xl !rounded-[3.5rem] min-h-[500px] relative overflow-hidden bg-white dark:bg-slate-900">
              <div class="absolute top-0 right-0 w-64 h-64 bg-primary-500/5 blur-[100px] rounded-full -mr-32 -mt-32"></div>
              
              <div class="flex flex-col md:flex-row md:items-center justify-between mb-12 gap-6 relative z-10">
                 <div>
                    <h2 class="text-2xl font-black text-slate-900 dark:text-white leading-tight">
                       {{ t('admin.roles.permissions_for') || 'Izin Role:' }} 
                       <span class="text-primary-600 capitalize">{{ roles.find(r => r.id === selectedRole)?.name.replace(/_/g, ' ') }}</span>
                    </h2>
                    <p class="text-xs font-bold text-slate-400 mt-2 uppercase tracking-widest">{{ t('admin.roles.permissions_desc') || 'Centang izin yang ingin diberikan pada role ini.' }}</p>
                 </div>
                 <button 
                   @click="syncPermissions"
                   :disabled="permissionForm.processing"
                   class="flex items-center gap-3 px-10 py-5 bg-slate-900 dark:bg-primary-600 text-white rounded-[1.5rem] text-sm font-black hover:bg-slate-800 transition-all shadow-xl shadow-slate-900/20 active:scale-95 disabled:opacity-50"
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
                       <p class="text-xs font-black text-slate-800 dark:text-slate-200 uppercase tracking-tight">{{ permission.name.replace(/_/g, ' ') }}</p>
                       <p class="text-[9px] font-bold text-slate-400 font-mono mt-0.5">{{ permission.name }}</p>
                    </div>
                 </div>
              </div>
           </Card>

           <div v-else class="h-full flex items-center justify-center bg-slate-50/50 dark:bg-slate-900/50 rounded-[4rem] border-4 border-dashed border-slate-100 dark:border-slate-800">
              <div class="text-center p-20">
                 <div class="w-24 h-24 bg-white dark:bg-slate-800 rounded-[2rem] shadow-sm flex items-center justify-center mx-auto mb-8">
                    <Key class="w-10 h-10 text-slate-200" />
                 </div>
                 <p class="text-lg font-black text-slate-400 uppercase tracking-widest">{{ t('admin.roles.select_prompt') || 'Pilih role untuk mengelola izin.' }}</p>
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
