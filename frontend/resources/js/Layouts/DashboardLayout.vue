<script setup lang="ts">
import logger from '@/Lib/logger';
import axios from 'axios';
import { Link, router as inertiaRouter, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { 
    LayoutDashboard, Users, Briefcase, Calendar, 
    Settings, Bell, Search, Menu, X, LogOut,
    User, Shield, Building2, Activity, Key, Globe, MapPin, 
    BarChart3, Bookmark, ClipboardList,
    Sun, Moon
} from 'lucide-vue-next';
import { useAuthStore } from '@/Stores/auth';
import { useLangStore } from '@/Stores/lang';
import { useTheme } from '@/Composables/useTheme';
import AppLogo from '@/Components/AppLogo.vue';

const authStore = useAuthStore();
const langStore = useLangStore();
const page = usePage();
const { isDarkMode, toggleDarkMode, initTheme } = useTheme();

const roleLabel = computed(() => {
    const role = authStore.user?.role;
    if (role === 'super_admin') return t('roles.super_admin');
    if (role === 'admin') return t('roles.admin');
    if (role === 'hr') return t('roles.hr');
    if (role === 'mentor') return t('roles.mentor');
    return t('roles.student');
});

const closeNotifications = () => {
    isNotificationsOpen.value = false;
};

interface Toast {
    id: number;
    title: string;
    message: string;
}

const unreadCount = ref(0);
const activeToasts = ref<Toast[]>([]);
const toastTimers = new Set<ReturnType<typeof window.setTimeout>>();

const triggerToast = (title: string, message: string) => {
    const id = Date.now();
    activeToasts.value.push({ id, title, message });
    // Sound indicator fallback (subtle notification pop sound)
    try {
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-84.wav');
        audio.volume = 0.2;
        audio.play();
    } catch {
        // Ignore audio errors if blocked by browser autoplay policy
    }
    // Auto-remove after 6 seconds
    const timer = setTimeout(() => {
        activeToasts.value = activeToasts.value.filter(t => t.id !== id);
        toastTimers.delete(timer);
    }, 6000);
    toastTimers.add(timer);
};

onMounted(() => {
    initTheme();
    fetchNotifications(true); // First load
    window.addEventListener('click', closeNotifications);
    
    // Active WebSocket connection for real-time instant notification sync
    if (window.Echo && authStore.user) {
        // Listen to custom ApplicationStatusChanged events
        window.Echo.private(`user.${authStore.user.id}`)
            .listen('.ApplicationStatusChanged', () => {
                logger.log('Realtime notification: Application status changed');
                fetchNotifications(false);
            });

        // Listen to standard Laravel notifications (ShouldQueue)
        window.Echo.private(`App.Models.User.${authStore.user.id}`)
            .notification((notification: Record<string, unknown>) => {
                logger.log('Realtime notification: Broadcast notification created', notification);
                fetchNotifications(false);
            });
    }
});

onUnmounted(() => {
    window.removeEventListener('click', closeNotifications);
    toastTimers.forEach((timer) => window.clearTimeout(timer));
    toastTimers.clear();

    // Cleanup WebSocket listeners on unmount to prevent memory leaks
    if (window.Echo && authStore.user) {
        window.Echo.leave(`user.${authStore.user.id}`);
        window.Echo.leave(`App.Models.User.${authStore.user.id}`);
    }
});

const t = (key: string) => langStore.t(key);
const user = computed(() => authStore.user || {});
const currentPath = computed(() => new window.URL(page.url, window.location.origin).pathname);

const isSidebarOpen = ref(true);
const searchQuery = ref('');
const isMobileSheetOpen = ref(false);

const mobileNavItems = computed(() => navigation.value.slice(0, 4));
const extraNavItems = computed(() => navigation.value.slice(4));

const handleSearch = () => {
    const query = searchQuery.value.trim();
    if (!query) return;

    if (authStore.isSuperAdmin) {
        inertiaRouter.get('/super-admin/users', { search: query });
        return;
    }

    if (authStore.isAdmin) {
        inertiaRouter.get('/admin/users', { search: query });
        return;
    }

    inertiaRouter.get('/internships', { q: query });
};

const toggleSidebar = () => {
    isSidebarOpen.value = !isSidebarOpen.value;
};

interface SystemNotification {
    id: number;
    read_at: string | null;
    title?: string;
    message?: string;
    time?: string;
    created_at_human?: string;
    data?: {
        title?: string;
        message?: string;
    };
}

const isNotificationsOpen = ref(false);
const initialNotifications = computed(() => {
    const value = page.props.notifications;
    return Array.isArray(value) ? value as SystemNotification[] : [];
});
const notifications = ref<SystemNotification[]>(initialNotifications.value);
unreadCount.value = initialNotifications.value.filter(note => !note.read_at).length;
const webJson = axios.create({
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
});

const fetchNotifications = async (isFirstLoad = false) => {
    try {
        const response = await webJson.get('/notifications');
        const newNotifications: SystemNotification[] = response.data.notifications || [];
        const newUnreadCount: number = response.data.unreadCount || 0;

        // Trigger dynamic Toast alerts if a new unread notification is detected
        if (!isFirstLoad && newUnreadCount > unreadCount.value) {
            const freshlyUnread = newNotifications.filter(
                (newNote: SystemNotification) => !newNote.read_at && !notifications.value.some((oldNote: SystemNotification) => oldNote.id === newNote.id)
            );
            
            freshlyUnread.forEach((note: SystemNotification) => {
                const title = note.data?.title || note.title || 'Pemberitahuan Baru';
                const message = note.data?.message || note.message || 'Status lamaran Anda diperbarui.';
                triggerToast(title, message);
            });
        }

        notifications.value = newNotifications;
        unreadCount.value = newUnreadCount;
    } catch (error) {
        logger.error('Failed to fetch notifications:', error);
    }
};

const markAsRead = async (id: number) => {
    try {
        await webJson.post(`/notifications/${id}/read`);
        // Fast optimistic local UI update
        notifications.value = notifications.value.map(n => n.id === id ? { ...n, read_at: new Date().toISOString() } : n);
        if (unreadCount.value > 0) {
            unreadCount.value--;
        }
        fetchNotifications(true);
    } catch (error) {
        logger.error('Failed to mark notification as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await webJson.post('/notifications/read-all');
        // Fast optimistic local UI update
        notifications.value = notifications.value.map(n => ({ ...n, read_at: new Date().toISOString() }));
        unreadCount.value = 0;
        fetchNotifications(true);
    } catch (error) {
        logger.error('Failed to mark all as read:', error);
    }
};

const logout = async () => {
    if (authStore.loading) return;

    await authStore.logout();
};

const profileHref = computed(() => {
    return '/profile';
});

const navigation = computed(() => {
    const isSuperAdmin = authStore.isSuperAdmin;
    const isAdmin = authStore.isAdmin;
    const isHR = authStore.isHR;
    const isMentor = authStore.isMentor;

    let dashboardHref = '/dashboard';
    if (isSuperAdmin) dashboardHref = '/super-admin/dashboard';
    else if (isAdmin) dashboardHref = '/admin/dashboard';
    else if (isHR) dashboardHref = '/hr/dashboard';
    else if (isMentor) dashboardHref = '/mentor/dashboard';

    let baseNav = [
        { name: t('sidebar.dashboard'), icon: LayoutDashboard, href: dashboardHref },
    ];

    if (isSuperAdmin) {
        baseNav.push(
            { name: t('sidebar.global_users'), icon: Users, href: '/super-admin/users' },
            { name: t('sidebar.role_permission'), icon: Key, href: '/super-admin/roles' },
            { name: t('sidebar.system_integration'), icon: Globe, href: '/super-admin/integrations' },
            { name: t('sidebar.audit_logs'), icon: Activity, href: '/super-admin/audit-logs' },
            { name: t('sidebar.security_events'), icon: Shield, href: '/super-admin/security-events' },
            { name: t('sidebar.system_settings'), icon: Settings, href: '/super-admin/settings' }
        );

        // Dynamically add admin features if Super Admin checked the permissions
        if (authStore.hasPermission('view_companies')) {
            baseNav.push({ name: t('sidebar.company_moderation'), icon: Building2, href: '/admin/companies' });
        }
        if (authStore.hasPermission('view_internships')) {
            baseNav.push({ name: t('sidebar.internship_moderation'), icon: Briefcase, href: '/admin/internships' });
        }
    } else if (isAdmin) {
        baseNav.push(
            { name: t('sidebar.user_moderation'), icon: Users, href: '/admin/users' },
            { name: t('sidebar.company_moderation'), icon: Building2, href: '/admin/companies' },
            { name: t('sidebar.internship_moderation'), icon: Briefcase, href: '/admin/internships' },
            { name: t('sidebar.location_management'), icon: MapPin, href: '/admin/locations' },
            { name: t('sidebar.analytic_reports'), icon: BarChart3, href: '/admin/reports' },
            { name: t('sidebar.audit_logs'), icon: Activity, href: '/admin/audit-logs' }
        );
    } else if (isHR) {
        baseNav.push(
            { name: t('sidebar.manage_jobs'), icon: Briefcase, href: '/hr/internships' },
            { name: t('sidebar.manage_applicants'), icon: Users, href: '/hr/applications' },
            { name: t('sidebar.manage_team'), icon: User, href: '/hr/team' },
            { name: t('sidebar.company_profile'), icon: Building2, href: '/hr/company/edit' }
        );
    } else if (isMentor) {
        baseNav.push(
            { name: t('sidebar.mentee_list'), icon: Users, href: '/mentor/mentees' },
            { name: t('sidebar.mentor_dashboard'), icon: ClipboardList, href: '/mentor/attendance' },
            { name: t('sidebar.tasks'), icon: Calendar, href: '/mentor/tasks' }
        );
    } else {
        baseNav.push(
            { name: t('sidebar.browse_jobs'), icon: Search, href: '/internships' },
            { name: t('sidebar.my_applications'), icon: Briefcase, href: '/my-applications' },
            { name: t('sidebar.saved'), icon: Bookmark, href: '/saved-internships' },
            { name: t('sidebar.my_profile'), icon: User, href: '/profile' }
        );
    }

    return baseNav;
});
</script>

<template>
    <div class="min-h-screen bg-neutral-50 dark:bg-neutral-950 flex transition-colors duration-500 font-sans">
        <!-- Mobile Sidebar Overlay -->
        <div 
            v-if="isSidebarOpen"
            class="fixed inset-0 bg-neutral-900/60 backdrop-blur-sm z-40 lg:hidden transition-opacity"
            @click="isSidebarOpen = false"
        ></div>

        <!-- Modern Sidebar -->
        <aside 
            class="sidebar hidden lg:flex flex-col fixed inset-y-0 left-0 z-50 bg-white dark:bg-neutral-900 border-r border-neutral-100 dark:border-neutral-800 transition-all duration-500"
            :class="[
                isSidebarOpen ? 'w-80 translate-x-0' : 'w-80 -translate-x-full lg:w-24 lg:translate-x-0'
            ]"
        >
            <!-- Sidebar Header -->
            <div class="h-24 flex items-center px-8 shrink-0 overflow-hidden">
                <Link href="/" class="group" aria-label="InternHub Dashboard">
                    <AppLogo 
                        variant="compact" 
                        :show-text="isSidebarOpen" 
                        size="md" 
                        :is-dark-mode="isDarkMode" 
                        :role-label="roleLabel"
                    />
                </Link>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-8 space-y-2 overflow-y-auto">
                <Link 
                    v-for="item in navigation" 
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-4 px-4 py-4 rounded-2xl transition-all duration-300 group"
                    :class="[
                        currentPath === item.href
                        ? 'bg-primary-50 text-primary-600 dark:bg-primary-900/20 dark:text-primary-400' 
                        : 'text-neutral-500 hover:bg-neutral-50 dark:text-neutral-400 dark:hover:bg-neutral-800'
                    ]"
                >
                    <component :is="item.icon" class="w-6 h-6 shrink-0 group-hover:scale-110 transition-transform" />
                    <span v-if="isSidebarOpen" class="text-sm font-bold tracking-tight">{{ item.name }}</span>
                    
                    <div v-if="currentPath === item.href" class="absolute right-6 w-1.5 h-6 bg-primary-600 rounded-full animate-reveal"></div>
                </Link>
            </nav>

            <!-- Sidebar Footer -->
            <div class="p-6 border-t border-neutral-100 dark:border-neutral-800 shrink-0">
                <div 
                    v-if="isSidebarOpen"
                    class="p-4 bg-neutral-50 dark:bg-neutral-800/50 rounded-2xl mb-6 flex items-center gap-4 animate-reveal"
                >
                    <div class="w-10 h-10 bg-white dark:bg-neutral-900 rounded-xl flex items-center justify-center shadow-sm">
                        <User class="w-5 h-5 text-neutral-400" />
                    </div>
                    <div class="overflow-hidden">
                        <p class="text-xs font-bold text-neutral-900 dark:text-white truncate">{{ user.name }}</p>
                        <p class="text-[10px] font-bold text-neutral-400 uppercase tracking-widest truncate">{{ roleLabel }}</p>
                    </div>
                </div>
                
                <button 
                    class="w-full flex items-center gap-4 px-4 py-4 rounded-2xl text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/10 transition-all group disabled:opacity-60 disabled:cursor-wait"
                    :disabled="authStore.loading"
                    @click="logout"
                >
                    <LogOut class="w-6 h-6 shrink-0 group-hover:translate-x-1 transition-transform" />
                    <span v-if="isSidebarOpen" class="text-sm font-bold tracking-tight">{{ langStore.t('nav.logout') }}</span>
                </button>
            </div>
        </aside>

        <!-- Main Content Area -->
        <main 
            class="flex-1 flex flex-col transition-all duration-500 pb-32 lg:pb-0"
            :class="[isSidebarOpen ? 'lg:pl-80' : 'lg:pl-24']"
        >
            <!-- Modern Topbar -->
            <header class="main-header h-24 flex items-center justify-between px-8 bg-white/80 dark:bg-neutral-950/80 backdrop-blur-md sticky top-0 z-40 border-b border-neutral-100 dark:border-neutral-800">
                <div class="flex items-center gap-6">
                    <button 
                        class="hidden lg:flex w-10 h-10 items-center justify-center text-neutral-400 hover:text-neutral-900 dark:hover:text-white transition-colors"
                        @click="toggleSidebar"
                    >
                        <Menu v-if="!isSidebarOpen" class="w-6 h-6" />
                        <X v-else class="w-6 h-6" />
                    </button>

                    <div class="lg:hidden flex items-center h-10">
                        <AppLogo variant="compact" :show-text="true" size="sm" :is-dark-mode="isDarkMode" :role-label="roleLabel" />
                    </div>
                    
                    <div class="relative group hidden md:block">
                        <Search class="absolute left-5 top-1/2 -translate-y-1/2 w-4 h-4 text-neutral-400 group-focus-within:text-primary-600 transition-colors" />
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            :placeholder="t('common.search_everything')"
                            class="bg-neutral-50 dark:bg-neutral-900 border border-neutral-100 dark:border-neutral-800 rounded-full py-3 pl-12 pr-6 text-xs font-bold focus:ring-4 focus:ring-primary-500/10 w-96 transition-all focus:bg-white dark:focus:bg-neutral-800"
                            @keyup.enter="handleSearch"
                        />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <!-- Language Switcher -->
                    <div class="flex items-center bg-neutral-50 dark:bg-neutral-900 rounded-2xl p-1.5 border border-neutral-100 dark:border-neutral-800">
                        <button 
                            class="px-3 py-2 rounded-xl text-[10px] font-bold transition-all"
                            :class="[langStore.locale === 'id' ? 'bg-white dark:bg-neutral-800 text-primary-600 shadow-sm' : 'text-neutral-400 hover:text-neutral-600']"
                            @click="langStore.setLocale('id')"
                        >
                            ID
                        </button>
                        <button 
                            class="px-3 py-2 rounded-xl text-[10px] font-bold transition-all"
                            :class="[langStore.locale === 'en' ? 'bg-white dark:bg-neutral-800 text-primary-600 shadow-sm' : 'text-neutral-400 hover:text-neutral-600']"
                            @click="langStore.setLocale('en')"
                        >
                            EN
                        </button>
                    </div>

                    <!-- Theme Toggle -->
                    <button 
                        class="w-12 h-12 flex items-center justify-center text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-900 rounded-2xl transition-all border border-transparent hover:border-neutral-100 dark:hover:border-neutral-800"
                        @click="toggleDarkMode"
                    >
                        <Sun v-if="isDarkMode" class="w-6 h-6" />
                        <Moon v-else class="w-6 h-6" />
                    </button>

                    <!-- Notifications Dropdown -->
                    <div class="relative">
                        <button 
                            class="w-12 h-12 flex items-center justify-center text-neutral-400 hover:bg-neutral-50 dark:hover:bg-neutral-900 rounded-2xl relative transition-all border border-transparent hover:border-neutral-100 dark:hover:border-neutral-800"
                            @click.stop="isNotificationsOpen = !isNotificationsOpen"
                        >
                            <Bell class="w-6 h-6" />
                            <span v-if="unreadCount > 0" class="absolute top-2.5 right-2.5 min-w-[18px] h-[18px] bg-rose-500 border-2 border-white dark:border-neutral-950 rounded-full flex items-center justify-center text-[8px] font-bold text-white px-1 shadow-sm">
                                {{ unreadCount }}
                            </span>
                        </button>

                        <div 
                            v-if="isNotificationsOpen" 
                            class="absolute right-0 top-full mt-4 w-80 bg-white dark:bg-neutral-900 rounded-[2rem] shadow-2xl border border-neutral-100 dark:border-neutral-800 p-6 z-[9999] animate-reveal origin-top-right"
                            @click.stop
                        >
                            <div class="flex items-center justify-between mb-6 border-b border-neutral-50 dark:border-neutral-800 pb-4">
                                <h4 class="text-xs font-bold text-neutral-900 dark:text-white uppercase tracking-widest">{{ t('sidebar.notifications') }}</h4>
                                <span 
                                    v-if="notifications.length > 0"
                                    class="text-[10px] font-bold text-primary-600 uppercase tracking-widest cursor-pointer hover:underline"
                                    @click="markAllAsRead"
                                >
                                    {{ t('sidebar.mark_all_read') }}
                                </span>
                            </div>
                            <div class="space-y-4 max-h-[300px] overflow-y-auto custom-scrollbar">
                                <div 
                                    v-for="note in notifications" 
                                    :key="note.id" 
                                    class="p-4 rounded-2xl hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors cursor-pointer group relative"
                                    :class="{'opacity-60': note.read_at}"
                                    @click="markAsRead(note.id)"
                                >
                                    <div v-if="!note.read_at" class="absolute top-4 right-4 w-1.5 h-1.5 bg-primary-600 rounded-full"></div>
                                    <p class="text-xs font-bold text-neutral-900 dark:text-white group-hover:text-primary-600 transition-colors">
                                    {{ note.data?.title || note.title || t('sidebar.notification_default_title') }}
                                </p>
                                <p class="text-[10px] font-medium text-neutral-500 mt-1">
                                        {{ note.data?.message || note.message || t('sidebar.notification_default_message') }}
                                </p>
                                    <p class="text-[9px] font-bold text-neutral-400 mt-2 uppercase tracking-widest">
                                        {{ note.created_at_human || note.time }}
                                    </p>
                                </div>
                                <div v-if="notifications.length === 0" class="text-center py-8 text-neutral-400 text-xs italic">
                                    {{ t('sidebar.no_notifications') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="h-10 w-[1px] bg-neutral-100 dark:bg-neutral-800"></div>

                    <Link :href="profileHref" class="flex items-center gap-3 pl-2 group">
                        <div class="text-right hidden sm:block">
                            <p class="text-xs font-bold text-neutral-900 dark:text-white truncate">{{ user.name }}</p>
                            <p class="text-[9px] font-bold text-primary-600 uppercase tracking-widest truncate">{{ roleLabel }}</p>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-neutral-100 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 flex items-center justify-center group-hover:scale-105 transition-transform overflow-hidden">
                            <img v-if="user.avatar_url" loading="lazy" decoding="async" :src="user.avatar_url" class="w-full h-full object-cover" />
                            <User v-else class="w-6 h-6 text-neutral-400" />
                        </div>
                    </Link>
                </div>
            </header>

            <!-- Dashboard Content -->
            <div class="p-8 lg:p-12 animate-reveal">
                <div v-if="$slots.header" class="mb-8">
                    <slot name="header" />
                </div>
                <slot />
            </div>
        </main>

        <!-- Mobile Bottom Nav -->
        <nav class="fixed bottom-6 left-1/2 -translate-x-1/2 w-[90%] max-w-sm bg-white/80 dark:bg-neutral-900/80 backdrop-blur-xl rounded-2xl shadow-[0_20px_50px_rgba(8,112,184,0.15)] dark:shadow-2xl border border-white/50 dark:border-neutral-800/50 z-50 flex items-center justify-around p-2 lg:hidden">
            <Link 
                v-for="item in mobileNavItems" 
                :key="item.name" 
                :href="item.href"
                class="flex flex-col items-center gap-1 p-2 rounded-2xl transition-all"
                :class="[currentPath === item.href ? 'text-primary-600' : 'text-neutral-400 hover:text-neutral-900 dark:hover:text-white']"
            >
                <div :class="[currentPath === item.href ? 'bg-primary-50 dark:bg-primary-900/20 p-2 rounded-xl' : '']">
                    <component :is="item.icon" class="w-6 h-6" />
                </div>
                <span v-if="currentPath === item.href" class="text-[9px] font-bold tracking-tight mt-1 truncate">{{ item.name }}</span>
            </Link>
            
            <button 
                class="flex flex-col items-center gap-1 p-2 rounded-2xl transition-all text-neutral-400 hover:text-neutral-900 dark:hover:text-white"
                @click="isMobileSheetOpen = true"
            >
                <div :class="[isMobileSheetOpen ? 'bg-primary-50 dark:bg-primary-900/20 p-2 rounded-xl text-primary-600' : '']">
                    <Menu class="w-6 h-6" />
                </div>
                <span v-if="isMobileSheetOpen" class="text-[9px] font-bold tracking-tight mt-1 truncate text-primary-600">{{ t('nav.more') || 'Menu' }}</span>
            </button>
        </nav>

        <!-- Mobile Bottom Sheet -->
        <div v-if="isMobileSheetOpen" class="fixed inset-0 z-[60] lg:hidden flex items-end">
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm transition-opacity" @click="isMobileSheetOpen = false"></div>
            <div class="bg-white dark:bg-neutral-900 w-full rounded-t-[3rem] p-8 pb-32 shadow-2xl transform transition-transform border-t border-neutral-100 dark:border-neutral-800 relative z-10 animate-slide-up max-h-[85vh] overflow-y-auto custom-scrollbar">
                
                <div class="w-16 h-1.5 bg-neutral-200 dark:bg-neutral-800 rounded-full mx-auto mb-8"></div>
                
                <div class="flex items-center gap-4 p-4 bg-neutral-50 dark:bg-neutral-800/50 rounded-2xl mb-8">
                    <div class="w-12 h-12 rounded-2xl overflow-hidden bg-neutral-200 dark:bg-neutral-800 flex items-center justify-center border border-neutral-300 dark:border-neutral-700">
                        <img v-if="user.avatar_url" :src="user.avatar_url" class="w-full h-full object-cover" />
                        <User v-else class="w-6 h-6 text-neutral-400" />
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-neutral-900 dark:text-white">{{ user.name }}</h4>
                        <p class="text-[10px] font-bold text-primary-600 uppercase tracking-widest">{{ roleLabel }}</p>
                    </div>
                </div>

                <div v-if="extraNavItems.length > 0" class="space-y-2 mb-8">
                    <h5 class="text-xs font-bold text-neutral-400 uppercase tracking-widest mb-4 px-2">Menu Lainnya</h5>
                    <Link 
                        v-for="item in extraNavItems" 
                        :key="item.name" 
                        :href="item.href"
                        class="flex items-center gap-4 p-4 rounded-2xl hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors"
                        :class="[currentPath === item.href ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600' : 'text-neutral-500 dark:text-neutral-400']"
                        @click="isMobileSheetOpen = false"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        <span class="text-xs font-bold">{{ item.name }}</span>
                    </Link>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-8">
                    <button class="p-4 rounded-2xl bg-neutral-50 dark:bg-neutral-800/50 flex flex-col items-center gap-3 text-neutral-500 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white transition-colors" @click="toggleDarkMode">
                        <Sun v-if="isDarkMode" class="w-6 h-6" />
                        <Moon v-else class="w-6 h-6" />
                        <span class="text-[10px] font-bold uppercase tracking-widest">{{ isDarkMode ? 'Mode Terang' : 'Mode Gelap' }}</span>
                    </button>
                    <div class="p-4 rounded-2xl bg-neutral-50 dark:bg-neutral-800/50 flex flex-col items-center justify-center gap-3">
                        <div class="flex gap-2 bg-white dark:bg-neutral-900 p-1 rounded-xl shadow-sm">
                            <button class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all" :class="langStore.locale === 'id' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600' : 'text-neutral-400 hover:text-neutral-600'" @click="langStore.setLocale('id')">ID</button>
                            <button class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all" :class="langStore.locale === 'en' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-600' : 'text-neutral-400 hover:text-neutral-600'" @click="langStore.setLocale('en')">EN</button>
                        </div>
                        <span class="text-[10px] font-bold text-neutral-500 dark:text-neutral-400 uppercase tracking-widest">Bahasa</span>
                    </div>
                </div>

                <button class="w-full p-4 rounded-2xl bg-rose-50 dark:bg-rose-900/10 text-rose-600 flex items-center justify-center gap-3 hover:bg-rose-100 dark:hover:bg-rose-900/20 transition-colors font-bold text-xs" @click="logout">
                    <LogOut class="w-5 h-5" />
                    {{ langStore.t('nav.logout') || 'Keluar' }}
                </button>

            </div>
        </div>

        <!-- Live Toast Notification Container -->
        <div class="fixed bottom-10 right-10 z-[99999] flex flex-col gap-4 pointer-events-none max-w-sm w-full">
            <TransitionGroup name="toast">
                <div 
                    v-for="toast in activeToasts" 
                    :key="toast.id"
                    class="bg-white dark:bg-neutral-900 border border-slate-100 dark:border-neutral-800 shadow-[0_20px_50px_rgba(8,_112,_184,_0.08)] dark:shadow-2xl rounded-2xl p-5 flex items-start gap-4 pointer-events-auto border-l-4 border-l-primary-600 transition-all duration-300"
                >
                    <div class="w-10 h-10 rounded-2xl bg-primary-50 dark:bg-primary-950/40 flex items-center justify-center shrink-0">
                        <Bell class="w-5 h-5 text-primary-600 animate-bounce" />
                    </div>
                    <div class="space-y-1 overflow-hidden flex-1">
                        <p class="text-xs font-bold text-neutral-900 dark:text-white truncate">
                            {{ toast.title }}
                        </p>
                        <p class="text-[10px] font-bold text-neutral-500 dark:text-neutral-400 leading-relaxed truncate">
                            {{ toast.message }}
                        </p>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </div>
</template>

<style scoped>
@keyframes reveal {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.animate-reveal {
    animation: reveal 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes slide-up {
    from { transform: translateY(100%); }
    to { transform: translateY(0); }
}

.animate-slide-up {
    animation: slide-up 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

/* Custom Premium Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

aside nav {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}
.dark aside nav {
    scrollbar-color: #334155 transparent;
}

/* Toast Transition Animations */
.toast-enter-active,
.toast-leave-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.toast-enter-from {
    opacity: 0;
    transform: translateY(30px) scale(0.9);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(100px);
}
</style>

