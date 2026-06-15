<script setup lang="ts">
import logger from '@/Lib/logger';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
    modelValue: string;
}>();

const emit = defineEmits(['update:modelValue']);

const container = ref<HTMLElement | null>(null);
const widgetId = ref<number | null>(null);
const errorMessage = ref('');

const siteKey = import.meta.env.VITE_RECAPTCHA_SITE_KEY || (window as any).__APP_CONFIG__?.recaptchaSiteKey;

const renderCaptcha = () => {
    try {
        const grecaptcha = (window as any).grecaptcha;
        if (grecaptcha && grecaptcha.render && container.value) {
            if (container.value.dataset.rendered === 'true') {
                return;
            }
            if (!siteKey) {
                errorMessage.value = 'reCAPTCHA site key is missing.';
                logger.error('reCAPTCHA site key is missing! Check your .env (VITE_RECAPTCHA_SITE_KEY)');
                return;
            }
            if (widgetId.value !== null) {
                return;
            }
            errorMessage.value = '';
            widgetId.value = grecaptcha.render(container.value, {
                sitekey: siteKey,
                callback: (response: string) => {
                    emit('update:modelValue', response);
                },
                'expired-callback': () => {
                    emit('update:modelValue', '');
                }
            });
        }
    } catch (e) {
        errorMessage.value = 'reCAPTCHA failed to load. Check the site key domain settings.';
        logger.error('Failed to render reCAPTCHA:', e);
    }
};

const loadRecaptcha = () => {
    if ((window as any).grecaptcha?.render) {
        renderCaptcha();
        return;
    }

    (window as any).__internhubRenderRecaptcha = renderCaptcha;

    const existingScript = document.querySelector<HTMLScriptElement>('script[src*="google.com/recaptcha/api.js"]');
    if (existingScript) {
        existingScript.addEventListener('load', renderCaptcha, { once: true });
    } else {
        const script = document.createElement('script');
        script.src = 'https://www.google.com/recaptcha/api.js?onload=__internhubRenderRecaptcha&render=explicit';
        script.async = true;
        script.defer = true;
        script.onerror = () => {
            errorMessage.value = 'reCAPTCHA script was blocked or failed to load.';
        };
        document.head.appendChild(script);
    }

    setTimeout(() => {
        if (widgetId.value === null && !errorMessage.value) {
            errorMessage.value = 'reCAPTCHA is still loading. Refresh the page if it does not appear.';
        }
    }, 10000);
};

onMounted(() => {
    container.value?.addEventListener('recaptcha-token', ((event: CustomEvent<string>) => {
        emit('update:modelValue', event.detail || '');
    }) as EventListener);
    loadRecaptcha();
});

onUnmounted(() => {
    const grecaptcha = (window as any).grecaptcha;
    if (widgetId.value !== null && grecaptcha) {
        // grecaptcha.reset(widgetId.value);
    }
});
</script>

<template>
    <div class="flex flex-col items-center justify-center my-4 min-h-[78px]">
        <div ref="container" class="internhub-recaptcha" :data-sitekey="siteKey"></div>
        <p v-if="errorMessage" class="mt-2 text-center text-xs font-bold text-red-500">
            {{ errorMessage }}
        </p>
    </div>
</template>
