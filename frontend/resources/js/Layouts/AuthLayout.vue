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
    <div class="min-h-screen flex font-sans text-slate-900 dark:text-slate-100 bg-slate-50 dark:bg-neutral-950">
        <!-- Language Switcher (Fixed Top Right) -->
        <div class="fixed top-6 right-6 flex items-center bg-white/80 dark:bg-neutral-900/80 backdrop-blur-md rounded-xl p-1 border border-slate-200 dark:border-neutral-800 shadow-sm z-50">
            <button 
                class="px-3 py-2 rounded-xl text-xs font-bold transition-colors"
                :class="[langStore.locale === 'id' ? 'bg-slate-100 dark:bg-neutral-800 text-blue-600 shadow-sm' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300']"
                @click="langStore.setLocale('id')"
            >
                ID
            </button>
            <button 
                class="px-3 py-2 rounded-xl text-xs font-bold transition-colors"
                :class="[langStore.locale === 'en' ? 'bg-slate-100 dark:bg-neutral-800 text-blue-600 shadow-sm' : 'text-slate-400 hover:text-slate-600 dark:hover:text-slate-300']"
                @click="langStore.setLocale('en')"
            >
                EN
            </button>
        </div>

        <!-- Left Side: Branding/Visual (Hidden on mobile) -->
        <div class="hidden lg:flex lg:w-[45%] xl:w-1/2 relative bg-neutral-900 overflow-hidden">
            <!-- Background Image -->
            <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80" alt="InternHub Workspace" class="absolute inset-0 w-full h-full object-cover" />
            
            <!-- Dark Overlay for Text Readability -->
            <div class="absolute inset-0 bg-gradient-to-t from-neutral-950/90 via-neutral-900/60 to-neutral-900/30"></div>
            
            <!-- Content -->
            <div class="relative z-10 flex flex-col justify-between w-full h-full p-12 xl:p-16">
                <div>
                    <Link href="/" class="inline-block hover:opacity-80 transition-opacity">
                        <AppLogo variant="full" size="lg" :isDarkMode="true" />
                    </Link>
                </div>

                <div class="max-w-md mt-auto mb-16">
                    <h1 class="text-4xl xl:text-5xl font-extrabold text-white leading-[1.15] mb-6 tracking-tight">
                        Selangkah Lebih Dekat Dengan Suksesmu.
                    </h1>
                    <p class="text-neutral-300 text-lg font-medium leading-relaxed">
                        Akses ribuan peluang magang eksklusif dari perusahaan top, tingkatkan skill, dan mulai bangun portofolio karier Anda bersama InternHub.
                    </p>
                </div>
                
                <div class="flex items-center gap-4 text-sm font-medium text-neutral-400">
                    <span>&copy; 2026 InternHub</span>
                    <span>&bull;</span>
                    <Link href="/help" class="hover:text-white transition-colors">{{ t('nav.help') }}</Link>
                </div>
            </div>
        </div>

        <!-- Right Side: Form Area -->
        <div class="w-full lg:w-[55%] xl:w-1/2 flex flex-col items-center justify-center p-6 sm:p-12 relative bg-white dark:bg-neutral-950 lg:rounded-l-3xl shadow-[-10px_0_30px_rgba(0,0,0,0.05)] z-20">
            <!-- Mobile Logo (Visible only on small screens) -->
            <div class="lg:hidden flex justify-center mb-8">
                <Link href="/" class="hover:opacity-80 transition-opacity">
                    <AppLogo variant="full" size="lg" />
                </Link>
            </div>

            <!-- Form Container -->
            <div class="w-full max-w-[440px] relative z-10">
                <Transition name="page" mode="out-in" appear>
                    <div :key="$page.url" class="w-full">
                        <div class="mb-10 text-center lg:text-left">
                            <h2 class="text-3xl font-bold text-slate-900 dark:text-white mb-3 tracking-tight">
                                <slot name="title">{{ props.title }}</slot>
                            </h2>
                            <p class="text-slate-500 dark:text-neutral-400 text-base font-medium leading-relaxed">
                                <slot name="subtitle">{{ props.subtitle }}</slot>
                            </p>
                        </div>

                        <div class="space-y-6">
                            <slot />
                        </div>

                        <div class="mt-10 pt-8 flex items-center justify-center lg:justify-start gap-2 text-slate-400 dark:text-neutral-600 border-t border-slate-100 dark:border-neutral-800">
                            <ShieldCheck class="w-4 h-4" />
                            <span class="text-xs font-medium">{{ t('auth.secure_connection') }}</span>
                        </div>
                    </div>
                </Transition>

                <footer class="mt-8 flex flex-col items-center lg:items-start gap-4 lg:hidden">
                    <div class="flex items-center justify-center gap-6 text-sm font-medium text-slate-500 dark:text-neutral-500">
                        <Link href="/privacy" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ t('info.privacy') }}</Link>
                        <span class="w-1 h-1 bg-slate-200 dark:bg-neutral-800 rounded-full"></span>
                        <Link href="/terms" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">{{ t('info.terms') }}</Link>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</template>
