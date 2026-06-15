<script setup lang="ts">
import logger from '@/Lib/logger';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
    modelValue: string;
}>();

const emit = defineEmits(['update:modelValue']);

const container = ref<HTMLElement | null>(null);
const widgetId = ref<number | null>(null);

const siteKey = import.meta.env.VITE_RECAPTCHA_SITE_KEY || (window as any).__APP_CONFIG__?.recaptchaSiteKey;

const renderCaptcha = () => {
    try {
        const grecaptcha = (window as any).grecaptcha;
        if (grecaptcha && grecaptcha.render && container.value) {
            if (!siteKey) {
                logger.error('reCAPTCHA site key is missing! Check your .env (VITE_RECAPTCHA_SITE_KEY)');
                return;
            }
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
        logger.error('Failed to render reCAPTCHA:', e);
    }
};

onMounted(() => {
    if ((window as any).grecaptcha) {
        renderCaptcha();
    } else {
        const interval = setInterval(() => {
            if ((window as any).grecaptcha) {
                renderCaptcha();
                clearInterval(interval);
            }
        }, 500);
        setTimeout(() => clearInterval(interval), 10000);
    }
});

onUnmounted(() => {
    const grecaptcha = (window as any).grecaptcha;
    if (widgetId.value !== null && grecaptcha) {
        // grecaptcha.reset(widgetId.value);
    }
});
</script>

<template>
    <div class="flex justify-center my-4 min-h-[78px]">
        <div ref="container"></div>
    </div>
</template>
