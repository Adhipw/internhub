<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, reactive, onMounted } from 'vue';
import { useLangStore } from '@/Stores/lang';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import GoogleAuthButton from '@/Components/GoogleAuthButton.vue';
import AuthDivider from '@/Components/AuthDivider.vue';
import LoadingButton from '@/Components/LoadingButton.vue';
import AuthLink from '@/Components/AuthLink.vue';
import Input from '@/Components/Input.vue';
import PasswordField from '@/Components/PasswordField.vue';
import Captcha from '@/Components/Captcha.vue';

const langStore = useLangStore();

const captchaToken = ref('');
const captchaRef = ref<InstanceType<typeof Captcha> | null>(null);
const requestedRole = ref(new window.URLSearchParams(window.location.search).get('role') || '');

const errors = reactive({
    captcha: '',
    general: '',
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

onMounted(() => {
    const rememberedEmail = localStorage.getItem('remembered_email');
    if (rememberedEmail) {
        form.email = rememberedEmail;
        form.remember = true;
    }
});

const t = (key: string) => langStore.t(key);
const roleLabel = computed(() => {
    const role = requestedRole.value;
    if (role === 'hr') return t('auth.role_hr_short');
    if (role === 'mentor') return t('auth.role_mentor_short');
    if (role === 'admin') return t('auth.role_admin_short');
    if (role === 'super_admin') return t('auth.role_super_admin_short');
    return t('auth.role_student_short');
});

const submit = async () => {
    const hostname = window.location.hostname;
    const isLocal =
        hostname === 'localhost' ||
        hostname === '127.0.0.1' ||
        hostname === '::1' ||
        hostname.endsWith('.local');

    if (!captchaToken.value && !isLocal) {
        errors.captcha = t('auth.captcha_required');
        return;
    }

    errors.captcha = '';
    errors.general = '';

    form.transform((data) => ({
        ...data,
        captcha: captchaToken.value
    })).post('/login', {
        preserveScroll: true,
        onStart: () => {
            if (form.remember) {
                localStorage.setItem('remembered_email', form.email);
            } else {
                localStorage.removeItem('remembered_email');
            }
        },
        onError: (pageErrors) => {
            errors.captcha = String(pageErrors.captcha || '');
            const hasFieldErrors = pageErrors.email || pageErrors.password || pageErrors.captcha;
            errors.general = String(pageErrors.general || (!hasFieldErrors ? t('auth.login_failed') : ''));
            captchaRef.value?.reset();
        },
    });
};
</script>

<template>
    <AuthLayout>
        <template #title>{{ t('auth.login_title') }}</template>
        <template #subtitle>
            <div v-if="requestedRole" class="flex items-center gap-2 mb-2">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 "></span>
                <span class="text-[10px] font-bold tracking-widest text-blue-600 uppercase">
                    {{ t('auth.portal_access') }} {{ roleLabel }}
                </span>
            </div>
            {{ t('auth.login_subtitle') }}
        </template>

        <template v-if="$page.props.feature_flags?.social_login !== false">
            <GoogleAuthButton :processing="form.processing" />
            <AuthDivider />
        </template>

        <form class="space-y-4" @submit.prevent="submit">
            <div v-if="errors.general" class="p-4 bg-red-50 text-red-600 text-xs font-bold rounded-2xl border border-red-100 text-center">
                {{ errors.general }}
            </div>

            <Input
                id="email"
                v-model="form.email"
                type="email"
                :label="t('auth.email_label')"
                :error="form.errors.email"
                :placeholder="t('auth.email_placeholder')"
                required
                autofocus
                autocomplete="off"
            />

            <PasswordField
                v-model="form.password"
                :label="t('auth.password_label')"
                :error="form.errors.password"
                :placeholder="t('auth.password_placeholder')"
                required
                autocomplete="new-password"
            />

            <div class="flex items-center justify-between pb-2">
                <label class="flex items-center gap-2 cursor-pointer group">
                    <input 
                        v-model="form.remember" 
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500/20"
                    />
                    <span class="text-sm font-semibold text-slate-600 group-hover:text-slate-900 transition-colors uppercase tracking-wider">{{ t('auth.remember_me') }}</span>
                </label>
                <Link href="/forgot-password" class="text-sm font-bold text-blue-600 hover:text-blue-700 p-2 -mr-2 transition-all">
                    {{ t('auth.forgot_password') }}
                </Link>
            </div>

            <div class="pt-1">
                <Captcha ref="captchaRef" v-model="captchaToken" />
            </div>
            <div v-if="errors.captcha" class="text-center text-xs font-bold text-red-500 mb-2 uppercase tracking-widest">
                {{ errors.captcha }}
            </div>

            <LoadingButton :processing="form.processing">
                {{ t('auth.login_btn') }}
            </LoadingButton>
        </form>

        <div v-if="$page.props.feature_flags?.public_registration !== false" class="mt-6">
            <AuthLink 
                :label="t('auth.no_account')" 
                :link-text="t('auth.register_link')" 
                href="/register" 
            />
        </div>
    </AuthLayout>
</template>
