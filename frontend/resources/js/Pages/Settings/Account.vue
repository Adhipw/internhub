<script setup lang="ts">
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { Head } from '@/Components';

import { ref } from 'vue';
import { route } from 'ziggy-js';
import { useLangStore } from '@/Stores/lang';
import {
    User, Lock, ShieldCheck, Eye, EyeOff,
    Save, AlertCircle, CheckCircle2
} from 'lucide-vue-next';
import LoadingButton from '@/Components/LoadingButton.vue';

const props = defineProps<{
    user: any;
    status?: string | null;
}>();

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const nameForm = useForm({
    name: props.user.name,
});

const updateName = () => {
    nameForm.patch(route('settings.name.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Success handling is managed by the flash message/status
        },
    });
};

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const updatePassword = () => {
    passwordForm.put(route('settings.password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
            }
        },
    });
};
</script>

<template>
    <Head :title="t('settings.account.page_title')" />

    <DashboardLayout>
        <div class="max-w-4xl mx-auto space-y-10">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 mb-2">{{ t('settings.account.title') }}</h1>
                <p class="text-slate-500">{{ t('settings.account.subtitle') }}</p>
            </div>

            <div class="grid grid-cols-1 gap-10">
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="p-8 md:p-10">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-12 h-12 rounded-2xl bg-primary-50 flex items-center justify-center text-primary-600">
                                <User class="w-6 h-6" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900">{{ t('settings.account.basic_info_title') }}</h2>
                                <p class="text-sm text-slate-500">{{ t('settings.account.basic_info_desc') }}</p>
                            </div>
                        </div>

                        <form class="space-y-6 max-w-md" @submit.prevent="updateName">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">{{ t('settings.account.full_name_label') }}</label>
                                <input
                                    v-model="nameForm.name"
                                    type="text"
                                    class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary-600 transition-all text-slate-900 font-medium"
                                    :placeholder="t('settings.account.full_name_placeholder')"
                                />
                                <p v-if="nameForm.errors.name" class="mt-2 text-xs text-red-500 font-medium flex items-center gap-1">
                                    <AlertCircle class="w-3 h-3" />
                                    {{ nameForm.errors.name }}
                                </p>
                            </div>

                            <div class="flex items-center gap-4">
                                <LoadingButton
                                    type="submit"
                                    :loading="nameForm.processing"
                                    class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-4 rounded-2xl font-bold text-sm transition-all shadow-lg shadow-primary-600/20"
                                >
                                    <div class="flex items-center justify-center gap-2">
                                        <Save class="w-4 h-4" />
                                        <span>{{ t('settings.account.save_changes') }}</span>
                                    </div>
                                </LoadingButton>

                                <Transition
                                    enter-active-class="transition ease-out duration-300"
                                    enter-from-class="opacity-0 translate-x-4"
                                    enter-to-class="opacity-100 translate-x-0"
                                    leave-active-class="transition ease-in duration-300"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-if="status === 'name-updated'" class="text-sm font-bold text-green-600 flex items-center gap-2">
                                        <CheckCircle2 class="w-4 h-4" />
                                        {{ t('settings.account.name_updated') }}
                                    </p>
                                </Transition>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                    <div class="p-8 md:p-10">
                        <div class="flex items-center gap-4 mb-8">
                            <div class="w-12 h-12 rounded-2xl bg-amber-50 flex items-center justify-center text-amber-600">
                                <ShieldCheck class="w-6 h-6" />
                            </div>
                            <div>
                                <h2 class="text-xl font-bold text-slate-900">{{ t('settings.account.security_title') }}</h2>
                                <p class="text-sm text-slate-500">{{ t('settings.account.security_desc') }}</p>
                            </div>
                        </div>

                        <form class="space-y-6 max-w-md" @submit.prevent="updatePassword">
                            <div class="relative">
                                <label class="block text-sm font-bold text-slate-700 mb-2">{{ t('settings.account.current_password_label') }}</label>
                                <div class="relative group">
                                    <input
                                        v-model="passwordForm.current_password"
                                        :type="showCurrentPassword ? 'text' : 'password'"
                                        class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-amber-600 transition-all text-slate-900 font-medium"
                                        :placeholder="t('settings.account.password_placeholder_mask')"
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                                        @click="showCurrentPassword = !showCurrentPassword"
                                    >
                                        <Eye v-if="!showCurrentPassword" class="w-5 h-5" />
                                        <EyeOff v-else class="w-5 h-5" />
                                    </button>
                                </div>
                                <p v-if="passwordForm.errors.current_password" class="mt-2 text-xs text-red-500 font-medium flex items-center gap-1">
                                    <AlertCircle class="w-3 h-3" />
                                    {{ passwordForm.errors.current_password }}
                                </p>
                            </div>

                            <hr class="border-slate-50" />

                            <div class="relative">
                                <label class="block text-sm font-bold text-slate-700 mb-2">{{ t('settings.account.new_password_label') }}</label>
                                <div class="relative group">
                                    <input
                                        v-model="passwordForm.password"
                                        :type="showNewPassword ? 'text' : 'password'"
                                        class="w-full px-5 py-4 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary-600 transition-all text-slate-900 font-medium"
                                        :placeholder="t('settings.account.new_password_placeholder')"
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                                        @click="showNewPassword = !showNewPassword"
                                    >
                                        <Eye v-if="!showNewPassword" class="w-5 h-5" />
                                        <EyeOff v-else class="w-5 h-5" />
                                    </button>
                                </div>
                                <p v-if="passwordForm.errors.password" class="mt-2 text-xs text-red-500 font-medium flex items-center gap-1">
                                    <AlertCircle class="w-3 h-3" />
                                    {{ passwordForm.errors.password }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">{{ t('settings.account.confirm_password_label') }}</label>
                                <div class="relative group">
                                    <input
                                        v-model="passwordForm.password_confirmation"
                                        :type="showConfirmPassword ? 'text' : 'password'"
                                        class="w-full px-5 py-4 pr-14 bg-slate-50 border-none rounded-2xl focus:ring-2 focus:ring-primary-600 transition-all text-slate-900 font-medium"
                                        :placeholder="t('settings.account.confirm_password_placeholder')"
                                    />
                                    <button
                                        type="button"
                                        class="absolute right-5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition-colors"
                                        :aria-label="showConfirmPassword ? t('settings.account.hide_confirm_password') : t('settings.account.show_confirm_password')"
                                        @click="showConfirmPassword = !showConfirmPassword"
                                    >
                                        <Eye v-if="!showConfirmPassword" class="w-5 h-5" />
                                        <EyeOff v-else class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>

                            <div class="flex items-center gap-4">
                                <LoadingButton
                                    type="submit"
                                    :loading="passwordForm.processing"
                                    class="bg-slate-900 hover:bg-black text-white px-8 py-4 rounded-2xl font-bold text-sm transition-all shadow-lg"
                                >
                                    <div class="flex items-center justify-center gap-2">
                                        <Lock class="w-4 h-4" />
                                        <span>{{ t('settings.account.update_password') }}</span>
                                    </div>
                                </LoadingButton>

                                <Transition
                                    enter-active-class="transition ease-out duration-300"
                                    enter-from-class="opacity-0 translate-x-4"
                                    enter-to-class="opacity-100 translate-x-0"
                                    leave-active-class="transition ease-in duration-300"
                                    leave-from-class="opacity-100"
                                    leave-to-class="opacity-0"
                                >
                                    <p v-if="status === 'password-updated'" class="text-sm font-bold text-green-600 flex items-center gap-2">
                                        <CheckCircle2 class="w-4 h-4" />
                                        {{ t('settings.account.password_updated') }}
                                    </p>
                                </Transition>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
