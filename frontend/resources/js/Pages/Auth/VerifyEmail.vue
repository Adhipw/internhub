<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed } from 'vue';
import { useLangStore } from '@/Stores/lang';
import AuthLayout from '@/Layouts/AuthLayout.vue';
import LoadingButton from '@/Components/LoadingButton.vue';
import FormError from '@/Components/FormError.vue';

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

defineProps<{
    devOtp?: string | null;
    deliveryError?: string | null;
}>();

const form = useForm({
    otp: '',
});

const submit = () => {
    form.post(route('verification.verify'), {
        onFinish: () => form.reset('otp'),
    });
};

const resendForm = useForm({});
const resendError = computed(() => (resendForm.errors as Record<string, string>).resend || '');

const resendOtp = () => {
    resendForm.post(route('verification.send'));
};
</script>

<template>
    <AuthLayout>
        <template #title>{{ t('auth.verify_email_title') }}</template>
        <template #subtitle>
            {{ t('auth.verify_email_subtitle') }}
        </template>

        <div v-if="devOtp" class="mb-6 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-center">
            <p class="text-[11px] font-semibold text-xs tracking-wide text-blue-500">Local OTP</p>
            <p class="mt-1 text-2xl font-bold tracking-[0.4em] text-blue-700">{{ devOtp }}</p>
        </div>

        <div v-if="deliveryError" class="mb-6 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-center text-sm font-semibold text-red-600">
            {{ deliveryError }}
        </div>

        <form class="space-y-6" @submit.prevent="submit">
            <div>
                <input
                    v-model="form.otp"
                    type="text"
                    class="w-full text-center tracking-[1em] text-2xl font-bold rounded-xl border border-slate-200 bg-white px-4 py-4 transition-all focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-50"
                    :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-50': form.errors.otp }"
                    placeholder="000000"
                    maxlength="6"
                    required
                    autofocus
                />
                <FormError :message="form.errors.otp" class="text-center mt-3" />
            </div>

            <LoadingButton :processing="form.processing">
                {{ t('auth.verify_account_button') }}
            </LoadingButton>
        </form>

        <div class="mt-8 text-center border-t border-slate-100 pt-6">
            <p class="text-xs text-slate-400 mb-2">{{ t('auth.did_not_receive_code') }}</p>
            <button 
                :disabled="resendForm.processing"
                class="text-sm font-bold text-blue-600 hover:text-blue-700 disabled:opacity-50"
                @click="resendOtp"
            >
                {{ t('auth.resend_otp') }}
            </button>
            <p v-if="resendForm.wasSuccessful" class="mt-2 text-xs text-green-600 font-medium">
                {{ t('auth.new_otp_sent') }}
            </p>
            <FormError :message="resendError" class="mt-2 text-center" />

            <div class="mt-6">
                <Link :href="route('logout')" method="post" as="button" class="text-xs font-medium text-slate-400 hover:text-red-500 transition-colors">
                    {{ t('auth.logout_and_exit') }}
                </Link>
            </div>
        </div>
    </AuthLayout>
</template>
