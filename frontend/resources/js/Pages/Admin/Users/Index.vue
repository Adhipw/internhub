<script setup lang="ts">
import logger from '@/Lib/logger';
import { ref, reactive, onMounted, onUnmounted, watch, computed } from 'vue';
import { router as inertiaRouter } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import Pagination from '@/Components/Pagination.vue';
import EmptyState from '@/Components/EmptyState.vue';
import { 
  Users, Search, ShieldCheck, ShieldAlert,
  Trash2, UserX, UserCheck, MoreVertical,
  Filter, Loader2
} from 'lucide-vue-next';
import { formatDate } from '@/Lib/utils';
import { useLangStore } from '@/Stores/lang';
import api from '@/Services/api';
import type { User, PaginatedResponse, Role } from '@/Types/user';

const urlParams = new URLSearchParams(window.location.search);
const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const props = defineProps<{
    users?: PaginatedResponse<User>;
    filters?: any;
}>();

const loading = ref(false);
const processing = ref(false);
const users = computed(() => props.users || {
  data: [],
  links: [],
  meta: {} as any
});

const filters = reactive({
  search: urlParams.get('search') || '',
  role: urlParams.get('role') || '',
  status: urlParams.get('status') || '',
  page: urlParams.get('page') || 1
});

const fetchUsers = () => {
    inertiaRouter.get('/admin/users', { ...filters }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const handleSearch = () => {
    filters.page = 1;
    updateQuery();
};

const updateQuery = () => {
    fetchUsers();
};

const toggleStatus = async (userId: number) => {
    if (confirm(t('admin.user_mgmt.toggle_confirm') || 'Apakah Anda yakin ingin mengubah status pengguna ini?')) {
        processing.value = true;
        try {
            await api.post(`/admin/users/${userId}/toggle-status`);
            inertiaRouter.reload({ only: ['users'] });
        } catch (error) {
            alert(t('common.error_occurred'));
        } finally {
            processing.value = false;
        }
    }
};

const deleteUser = async (userId: number) => {
    if (confirm(t('admin.user_mgmt.delete_confirm') || 'Apakah Anda yakin ingin menghapus pengguna ini secara permanen?')) {
        processing.value = true;
        try {
            await api.delete(`/admin/users/${userId}`);
            inertiaRouter.reload({ only: ['users'] });
        } catch (error) {
            alert(t('common.error_occurred'));
        } finally {
            processing.value = false;
        }
    }
};

let refreshInterval: any = null;

onMounted(() => {
    // Auto refresh every 60 seconds for real-time feel
    refreshInterval = setInterval(() => {
        inertiaRouter.reload({ only: ['users'] });
    }, 60000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});
</script>

<template>
  <DashboardLayout>
    <div class="space-y-10">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div>
          <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-2">{{ t('admin.user_mgmt.title') }}</h1>
          <p class="text-slate-500 dark:text-slate-400">{{ t('admin.user_mgmt.desc') }}</p>
        </div>
        
        <div class="flex items-center gap-3">
          <div class="relative group">
            <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary-600 transition-colors" />
            <input 
              type="text" 
              v-model="filters.search"
              @keyup.enter="handleSearch"
              :placeholder="t('admin.user_mgmt.search_placeholder')" 
              class="pl-11 pr-6 py-3 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all w-64"
            />
          </div>
        </div>
      </div>

      <!-- Users Table -->
      <Card class="overflow-hidden border-none shadow-sm relative">
        <div v-if="loading" class="absolute inset-0 bg-white/50 dark:bg-slate-900/50 backdrop-blur-[1px] z-10 flex items-center justify-center">
            <Loader2 class="w-8 h-8 text-primary-600 animate-spin" />
        </div>

        <div v-if="users.data.length > 0" class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800">
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('admin.user_mgmt.col_user') }}</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">{{ t('admin.user_mgmt.col_role') }}</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">{{ t('admin.user_mgmt.col_status') }}</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">{{ t('admin.user_mgmt.col_joined') }}</th>
                <th class="px-8 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">{{ t('admin.user_mgmt.col_actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
              <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition-colors group">
                <td class="px-8 py-6">
                  <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-sm font-bold text-slate-500">
                      {{ user.name.charAt(0) }}
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        {{ user.name }}
                        <ShieldCheck v-if="user.roles?.some((r: Role) => r.name === 'super_admin')" class="w-3 h-3 text-red-500" />
                      </p>
                      <p class="text-xs text-slate-500">{{ user.email }}</p>
                    </div>
                  </div>
                </td>
                <td class="px-8 py-6 text-center">
                  <div class="flex flex-wrap justify-center gap-1">
                    <span 
                      v-for="roleName in (user.all_roles || [user.role])"
                      :key="roleName"
                      class="px-3 py-1 rounded-lg text-[10px] font-bold uppercase tracking-widest inline-flex items-center gap-1.5"
                      :class="{
                        'bg-red-50 text-red-600 border border-red-100': roleName === 'super_admin',
                        'bg-orange-50 text-orange-600 border border-orange-100': roleName === 'admin',
                        'bg-blue-50 text-blue-600 border border-blue-100': roleName === 'hr',
                        'bg-slate-100 text-slate-600 border border-slate-200': ['student', 'user'].includes(roleName),
                        'bg-purple-50 text-purple-600 border border-purple-100': roleName === 'mentor',
                      }"
                    >
                      {{ 
                        roleName === 'super_admin' ? 'Super Admin' : 
                        roleName === 'admin' ? 'Administrator' : 
                        roleName === 'hr' ? 'HR Company' : 
                        roleName === 'mentor' ? 'Mentor' : 
                        ['student', 'user'].includes(roleName) ? 'Student' : 
                        roleName.toUpperCase() 
                      }}
                    </span>
                  </div>
                </td>
                <td class="px-8 py-6 text-center">
                  <div class="flex items-center justify-center">
                    <div 
                      class="flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest"
                      :class="user.is_active ? 'bg-green-50 text-green-600' : 'bg-slate-100 text-slate-400'"
                    >
                      <div class="w-1.5 h-1.5 rounded-full" :class="user.is_active ? 'bg-green-500' : 'bg-slate-300'"></div>
                      {{ user.is_active ? t('admin.user_mgmt.status_active') : t('admin.user_mgmt.status_inactive') }}
                    </div>
                  </div>
                </td>
                <td class="px-8 py-6">
                  <p class="text-xs text-slate-500">{{ user.created_at_human }}</p>
                </td>
                <td class="px-8 py-6 text-right">
                  <div v-if="!user.roles?.some((r: Role) => r.name === 'super_admin')" class="flex items-center justify-end gap-2">
                    <button 
                      @click="toggleStatus(user.id)"
                      :disabled="processing"
                      class="p-2 rounded-xl transition-all"
                      :class="user.is_active ? 'text-slate-400 hover:bg-orange-50 hover:text-orange-600' : 'text-slate-400 hover:bg-green-50 hover:text-green-600'"
                      :title="t('admin.user_mgmt.toggle_tooltip')"
                    >
                      <UserX v-if="user.is_active" class="w-4 h-4" />
                      <UserCheck v-else class="w-4 h-4" />
                    </button>
                    <button 
                      @click="deleteUser(user.id)"
                      :disabled="processing"
                      class="p-2 text-slate-400 hover:bg-red-50 hover:text-red-600 rounded-xl transition-all"
                      :title="t('admin.user_mgmt.delete_tooltip')"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                  <div v-else class="text-right">
                     <ShieldAlert class="w-4 h-4 text-slate-200 inline-block" :title="t('admin.user_mgmt.super_admin_notice')" />
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <EmptyState 
            v-else-if="!loading"
            type="empty"
            :title="t('admin.user_mgmt.empty')"
            description="Tidak ada pengguna yang sesuai dengan kriteria yang diberikan."
        />

        <!-- Pagination -->
        <div v-if="users.links && users.links.length > 3" class="px-8 py-6 border-t border-slate-50 dark:border-slate-800">
            <Pagination :links="users.links" />
        </div>
      </Card>
    </div>
  </DashboardLayout>
</template>
