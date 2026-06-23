<script setup lang="ts">
import logger from '@/Lib/logger';
import { ref, reactive, onMounted, onUnmounted, watch, computed } from 'vue';
import { router as inertiaRouter } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';
import { 
  Users, Search, ShieldCheck, ShieldAlert, CheckCircle2,
  Trash2, UserCog, Check, X, MoreVertical, Ban, Unlock,
  Plus, Filter, AlertCircle, Phone, Mail, User as UserIcon,
  Lock, Eye, EyeOff, Loader2, Download, Image as ImageIcon
} from 'lucide-vue-next';
import { formatDate } from '@/Lib/utils';
import { useDebounceFn } from '@vueuse/core';
import { useLangStore } from '@/Stores/lang';
import { useToastStore } from '@/Stores/toast';
import api from '@/Services/api';
import ImportModal from '@/Components/ImportModal.vue';
import type { User, PaginatedResponse } from '@/Types/user';

const langStore = useLangStore();
const toast = useToastStore();
const t = (key: string) => langStore.t(key);
const __ = t;

const props = defineProps<{
    users?: PaginatedResponse<User>;
    roles?: string[];
    filters?: {
        search?: string;
        role?: string;
        status?: string;
    };
}>();

const loading = ref(false);
const processing = ref(false);
const users = computed(() => props.users || {
    data: [],
    links: [],
    meta: {} as any
});
const roles = computed(() => props.roles || ['admin', 'hr', 'mentor', 'user']);

// Batch Import
const showImportModal = ref(false);
const handleImportSuccess = () => {
    showImportModal.value = false;
    inertiaRouter.reload();
};

const exportUsers = () => {
    window.open('/api/v1/super-admin/users/export', '_blank');
};

// Filters
const search = ref(props.filters?.search || '');
const roleFilter = ref(props.filters?.role || '');
const statusFilter = ref(props.filters?.status || '');


const fetchUsers = (page = 1) => {
    inertiaRouter.get('/super-admin/users', {
        page,
        search: search.value,
        role: roleFilter.value,
        status: statusFilter.value
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
};

const debouncedSearch = useDebounceFn(() => {
    fetchUsers(1);
}, 500);

watch([search, roleFilter, statusFilter], () => {
    debouncedSearch();
});

// Create User
const showCreateModal = ref(false);
const showPassword = ref(false);
const createForm = reactive({
    name: '',
    email: '',
    phone_number: '',
    password: '',
    password_confirmation: '',
    role: 'hr',
    is_active: true,
    avatar: null as File | null,
    errors: {} as any
});


const openCreateModal = () => {
    createForm.name = '';
    createForm.email = '';
    createForm.phone_number = '';
    createForm.password = '';
    createForm.password_confirmation = '';
    createForm.role = 'hr';
    createForm.is_active = true;
    createForm.avatar = null;
    createForm.errors = {};
    showCreateModal.value = true;
};


const appendFormValue = (formData: FormData, key: string, value: unknown) => {
    if (value instanceof File) {
        formData.append(key, value);
        return;
    }

    if (typeof value === 'boolean') {
        formData.append(key, value ? '1' : '0');
        return;
    }

    formData.append(key, String(value));
};

const getApiErrorMessage = (error: any) => {
    return error.response?.data?.message || t('common.error_occurred');
};

const submitCreate = () => {
    processing.value = true;
    createForm.errors = {};
    
    const formData = new FormData();
    Object.keys(createForm).forEach(key => {
        if (key === 'errors') return;
        const val = (createForm as any)[key];
        if (val !== null && val !== undefined && val !== '') {
            appendFormValue(formData, key, val);
        }
    });

    inertiaRouter.post('/super-admin/users', formData, {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
        },
        onError: (errors) => {
            createForm.errors = errors;
            toast.error(t('common.error_occurred'));
        },
        onFinish: () => {
            processing.value = false;
        }
    });
};

const handleAvatarChange = (event: any, mode: 'create' | 'edit') => {
    const file = event.target.files[0];
    if (file) {
        if (mode === 'create') createForm.avatar = file;
        else editForm.avatar = file;
    }
};


// Edit User
const showEditModal = ref(false);
const editingUser = ref<User | null>(null);
const editForm = reactive({
    name: '',
    email: '',
    phone_number: '',
    password: '',
    password_confirmation: '',
    role: '',
    is_active: true,
    avatar: null as File | null,
    errors: {} as any
});


const openEditModal = (user: User) => {
    editingUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.phone_number = user.phone_number || '';
    editForm.role = user.role;
    editForm.is_active = !!user.is_active;
    editForm.password = '';
    editForm.password_confirmation = '';
    editForm.avatar = null;
    editForm.errors = {};
    showEditModal.value = true;
};


const submitEdit = () => {
    processing.value = true;
    editForm.errors = {};
    
    const formData = new FormData();
    // Use _method: PUT for Laravel to handle multipart in PUT/PATCH
    formData.append('_method', 'PUT');
    Object.keys(editForm).forEach(key => {
        if (key === 'errors') return;
        const val = (editForm as any)[key];
        if (val !== null && val !== undefined && val !== '') {
            appendFormValue(formData, key, val);
        }
    });

    if (!editingUser.value) {
        processing.value = false;
        return;
    }

    inertiaRouter.post(`/super-admin/users/${editingUser.value.id}`, formData, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
        },
        onError: (errors) => {
            editForm.errors = errors;
            toast.error(t('common.error_occurred'));
        },
        onFinish: () => {
            processing.value = false;
        }
    });
};


// Confirm Actions
const showConfirmModal = ref(false);
const confirmActionType = ref<'ban' | 'delete' | 'unban'>('ban');
const confirmUser = ref<User | null>(null);
const banReason = ref('');

const openConfirmModal = (type: 'ban' | 'delete' | 'unban', user: User) => {
    confirmActionType.value = type;
    confirmUser.value = user;
    banReason.value = '';
    showConfirmModal.value = true;
};

const handleConfirmedAction = () => {
    if (!confirmUser.value) return;

    processing.value = true;
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            showConfirmModal.value = false;
        },
        onError: () => {
            toast.error(t('common.error_occurred'));
        },
        onFinish: () => {
            processing.value = false;
        }
    };

    if (confirmActionType.value === 'delete') {
        inertiaRouter.delete(`/super-admin/users/${confirmUser.value.id}`, options);
    } else if (confirmActionType.value === 'ban') {
        if (!banReason.value) {
            alert('Alasan blokir harus diisi.');
            processing.value = false;
            return;
        }
        inertiaRouter.patch(`/super-admin/users/${confirmUser.value.id}/ban`, { reason: banReason.value }, options);
    } else if (confirmActionType.value === 'unban') {
        inertiaRouter.patch(`/super-admin/users/${confirmUser.value.id}/unban`, {}, options);
    }
};

const getObjectURL = (file: File | null) => {
    return file ? URL.createObjectURL(file) : '';
};

</script>

<template>
  <DashboardLayout>
    <div class="space-y-8">
      <!-- Header Section -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
        <div>
          <h1 class="text-3xl font-black text-slate-900 dark:text-white mb-2 tracking-tight">{{ t('admin.user_mgmt.title') }}</h1>
          <p class="text-slate-500 dark:text-slate-400">{{ t('admin.user_mgmt.desc') }}</p>
        </div>
        
        <div class="flex items-center gap-3">
          <button 
            @click="exportUsers"
            class="flex items-center justify-center gap-2 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-6 py-3.5 rounded-2xl font-bold transition-all border border-slate-200 dark:border-slate-700 active:scale-95 hover:bg-slate-200 dark:hover:bg-slate-700"
          >
            <Download class="w-5 h-5" />
            Export CSV
          </button>
          <button 
            @click="showImportModal = true"
            class="flex items-center justify-center gap-2 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-6 py-3.5 rounded-2xl font-bold transition-all border border-slate-200 dark:border-slate-700 active:scale-95 hover:bg-slate-50 dark:hover:bg-slate-700"
          >
            <Download class="w-5 h-5" />
            Import
          </button>
          <button 
            @click="openCreateModal"
            class="flex items-center justify-center gap-2 bg-primary-600 hover:bg-primary-700 text-white px-6 py-3.5 rounded-2xl font-bold transition-all shadow-lg shadow-primary-500/20 active:scale-95"
          >
            <Plus class="w-5 h-5" />
            {{ t('admin.user_mgmt.add_user') }}
          </button>
        </div>
      </div>

      <!-- Filters Bar -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-white dark:bg-slate-900 p-4 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm">
        <div class="md:col-span-2 relative group">
          <Search class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary-600 transition-colors" />
          <input 
            v-model="search"
            type="text" 
            :placeholder="t('admin.user_mgmt.search_placeholder')" 
            class="w-full pl-11 pr-6 py-3 bg-slate-50 dark:bg-slate-800 dark:text-white border-none rounded-2xl text-sm focus:ring-2 focus:ring-primary-500/20"
          />
        </div>
        
        <div class="relative">
          <Filter class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
          <select 
            v-model="roleFilter"
            class="w-full pl-11 pr-6 py-3 bg-slate-50 dark:bg-slate-800 dark:text-white border-none rounded-2xl text-sm focus:ring-2 focus:ring-primary-500/20 appearance-none"
          >
            <option value="">{{ t('admin.user_mgmt.filter_role') }}</option>
            <option v-for="role in roles" :key="role" :value="role">{{ role.toUpperCase() }}</option>
          </select>
        </div>

        <div class="relative">
          <ShieldCheck class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
          <select 
            v-model="statusFilter"
            class="w-full pl-11 pr-6 py-3 bg-slate-50 dark:bg-slate-800 dark:text-white border-none rounded-2xl text-sm focus:ring-2 focus:ring-primary-500/20 appearance-none"
          >
            <option value="">{{ t('admin.user_mgmt.filter_status') }}</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
          </select>
        </div>
      </div>

      <!-- Users Table -->
      <Card class="overflow-hidden border-none shadow-xl relative min-h-[400px] !rounded-[3rem]">
        <div v-if="loading" class="absolute inset-0 bg-white/60 dark:bg-slate-900/60 z-20 flex items-center justify-center">
            <Loader2 class="w-10 h-10 text-primary-600 animate-spin" />
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-slate-50/50 dark:bg-slate-900/50 border-b border-slate-100 dark:border-slate-800 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">
                <th class="px-8 py-6">{{ t('admin.user_mgmt.col_user') }}</th>
                <th class="px-4 py-6 text-center">{{ t('admin.user_mgmt.col_role') }}</th>
                <th class="px-4 py-6 text-center">{{ t('admin.user_mgmt.col_status') }}</th>
                <th class="px-4 py-6 hidden md:table-cell">{{ t('admin.user_mgmt.col_joined') }}</th>
                <th class="px-8 py-6 text-right">{{ t('admin.user_mgmt.col_actions') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-50 dark:divide-slate-800/50">
              <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-50/30 dark:hover:bg-slate-900/20 transition-colors group">
                <td class="px-6 py-6">
                  <div class="flex items-center gap-4">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-primary-500 to-indigo-600 flex items-center justify-center text-white font-black shadow-lg shadow-primary-500/20 shrink-0 overflow-hidden">
                      <img v-if="user.avatar_url" :src="user.avatar_url" class="w-full h-full object-cover" />
                      <span v-else>{{ user.name.charAt(0) }}</span>
                    </div>
                    <div class="flex flex-col min-w-0">
                      <span class="font-bold text-slate-900 dark:text-white truncate">{{ user.name }}</span>
                      <div class="flex flex-col gap-0.5 mt-0.5">
                        <span class="text-[10px] text-slate-400 font-medium flex items-center gap-1">
                          <Mail class="w-3 h-3" /> {{ user.email }}
                        </span>
                        <span v-if="user.phone_number" class="text-[10px] text-slate-400 font-medium flex items-center gap-1">
                          <Phone class="w-3 h-3" /> {{ user.phone_number }}
                        </span>
                      </div>
                    </div>
                  </div>
                </td>
                <td class="px-4 py-6 text-center">
                  <span 
                    class="inline-flex px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-widest border"
                    :class="{
                      'bg-red-50 text-red-600 border-red-100': user.role === 'super_admin',
                      'bg-orange-50 text-orange-600 border-orange-100': user.role === 'admin',
                      'bg-blue-50 text-blue-600 border-blue-100': user.role === 'hr',
                      'bg-slate-50 text-slate-600 border-slate-100': user.role === 'user',
                      'bg-purple-50 text-purple-600 border-purple-100': user.role === 'mentor',
                    }"
                  >
                    {{ user.role }}
                  </span>
                </td>
                <td class="px-4 py-6 text-center">
                  <div class="flex flex-col items-center gap-1">
                    <span v-if="user.banned_at" class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-red-100 text-red-700 rounded-md text-[9px] font-black uppercase tracking-wider">
                      <Ban class="w-2.5 h-2.5" /> Banned
                    </span>
                    <span v-else-if="!user.is_active" class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-amber-100 text-amber-700 rounded-md text-[9px] font-black uppercase tracking-wider">
                      <ShieldAlert class="w-2.5 h-2.5" /> Inactive
                    </span>
                    <span v-else class="inline-flex items-center gap-1 px-1.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-md text-[9px] font-black uppercase tracking-wider">
                      <CheckCircle2 class="w-2.5 h-2.5" /> Active
                    </span>
                  </div>
                </td>
                <td class="px-4 py-6 hidden md:table-cell">
                  <span class="text-[10px] text-slate-500 font-medium">{{ formatDate(user.created_at) }}</span>
                </td>
                <td class="px-8 py-6 text-right">
                  <div class="flex items-center justify-end gap-1">
                    <button 
                      type="button"
                      @click="openEditModal(user)" 
                      class="p-2 text-slate-400 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30 rounded-xl transition-all active:scale-95" 
                      title="Edit Data"
                    >
                      <UserCog class="w-4 h-4" />
                    </button>
                    
                    <button 
                      v-if="user.banned_at" 
                      type="button"
                      @click="openConfirmModal('unban', user)" 
                      class="p-2 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-xl transition-all active:scale-95" 
                      title="Unban"
                    >
                      <Unlock class="w-4 h-4" />
                    </button>
                    
                    <button 
                      v-else 
                      type="button"
                      @click="openConfirmModal('ban', user)" 
                      class="p-2 text-slate-400 hover:text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-900/30 rounded-xl transition-all active:scale-95" 
                      title="Ban"
                    >
                      <Ban class="w-4 h-4" />
                    </button>
                    
                    <button 
                      v-if="user.role !== 'super_admin'"
                      type="button"
                      @click="openConfirmModal('delete', user)" 
                      class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-xl transition-all active:scale-95" 
                      title="Delete"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="users.data.length === 0 && !loading">
                <td colspan="5" class="px-8 py-20 text-center">
                  <div class="flex flex-col items-center justify-center text-slate-400">
                    <Users class="w-12 h-12 mb-4 opacity-20" />
                    <p class="font-bold">{{ t('admin.user_mgmt.empty') }}</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        
        <!-- Pagination -->
        <div v-if="users.links && users.links.length > 3" class="px-6 py-4 border-t border-slate-50 dark:border-slate-800">
            <Pagination :links="users.links" />
        </div>
      </Card>
    </div>

    <!-- Confirm Action Modal -->
    <Modal :show="showConfirmModal" @close="showConfirmModal = false" max-width="md">
      <div class="p-8 text-center">
        <div :class="{
          'bg-orange-50 text-orange-600': confirmActionType === 'ban',
          'bg-red-50 text-red-600': confirmActionType === 'delete',
          'bg-emerald-50 text-emerald-600': confirmActionType === 'unban'
        }" class="w-20 h-20 rounded-[2rem] flex items-center justify-center mx-auto mb-6 shadow-xl shadow-current/5">
          <Ban v-if="confirmActionType === 'ban'" class="w-10 h-10" />
          <Trash2 v-if="confirmActionType === 'delete'" class="w-10 h-10" />
          <Unlock v-if="confirmActionType === 'unban'" class="w-10 h-10" />
        </div>

        <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-2 leading-tight">
          {{ confirmActionType === 'ban' ? t('admin.user_mgmt.ban_confirm_title') : confirmActionType === 'delete' ? t('admin.user_mgmt.delete_confirm_title') : t('admin.user_mgmt.unban_confirm_title') }}
        </h3>
        <p class="text-slate-500 dark:text-slate-400 mb-8 text-sm">
          {{ confirmActionType === 'ban' ? `Anda akan memblokir akses untuk ${confirmUser?.email}.` : confirmActionType === 'delete' ? `Tindakan ini akan menghapus permanen data ${confirmUser?.email}.` : `Akses untuk ${confirmUser?.email} akan dikembalikan.` }}
        </p>

        <div v-if="confirmActionType === 'ban'" class="mb-8 text-left space-y-2">
            <label class="text-[10px] font-black uppercase tracking-widest text-slate-400 ml-1">Alasan Pemblokiran</label>
            <textarea 
                v-model="banReason"
                rows="3"
                class="w-full p-4 bg-slate-50 dark:bg-slate-800 dark:text-white border-none rounded-2xl focus:ring-2 focus:ring-orange-500/20 text-sm"
                placeholder="Masukkan alasan pemblokiran agar user tahu..."
            ></textarea>
        </div>

        <div class="flex gap-4">
          <button @click="showConfirmModal = false" class="flex-1 px-6 py-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl font-bold transition-all hover:bg-slate-200">
            {{ t('admin.user_mgmt.cancel_btn') }}
          </button>
          <button 
            @click="handleConfirmedAction"
            :disabled="processing"
            :class="{
              'bg-orange-600 hover:bg-orange-700 shadow-orange-500/20': confirmActionType === 'ban',
              'bg-red-600 hover:bg-red-700 shadow-red-500/20': confirmActionType === 'delete',
              'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-500/20': confirmActionType === 'unban'
            }"
            class="flex-1 px-6 py-4 text-white rounded-2xl font-black shadow-lg transition-all active:scale-95 flex items-center justify-center gap-2"
          >
            <Loader2 v-if="processing" class="w-4 h-4 animate-spin" />
            {{ t('admin.user_mgmt.continue_btn') }}
          </button>
        </div>
      </div>
    </Modal>

    <!-- Create User Modal -->
    <Modal :show="showCreateModal" @close="showCreateModal = false" max-width="2xl">
      <div class="p-8">
        <div class="flex items-center justify-between mb-8">
          <div>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white">Tambah Akun Baru</h2>
            <p class="text-sm text-slate-500">Buat akun untuk HR, Mentor, atau Admin baru.</p>
          </div>
          <button @click="showCreateModal = false" class="p-2 hover:bg-slate-100 rounded-xl transition-colors">
            <X class="w-6 h-6 text-slate-400" />
          </button>
        </div>

        <form @submit.prevent="submitCreate" class="space-y-6">
          <div class="flex flex-col items-center gap-4 mb-8">
            <div class="w-24 h-24 rounded-full bg-slate-100 dark:bg-slate-800 border-4 border-white dark:border-slate-900 shadow-xl overflow-hidden group relative">
               <img v-if="createForm.avatar" :src="getObjectURL(createForm.avatar)" class="w-full h-full object-cover" />
               <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                  <ImageIcon class="w-10 h-10" />
               </div>
               <label class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                  <input type="file" @change="e => handleAvatarChange(e, 'create')" class="hidden" accept="image/*" />
                  <span class="text-white text-[10px] font-black uppercase tracking-tighter">Ganti Foto</span>
               </label>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Foto Profil (Opsional)</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Nama Lengkap</label>
              <div class="relative group">
                <UserIcon class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary-600" />
                <input v-model="createForm.name" type="text" class="w-full pl-11 pr-6 py-3.5 bg-slate-50 dark:bg-slate-800 dark:text-white border-none rounded-2xl focus:ring-2 focus:ring-primary-500/20" placeholder="Contoh: John Doe" />
              </div>
              <div v-if="createForm.errors.name" class="text-xs text-red-500 font-bold">{{ createForm.errors.name[0] }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Alamat Email</label>
              <div class="relative group">
                <Mail class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary-600" />
                <input v-model="createForm.email" type="email" class="w-full pl-11 pr-6 py-3.5 bg-slate-50 dark:bg-slate-800 dark:text-white border-none rounded-2xl focus:ring-2 focus:ring-primary-500/20" placeholder="email@contoh.com" />
              </div>
              <div v-if="createForm.errors.email" class="text-xs text-red-500 font-bold">{{ createForm.errors.email[0] }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Nomor Telepon (Opsional)</label>
              <div class="relative group">
                <Phone class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary-600" />
                <input v-model="createForm.phone_number" type="text" class="w-full pl-11 pr-6 py-3.5 bg-slate-50 dark:bg-slate-800 dark:text-white border-none rounded-2xl focus:ring-2 focus:ring-primary-500/20" placeholder="081234..." />
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Role Pengguna</label>
              <div class="relative">
                <ShieldCheck class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
                <select v-model="createForm.role" class="w-full pl-11 pr-6 py-3.5 bg-slate-50 dark:bg-slate-800 dark:text-white border-none rounded-2xl focus:ring-2 focus:ring-primary-500/20 appearance-none">
                  <option value="hr">HR (Human Resources)</option>
                  <option value="mentor">MENTOR</option>
                  <option value="admin">ADMINISTRATOR</option>
                </select>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Password</label>
              <div class="relative group">
                <Lock class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary-600" />
                <input v-model="createForm.password" :type="showPassword ? 'text' : 'password'" class="w-full pl-11 pr-12 py-3.5 bg-slate-50 dark:bg-slate-800 dark:text-white border-none rounded-2xl focus:ring-2 focus:ring-primary-500/20" placeholder="••••••••" />
                <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                  <Eye v-if="!showPassword" class="w-4 h-4" />
                  <EyeOff v-else class="w-4 h-4" />
                </button>
              </div>
              <div v-if="createForm.errors.password" class="text-xs text-red-500 font-bold">{{ createForm.errors.password[0] }}</div>
            </div>

            <div class="space-y-2">
              <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Konfirmasi Password</label>
              <div class="relative group">
                <Lock class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 group-focus-within:text-primary-600" />
                <input v-model="createForm.password_confirmation" :type="showPassword ? 'text' : 'password'" class="w-full pl-11 pr-6 py-3.5 bg-slate-50 dark:bg-slate-800 dark:text-white border-none rounded-2xl focus:ring-2 focus:ring-primary-500/20" placeholder="••••••••" />
              </div>
            </div>
          </div>

          <div class="bg-primary-50 dark:bg-primary-900/20 p-6 rounded-3xl flex items-center justify-between border border-primary-100 dark:border-primary-800">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-white dark:bg-slate-900 rounded-xl flex items-center justify-center text-primary-600 shadow-sm">
                <CheckCircle2 class="w-5 h-5" />
              </div>
              <div>
                <p class="text-sm font-bold text-slate-900 dark:text-white">Status Akun</p>
                <p class="text-xs text-slate-500">Akun akan langsung aktif setelah dibuat.</p>
              </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="createForm.is_active" class="sr-only peer">
              <div class="w-14 h-7 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-primary-600"></div>
            </label>
          </div>

          <div class="flex gap-4 pt-4">
            <button type="button" @click="showCreateModal = false" class="flex-1 px-6 py-4 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold transition-all">
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="processing"
              class="flex-1 px-6 py-4 bg-primary-600 hover:bg-primary-700 text-white rounded-2xl font-black shadow-lg shadow-primary-500/30 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <Loader2 v-if="processing" class="w-4 h-4 animate-spin" />
              {{ processing ? 'Menyimpan...' : 'Simpan Akun' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- Edit User Modal -->
    <Modal :show="showEditModal" @close="showEditModal = false" max-width="2xl">
      <div class="p-8">
        <div class="flex items-center justify-between mb-8">
          <div>
            <h2 class="text-2xl font-black text-slate-900 dark:text-white">Edit Akun</h2>
            <p class="text-sm text-slate-500">Memperbarui data akun: <span class="font-bold text-primary-600">{{ editingUser?.name }}</span></p>
          </div>
          <button @click="showEditModal = false" class="p-2 hover:bg-slate-100 rounded-xl transition-colors">
            <X class="w-6 h-6 text-slate-400" />
          </button>
        </div>

        <form @submit.prevent="submitEdit" class="space-y-6">
          <div class="flex flex-col items-center gap-4 mb-8">
            <div class="w-24 h-24 rounded-full bg-slate-100 dark:bg-slate-800 border-4 border-white dark:border-slate-900 shadow-xl overflow-hidden group relative">
               <img v-if="editForm.avatar" :src="getObjectURL(editForm.avatar)" class="w-full h-full object-cover" />
               <img v-else-if="editingUser?.avatar_url" :src="editingUser.avatar_url" class="w-full h-full object-cover" />
               <div v-else class="w-full h-full flex items-center justify-center text-slate-400">
                  <ImageIcon class="w-10 h-10" />
               </div>
               <label class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                  <input type="file" @change="e => handleAvatarChange(e, 'edit')" class="hidden" accept="image/*" />
                  <span class="text-white text-[10px] font-black uppercase tracking-tighter">Ganti Foto</span>
               </label>
            </div>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Update Foto Profil</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-2">
              <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Nama Lengkap</label>
              <input v-model="editForm.name" type="text" class="w-full px-6 py-3.5 bg-slate-50 dark:bg-slate-800 dark:text-white border-none rounded-2xl focus:ring-2 focus:ring-primary-500/20" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Alamat Email</label>
              <input v-model="editForm.email" type="email" class="w-full px-6 py-3.5 bg-slate-50 dark:bg-slate-800 dark:text-white border-none rounded-2xl focus:ring-2 focus:ring-primary-500/20" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Nomor Telepon</label>
              <input v-model="editForm.phone_number" type="text" class="w-full px-6 py-3.5 bg-slate-50 dark:bg-slate-800 dark:text-white border-none rounded-2xl focus:ring-2 focus:ring-primary-500/20" />
            </div>

            <div class="space-y-2">
              <label class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400">Role</label>
              <select v-model="editForm.role" class="w-full px-6 py-3.5 bg-slate-50 dark:bg-slate-800 dark:text-white border-none rounded-2xl focus:ring-2 focus:ring-primary-500/20 appearance-none">
                <option value="admin">ADMIN</option>
                <option value="hr">HR</option>
                <option value="mentor">MENTOR</option>
                <option value="user">USER (STUDENT)</option>
              </select>
            </div>
          </div>

          <div class="p-6 bg-slate-50 dark:bg-slate-800/50 rounded-3xl border border-dashed border-slate-200 dark:border-slate-700">
            <p class="text-xs font-bold text-slate-400 mb-4 flex items-center gap-2 uppercase tracking-widest">
              <Lock class="w-3 h-3" /> Ubah Password (Kosongkan jika tidak ingin diubah)
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <input v-model="editForm.password" type="password" class="w-full px-6 py-3.5 bg-white dark:bg-slate-900 dark:text-white border-none rounded-2xl focus:ring-2 focus:ring-primary-500/20" placeholder="Password Baru" />
              <input v-model="editForm.password_confirmation" type="password" class="w-full px-6 py-3.5 bg-white dark:bg-slate-900 dark:text-white border-none rounded-2xl focus:ring-2 focus:ring-primary-500/20" placeholder="Konfirmasi Password" />
            </div>
          </div>

          <div class="flex items-center justify-between p-4 bg-slate-50 dark:bg-slate-900 rounded-2xl">
             <span class="text-sm font-bold text-slate-700 dark:text-slate-300">Status Akun Aktif</span>
             <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="editForm.is_active" class="sr-only peer">
              <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary-600"></div>
            </label>
          </div>

          <div class="flex gap-4">
            <button type="button" @click="showEditModal = false" class="flex-1 px-6 py-4 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-2xl font-bold transition-all">
              Batal
            </button>
            <button 
              type="submit" 
              :disabled="processing"
              class="flex-1 px-6 py-4 bg-primary-600 hover:bg-primary-700 text-white rounded-2xl font-black shadow-lg shadow-primary-500/30 transition-all disabled:opacity-50 flex items-center justify-center gap-2"
            >
              <Loader2 v-if="processing" class="w-4 h-4 animate-spin" />
              {{ processing ? 'Menyimpan...' : 'Perbarui Akun' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>

    <ImportModal 
      :show="showImportModal" 
      title="Import User Accounts" 
      endpoint="/import/users" 
      template-url="/api/import/template/users"
      @close="showImportModal = false"
      @success="handleImportSuccess"
    />
  </DashboardLayout>
</template>

<style scoped>
/* Custom transitions and glassmorphism if needed */
</style>
