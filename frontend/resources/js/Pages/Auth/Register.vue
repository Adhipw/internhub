<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useLangStore } from '@/Stores/lang';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import GoogleAuthButton from '@/Components/GoogleAuthButton.vue';
import AuthDivider from '@/Components/AuthDivider.vue';
import PasswordField from '@/Components/PasswordField.vue';
import PasswordStrengthMeter from '@/Components/PasswordStrengthMeter.vue';
import LoadingButton from '@/Components/LoadingButton.vue';
import AuthLink from '@/Components/AuthLink.vue';
import FormError from '@/Components/FormError.vue';
import Captcha from '@/Components/Captcha.vue';
import { GraduationCap, Building2 } from 'lucide-vue-next';

const langStore = useLangStore();

const generalError = ref('');
const captchaRef = ref<InstanceType<typeof Captcha> | null>(null);

const form = useForm({
    name: '',
    email: '',
    phone_number: '',
    password: '',
    password_confirmation: '',
    captcha: '',
    role: 'user', // Default role
});

const t = (key: string) => langStore.t(key);

const submit = async () => {
    const hostname = window.location.hostname;
    const isLocal =
        hostname === 'localhost' ||
        hostname === '127.0.0.1' ||
        hostname === '::1' ||
        hostname.endsWith('.local');

    if (!form.captcha && !isLocal) {
        form.setError('captcha', t('auth.captcha_required'));
        return;
    }

    form.clearErrors();
    generalError.value = '';

    form.post('/register', {
        preserveScroll: true,
        onError: (pageErrors) => {
            generalError.value = String(pageErrors.general || '');

            if (!generalError.value && Object.keys(pageErrors).length === 0) {
                generalError.value = t('auth.register_failed');
            }

            captchaRef.value?.reset();
        },
    });
};
</script>

<template>
    <AuthLayout>
        <template #title>{{ t('auth.create_account') }}</template>
        <template #subtitle>{{ t('auth.register_subtitle') }}</template>

        <template v-if="$page.props.feature_flags?.social_login !== false">
            <GoogleAuthButton :processing="form.processing" />
            <AuthDivider />
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div v-if="generalError" class="p-3 bg-red-50 text-red-600 text-sm rounded-lg border border-red-100">
                {{ generalError }}
            </div>
            <!-- Role Selector -->
            <div class="grid grid-cols-2 gap-4 mb-8">
                <button 
                    type="button"
                    :class="[
                        'flex flex-col items-center gap-3 p-4 rounded-2xl border-2 transition-all duration-300',
                        form.role === 'user' 
                            ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-500/10' 
                            : 'border-slate-100 dark:border-white/5 hover:border-indigo-200'
                    ]"
                    @click="form.role = 'user'"
                >
                    <GraduationCap :class="['w-8 h-8', form.role === 'user' ? 'text-indigo-600' : 'text-slate-400']" />
                    <span :class="['text-xs font-bold uppercase tracking-widest', form.role === 'user' ? 'text-indigo-600' : 'text-slate-500']">{{ t('auth.role_student_short') }}</span>
                </button>
                <button 
                    type="button"
                    :class="[
                        'flex flex-col items-center gap-3 p-4 rounded-2xl border-2 transition-all duration-300',
                        form.role === 'hr' 
                            ? 'border-indigo-600 bg-indigo-50 dark:bg-indigo-500/10' 
                            : 'border-slate-100 dark:border-white/5 hover:border-indigo-200'
                    ]"
                    @click="form.role = 'hr'"
                >
                    <Building2 :class="['w-8 h-8', form.role === 'hr' ? 'text-indigo-600' : 'text-slate-400']" />
                    <span :class="['text-xs font-bold uppercase tracking-widest', form.role === 'hr' ? 'text-indigo-600' : 'text-slate-500']">{{ t('auth.role_hr_short') }}</span>
                </button>
            </div>

            <div>
                <label for="name" class="block text-sm font-semibold text-slate-700 mb-2">{{ t('auth.full_name') }}</label>
                <input
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-50"
                    :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-50': form.errors.name }"
                    :placeholder="t('auth.full_name_placeholder')"
                    required
                />
                <FormError :message="form.errors.name" />
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">{{ t('auth.email') }}</label>
                <input
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-50"
                    :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-50': form.errors.email }"
                    :placeholder="t('auth.email_placeholder')"
                    required
                />
                <FormError :message="form.errors.email" />
            </div>

            <div>
                <label for="phone" class="block text-sm font-semibold text-slate-700 mb-2">{{ t('auth.whatsapp_number') }}</label>
                <input
                    id="phone"
                    v-model="form.phone_number"
                    type="tel"
                    class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm transition-all focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-50"
                    :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-50': form.errors.phone_number }"
                    placeholder="08xxxxxxxxxx"
                    required
                />
                <FormError :message="form.errors.phone_number" />
            </div>

            <div class="space-y-1">
                <PasswordField
                    id="password"
                    v-model="form.password"
                    :label="t('auth.password')"
                    :error="form.errors.password"
                    :placeholder="t('auth.password_placeholder_register')"
                    required
                />
                <PasswordStrengthMeter :password="form.password" />
            </div>

            <PasswordField
                id="password_confirmation"
                v-model="form.password_confirmation"
                :label="t('auth.confirm_password')"
                :placeholder="t('auth.confirm_password_placeholder')"
                required
            />

            <Captcha ref="captchaRef" v-model="form.captcha" :error="form.errors.captcha" />

            <div class="pt-2">
                <LoadingButton :processing="form.processing">
                    {{ t('auth.register_button') }}
                </LoadingButton>
            </div>
        </form>

        <AuthLink 
            :label="t('auth.already_have_account')" 
            :link-text="t('auth.login_here')" 
            href="/login" 
        />
    </AuthLayout>
</template>
