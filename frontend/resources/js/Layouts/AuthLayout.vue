<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useLangStore } from '@/Stores/lang';
import { ShieldCheck } from 'lucide-vue-next';
import AppLogo from '@/Components/AppLogo.vue';

const props = defineProps<{
    title?: string;
    subtitle?: string;
}>();

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex flex-col items-center justify-center p-6 font-sans text-slate-900">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[5%] w-[40%] h-[40%] bg-blue-500/5 rounded-full blur-[100px]"></div>
            <div class="absolute -bottom-[10%] -right-[5%] w-[40%] h-[40%] bg-indigo-500/5 rounded-full blur-[100px]"></div>
        </div>

        <div class="w-full max-w-[480px] relative z-10">
            <div class="flex justify-center mb-8">
                <Link href="/" class="hover:opacity-80 transition-opacity">
                    <AppLogo variant="full" size="lg" :is-dark-mode="false" />
                </Link>
            </div>

            <div class="bg-white rounded-3xl p-8 md:p-12 shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100">
                <div class="mb-10 text-center lg:text-left">
                    <h1 class="text-2xl font-extrabold text-slate-900 mb-2 tracking-tight">
                        <slot name="title">{{ props.title }}</slot>
                    </h1>
                    <p class="text-slate-500 text-sm font-medium leading-relaxed">
                        <slot name="subtitle">{{ props.subtitle }}</slot>
                    </p>
                </div>

                <div class="space-y-6">
                    <slot />
                </div>

                <div class="mt-10 pt-8 border-t border-slate-50 flex items-center justify-center gap-2 text-slate-300">
                    <ShieldCheck class="w-4 h-4" />
                    <span class="text-[10px] font-bold uppercase tracking-[0.1em]">{{ t('auth.secure_connection') }}</span>
                </div>
            </div>

            <footer class="mt-10 flex flex-col items-center gap-4">
                <div class="flex items-center gap-6 text-xs font-bold text-slate-400 uppercase tracking-widest">
                    <Link href="/help" class="hover:text-blue-600 transition-colors">{{ t('nav.help') }}</Link>
                    <span class="w-1 h-1 bg-slate-200 rounded-full"></span>
                    <Link href="/privacy" class="hover:text-blue-600 transition-colors">{{ t('info.privacy') }}</Link>
                    <span class="w-1 h-1 bg-slate-200 rounded-full"></span>
                    <Link href="/terms" class="hover:text-blue-600 transition-colors">{{ t('info.terms') }}</Link>
                </div>
                <p class="text-slate-400 text-[10px] font-medium opacity-60">
                    &copy; 2026 InternHub Platform Ecosystem
                </p>
            </footer>
        </div>
    </div>
</template>
