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
    <div class="min-h-screen bg-slate-50 dark:bg-neutral-950 flex flex-col items-center justify-center p-6 font-sans text-slate-900 dark:text-slate-100">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-[10%] -left-[5%] w-[40%] h-[40%] bg-blue-500/5 rounded-full blur-2xl opacity-30"></div>
            <div class="absolute -bottom-[10%] -right-[5%] w-[40%] h-[40%] bg-indigo-500/5 rounded-full blur-2xl opacity-30"></div>
        </div>

        <div class="w-full max-w-[480px] relative z-10">
            <div class="flex justify-center mb-6">
                <Link href="/" class="hover:opacity-80 transition-opacity">
                    <AppLogo variant="full" size="lg" />
                </Link>
            </div>

            <div class="bg-white dark:bg-neutral-900 rounded-2xl p-6 md:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.04)] dark:shadow-none border border-slate-100 dark:border-neutral-800">
                <div class="mb-8 text-center lg:text-left">
                    <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white mb-2 tracking-tight">
                        <slot name="title">{{ props.title }}</slot>
                    </h1>
                    <p class="text-slate-500 dark:text-neutral-400 text-sm font-medium leading-relaxed">
                        <slot name="subtitle">{{ props.subtitle }}</slot>
                    </p>
                </div>

                <div class="space-y-5">
                    <slot />
                </div>

                <div class="mt-8 pt-6 border-t border-slate-50 dark:border-neutral-800 flex items-center justify-center gap-2 text-slate-300 dark:text-neutral-600">
                    <ShieldCheck class="w-4 h-4" />
                    <span class="text-[10px] font-bold uppercase tracking-[0.1em]">{{ t('auth.secure_connection') }}</span>
                </div>
            </div>

            <footer class="mt-8 flex flex-col items-center gap-4">
                <div class="flex items-center gap-6 text-xs font-bold text-slate-400 dark:text-neutral-500 uppercase tracking-widest">
                    <Link href="/help" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ t('nav.help') }}</Link>
                    <span class="w-1 h-1 bg-slate-200 dark:bg-neutral-800 rounded-full"></span>
                    <Link href="/privacy" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ t('info.privacy') }}</Link>
                    <span class="w-1 h-1 bg-slate-200 dark:bg-neutral-800 rounded-full"></span>
                    <Link href="/terms" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ t('info.terms') }}</Link>
                </div>
                <p class="text-slate-400 dark:text-neutral-500 text-[10px] font-medium opacity-60">
                    &copy; 2026 InternHub Platform Ecosystem
                </p>
            </footer>
        </div>
    </div>
</template>
