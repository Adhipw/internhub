<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import { ShieldAlert, CheckCircle2, AlertCircle, Clock } from 'lucide-vue-next';
import Card from '@/Components/Card.vue';
import { formatDate } from '@/Lib/utils';

interface Ticket {
    id: number;
    category: string;
    description: string;
    status: string;
    created_at: string;
    created_at_human: string;
    reporter: { name: string };
    reported_user: { name: string } | null;
}

const props = defineProps<{
    tickets: { data: Ticket[], links: any[] };
}>();

const statusColors = {
    open: 'bg-red-100 text-red-700',
    investigating: 'bg-orange-100 text-orange-700',
    resolved: 'bg-emerald-100 text-emerald-700',
    closed: 'bg-slate-100 text-slate-700'
};
</script>

<template>
    <DashboardLayout>
        <div class="space-y-8 animate-fade-in">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Ticketing & Dispute</h1>
                    <p class="text-slate-500 font-medium">Selesaikan perselisihan dan tinjau laporan pelanggaran.</p>
                </div>
            </div>

            <Card class="p-0 border-none shadow-premium rounded-[3rem] overflow-hidden bg-white dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-white/5">
                                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">ID</th>
                                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Pelapor</th>
                                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Kategori</th>
                                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Status</th>
                                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Tanggal</th>
                                <th class="px-8 py-6 text-[10px] font-black text-slate-400 uppercase tracking-widest">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 dark:divide-white/5">
                            <tr v-for="ticket in tickets.data" :key="ticket.id" class="hover:bg-slate-50/50 dark:hover:bg-white/5 transition-colors">
                                <td class="px-8 py-6 text-sm font-black text-slate-900 dark:text-white">#TKT-{{ ticket.id.toString().padStart(4, '0') }}</td>
                                <td class="px-8 py-6">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-bold">{{ ticket.reporter.name.charAt(0) }}</div>
                                        <span class="text-sm font-bold text-slate-900 dark:text-white">{{ ticket.reporter.name }}</span>
                                    </div>
                                </td>
                                <td class="px-8 py-6">
                                    <span class="text-xs font-bold text-slate-600 dark:text-slate-300">{{ ticket.category }}</span>
                                </td>
                                <td class="px-8 py-6">
                                    <span :class="['px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest', statusColors[ticket.status as keyof typeof statusColors]]">
                                        {{ ticket.status }}
                                    </span>
                                </td>
                                <td class="px-8 py-6 text-xs font-bold text-slate-500">{{ ticket.created_at_human }}</td>
                                <td class="px-8 py-6">
                                    <Link :href="`/admin/tickets/${ticket.id}`" class="text-xs font-black text-primary-600 hover:text-primary-700 uppercase tracking-widest">Tinjau</Link>
                                </td>
                            </tr>
                            <tr v-if="tickets.data.length === 0">
                                <td colspan="6" class="px-8 py-12 text-center text-slate-500 font-medium">Belum ada tiket yang masuk.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>
