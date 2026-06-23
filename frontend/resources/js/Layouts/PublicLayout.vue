<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useAuthStore } from '@/Stores/auth';
import { useLangStore } from '@/Stores/lang';
import { useTheme } from '@/Composables/useTheme';
import Icon from '@/Components/Icon.vue';
import AppLogo from '@/Components/AppLogo.vue';

const authStore = useAuthStore();
const langStore = useLangStore();
const { isDarkMode, toggleDarkMode, initTheme } = useTheme();

const user = computed(() => authStore.user);
const locale = computed(() => langStore.locale);

const dashboardRoute = computed(() => {
    if (!user.value) return '/login';
    const role = user.value.role;
    if (role === 'super_admin') return '/super-admin/dashboard';
    if (role === 'admin') return '/admin/dashboard';
    if (role === 'hr') return '/hr/dashboard';
    if (role === 'mentor') return '/mentor/dashboard';
    return '/dashboard';
});

const isScrolled = ref(false);
const isMobileMenuOpen = ref(false);
const isLangMenuOpen = ref(false);

const handleScroll = () => {
    isScrolled.value = window.scrollY > 20;
};

const logout = async () => {
    await authStore.logout();
};

const setLanguage = async (newLocale: string) => {
    await langStore.setLocale(newLocale as 'id' | 'en');
    isLangMenuOpen.value = false;
};

const t = (key: string) => langStore.t(key);

onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    initTheme();
    // Ensure translations are loaded
    if (Object.keys(langStore.translations).length === 0) {
        langStore.fetchTranslations();
    }
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <div class="min-h-screen transition-colors duration-300" :class="isDarkMode ? 'bg-slate-950 text-slate-50' : 'bg-slate-50 text-slate-950'">
        <!-- Navigation (72px, Sticky, Professional) -->
        <nav 
            class="fixed top-0 z-50 w-full transition-all duration-300 border-b"
            :class="[
                isScrolled 
                    ? (isDarkMode ? 'bg-slate-900/90 backdrop-blur-md border-slate-800 shadow-lg' : 'bg-white/90 backdrop-blur-md border-slate-200 shadow-sm') 
                    : (isDarkMode ? 'bg-slate-950 border-transparent' : 'bg-white border-transparent'),
                'py-3 lg:py-4'
            ]"
            style="height: 72px;"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 h-full flex items-center justify-between">
                <!-- Logo -->
                <Link href="/" class="group shrink-0" aria-label="InternHub Home">
                    <AppLogo 
                        variant="full" 
                        size="md" 
                        :is-dark-mode="isDarkMode" 
                    />
                </Link>

                <!-- Desktop Menu -->
                <div class="hidden lg:flex items-center gap-8">
                    <div class="flex items-center gap-6">
                        <Link href="/" class="text-sm font-bold hover:text-blue-600 transition-colors" :class="isDarkMode ? 'text-slate-400' : 'text-slate-600'">{{ t('nav.home') }}</Link>
                        <Link href="/internships" class="text-sm font-bold hover:text-blue-600 transition-colors" :class="isDarkMode ? 'text-slate-400' : 'text-slate-600'">{{ t('nav.internships') }}</Link>
                        <Link href="/companies" class="text-sm font-bold hover:text-blue-600 transition-colors" :class="isDarkMode ? 'text-slate-400' : 'text-slate-600'">{{ t('nav.companies') }}</Link>
                        <Link href="/career-tips" class="text-sm font-bold hover:text-blue-600 transition-colors" :class="isDarkMode ? 'text-slate-400' : 'text-slate-600'">{{ t('nav.career_tips') }}</Link>
                        <Link href="/help" class="text-sm font-bold hover:text-blue-600 transition-colors" :class="isDarkMode ? 'text-slate-400' : 'text-slate-600'">{{ t('nav.help') }}</Link>
                    </div>
                    
                    <div class="h-6 w-[1px]" :class="isDarkMode ? 'bg-slate-800' : 'bg-slate-200'"></div>

                    <div class="flex items-center gap-3">
                        <!-- Dark Mode Toggle -->
                        <button class="w-10 h-10 rounded-xl flex items-center justify-center transition-all" :class="isDarkMode ? 'bg-slate-800 text-amber-400 hover:bg-slate-700' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" @click="toggleDarkMode">
                            <Icon :name="isDarkMode ? 'sun' : 'moon'" class-name="w-5 h-5" />
                        </button>

                        <!-- Lang Switcher -->
                        <div class="relative">
                            <button class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-bold uppercase tracking-widest transition-all" :class="isDarkMode ? 'bg-slate-800 text-slate-300' : 'bg-slate-100 text-slate-600'" @click="isLangMenuOpen = !isLangMenuOpen">
                                {{ locale }}
                                <Icon name="chevron" class-name="w-3 h-3 transition-transform" :class="isLangMenuOpen ? 'rotate-180' : ''" />
                            </button>
                            <div v-if="isLangMenuOpen" class="absolute top-full right-0 mt-2 w-32 rounded-xl shadow-xl border overflow-hidden animate-in fade-in slide-in-from-top-2" :class="isDarkMode ? 'bg-slate-800 border-slate-700' : 'bg-white border-slate-100'">
                                <button class="w-full px-4 py-3 text-left text-xs font-bold hover:bg-blue-600 hover:text-white transition-colors" :class="locale === 'id' ? 'text-blue-600' : ''" @click="setLanguage('id')">BHS INDONESIA</button>
                                <button class="w-full px-4 py-3 text-left text-xs font-bold hover:bg-blue-600 hover:text-white transition-colors" :class="locale === 'en' ? 'text-blue-600' : ''" @click="setLanguage('en')">ENGLISH</button>
                            </div>
                        </div>

                        <template v-if="user">
                            <Link :href="dashboardRoute" class="flex items-center gap-2 text-sm font-bold px-5 py-2.5 rounded-xl border shadow-sm transition-all" :class="isDarkMode ? 'bg-slate-800 border-slate-700 text-white hover:bg-slate-700' : 'bg-white border-slate-200 text-slate-950 hover:bg-slate-50'">
                                <Icon name="chart" class-name="w-4 h-4" />
                                {{ t('nav.dashboard') }}
                            </Link>
                        </template>
                        <template v-else>
                            <Link href="/login" class="text-sm font-bold hover:text-blue-600 transition-colors px-2" :class="isDarkMode ? 'text-slate-300' : 'text-slate-600'">{{ t('nav.login') }}</Link>
                            <Link v-if="$page.props.feature_flags?.public_registration !== false" href="/register" class="bg-blue-600 text-white px-6 py-2.5 rounded-full text-sm font-bold hover:bg-blue-700 shadow-lg shadow-blue-600/10 transition-all">
                                {{ t('nav.register') }}
                            </Link>
                            <Link href="/login?role=hr" class="text-sm font-bold text-blue-600 border border-blue-600 px-6 py-2.5 rounded-xl hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all">
                                {{ t('nav.post_job') }}
                            </Link>
                        </template>
                    </div>
                </div>

                <!-- Mobile Toggle -->
                <div class="flex items-center gap-2 lg:hidden">
                    <button class="w-10 h-10 rounded-xl flex items-center justify-center" :class="isDarkMode ? 'bg-slate-800 text-amber-400' : 'bg-slate-100 text-slate-600'" @click="toggleDarkMode">
                        <Icon :name="isDarkMode ? 'sun' : 'moon'" class-name="w-5 h-5" />
                    </button>
                    <button class="w-10 h-10 rounded-xl flex items-center justify-center" :class="isDarkMode ? 'bg-slate-800 text-slate-300' : 'bg-slate-100 text-slate-600'" @click="isMobileMenuOpen = !isMobileMenuOpen">
                        <Icon :name="isMobileMenuOpen ? 'close' : 'menu'" class-name="w-6 h-6" />
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-10"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-10"
            >
                <div v-if="isMobileMenuOpen" class="absolute top-full left-0 w-full border-b p-8 flex flex-col gap-6 shadow-2xl lg:hidden" :class="isDarkMode ? 'bg-slate-900 border-slate-800' : 'bg-white border-slate-200'">
                    <Link href="/" class="text-lg font-bold" @click="isMobileMenuOpen = false">{{ t('nav.home') }}</Link>
                    <Link href="/internships" class="text-lg font-bold" @click="isMobileMenuOpen = false">{{ t('nav.internships') }}</Link>
                    <Link href="/companies" class="text-lg font-bold" @click="isMobileMenuOpen = false">{{ t('nav.companies') }}</Link>
                    <Link href="/career-tips" class="text-lg font-bold" @click="isMobileMenuOpen = false">{{ t('nav.career_tips') }}</Link>
                    
                    <div class="flex items-center gap-4 py-2">
                        <button class="px-4 py-2 rounded-lg text-xs font-bold" :class="locale === 'id' ? 'bg-blue-600 text-white' : (isDarkMode ? 'bg-slate-800 text-slate-400' : 'bg-slate-100 text-slate-500')" @click="setLanguage('id')">ID</button>
                        <button class="px-4 py-2 rounded-lg text-xs font-bold" :class="locale === 'en' ? 'bg-blue-600 text-white' : (isDarkMode ? 'bg-slate-800 text-slate-400' : 'bg-slate-100 text-slate-500')" @click="setLanguage('en')">EN</button>
                    </div>

                    <hr :class="isDarkMode ? 'border-slate-800' : 'border-slate-100'" />
                    
                    <template v-if="user">
                        <Link :href="dashboardRoute" class="text-lg font-bold text-blue-600" @click="isMobileMenuOpen = false">{{ t('nav.dashboard') }}</Link>
                        <button class="text-lg font-bold text-red-600 text-left" @click="logout">{{ t('nav.logout') }}</button>
                    </template>
                    <template v-else>
                        <Link href="/login" class="text-center font-bold bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white py-4 rounded-2xl" @click="isMobileMenuOpen = false">{{ t('nav.login') }}</Link>
                        <Link v-if="$page.props.feature_flags?.public_registration !== false" href="/register" class="bg-blue-600 text-white px-6 py-4 rounded-2xl text-center font-bold" @click="isMobileMenuOpen = false">{{ t('nav.register') }}</Link>
                        <Link href="/login?role=hr" class="text-center font-bold text-blue-600 border border-blue-600 py-4 rounded-2xl" @click="isMobileMenuOpen = false">{{ t('nav.post_job') }}</Link>
                    </template>
                </div>
            </Transition>
        </nav>

        <!-- Main Content -->
        <main class="pt-[72px]">
            <slot />
        </main>

        <!-- Footer (Job Portal Style) -->
        <footer class="border-t pt-24 pb-12 transition-colors duration-300" :class="isDarkMode ? 'bg-slate-950 border-slate-800' : 'bg-white border-slate-200'">
            <div class="max-w-7xl mx-auto px-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-16 mb-20">
                    <div class="lg:col-span-1">
                        <Link href="/" class="inline-block mb-6 group" aria-label="InternHub Home">
                            <AppLogo 
                                variant="full" 
                                size="md" 
                                :is-dark-mode="isDarkMode" 
                            />
                        </Link>
                        <p class="text-sm leading-relaxed mb-8" :class="isDarkMode ? 'text-slate-400' : 'text-slate-500'">
                            {{ t('footer.description') }}
                        </p>
                    </div>

                    <div>
                        <h4 class="font-bold mb-6 text-sm uppercase tracking-widest" :class="isDarkMode ? 'text-white' : 'text-slate-900'">{{ t('footer.students') }}</h4>
                        <ul class="space-y-4 text-sm font-semibold" :class="isDarkMode ? 'text-slate-400' : 'text-slate-500'">
                            <li><Link href="/internships" class="hover:text-blue-600 transition-colors">{{ t('footer.find_internships') }}</Link></li>
                            <li><Link href="/career-tips" class="hover:text-blue-600 transition-colors">{{ t('footer.career_tips') }}</Link></li>
                            <li><Link href="/cv-guide" class="hover:text-blue-600 transition-colors">{{ t('footer.cv_guide') }}</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-bold mb-6 text-sm uppercase tracking-widest" :class="isDarkMode ? 'text-white' : 'text-slate-900'">{{ t('footer.companies') }}</h4>
                        <ul class="space-y-4 text-sm font-semibold" :class="isDarkMode ? 'text-slate-400' : 'text-slate-500'">
                            <li><Link href="/login?role=hr" class="hover:text-blue-600 transition-colors">{{ t('footer.post_internship') }}</Link></li>
                            <li><Link href="/selection-system" class="hover:text-blue-600 transition-colors">{{ t('footer.verify_company') }}</Link></li>
                            <li><Link href="/enterprise" class="hover:text-blue-600 transition-colors">{{ t('footer.partnership') }}</Link></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="font-bold mb-6 text-sm uppercase tracking-widest" :class="isDarkMode ? 'text-white' : 'text-slate-900'">{{ t('footer.help') }}</h4>
                        <ul class="space-y-4 text-sm font-semibold" :class="isDarkMode ? 'text-slate-400' : 'text-slate-500'">
                            <li><Link href="/help" class="hover:text-blue-600 transition-colors">{{ t('footer.help_center') }}</Link></li>
                            <li><Link href="/help" class="hover:text-blue-600 transition-colors">{{ t('footer.faq') }}</Link></li>
                            <li><Link href="/privacy" class="hover:text-blue-600 transition-colors">{{ t('footer.privacy') }}</Link></li>
                        </ul>
                    </div>
                </div>
                
                <div class="pt-12 border-t flex flex-col md:flex-row justify-between items-center gap-8 text-xs font-bold uppercase tracking-widest" :class="isDarkMode ? 'border-slate-800 text-slate-500' : 'border-slate-100 text-slate-400'">
                    <p>© 2026 InternHub. {{ t('footer.all_rights') }}</p>
                    <div class="flex items-center gap-8">
                        <span>{{ t('footer.made_in') }}</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&display=swap');

body {
    font-family: 'Outfit', sans-serif;
}
</style>

