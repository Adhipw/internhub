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
        <!-- Minimal clean background, no blobs -->

        <div class="w-full max-w-[480px] relative z-10">
            <div class="flex justify-center mb-6">
                <Link href="/" class="hover:opacity-80 transition-opacity">
                    <AppLogo variant="full" size="lg" />
                </Link>
            </div>

            <Transition name="page" mode="out-in" appear>
                <div :key="$page.url" class="w-full">
                    <div class="px-4 py-8 md:px-0">
                        <div class="mb-10 text-center lg:text-left">
                            <h1 class="text-3xl font-bold text-slate-900 dark:text-white mb-3 tracking-tight">
                                <slot name="title">{{ props.title }}</slot>
                            </h1>
                            <p class="text-slate-500 dark:text-neutral-400 text-base font-medium leading-relaxed">
                                <slot name="subtitle">{{ props.subtitle }}</slot>
                            </p>
                        </div>

                        <div class="space-y-6">
                            <slot />
                        </div>

                        <div class="mt-10 pt-8 flex items-center justify-center gap-2 text-slate-400 dark:text-neutral-600">
                            <ShieldCheck class="w-4 h-4" />
                            <span class="text-xs font-medium">{{ t('auth.secure_connection') }}</span>
                        </div>
                    </div>
                </div>
            </Transition>

            <footer class="mt-8 flex flex-col items-center gap-4">
                <div class="flex items-center gap-6 text-sm font-medium text-slate-500 dark:text-neutral-500">
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
