<script setup lang="ts">
import { ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import { ShieldAlert, ArrowLeft, Send } from 'lucide-vue-next';

interface Ticket {
    id: number;
    category: string;
    description: string;
    status: string;
    created_at_human: string;
    resolution_notes: string | null;
    reporter: { name: string; email: string };
    reportedUser: { name: string; email: string } | null;
    resolver: { name: string } | null;
}

const props = defineProps<{
    ticket: Ticket;
}>();

const form = useForm({
    status: props.ticket.status,
    resolution_notes: props.ticket.resolution_notes || ''
});

const submitResolution = () => {
    form.patch(`/admin/tickets/${props.ticket.id}/status`, {
        preserveScroll: true
    });
};
</script>

<template>
    <DashboardLayout>
        <div class="space-y-8 animate-fade-in max-w-4xl mx-auto">
            <Link href="/admin/tickets" class="inline-flex items-center gap-2 text-xs font-black text-slate-500 uppercase tracking-widest hover:text-slate-900 transition-colors">
                <ArrowLeft class="w-4 h-4" />
                Kembali ke Daftar
            </Link>

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight">Detail Tiket #TKT-{{ ticket.id.toString().padStart(4, '0') }}</h1>
                    <p class="text-slate-500 font-medium">Dibuka {{ ticket.created_at_human }}</p>
                </div>
                <div class="px-4 py-2 bg-slate-100 dark:bg-slate-800 rounded-full text-xs font-black uppercase tracking-widest text-slate-600">
                    {{ ticket.status }}
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="md:col-span-2 space-y-6">
                    <Card class="p-8 border-none shadow-premium rounded-[2.5rem]">
                        <h2 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Laporan</h2>
                        <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl mb-6">
                            <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-medium">
                                {{ ticket.description }}
                            </p>
                        </div>
                        <div class="flex items-center gap-6">
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Kategori</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ ticket.category }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Pelapor</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ ticket.reporter.name }}</p>
                            </div>
                            <div v-if="ticket.reportedUser">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Terlapor</p>
                                <p class="text-sm font-bold text-slate-900 dark:text-white">{{ ticket.reportedUser.name }}</p>
                            </div>
                        </div>
                    </Card>

                    <Card class="p-8 border-none shadow-premium rounded-[2.5rem] bg-indigo-50/50 dark:bg-indigo-900/10">
                        <h2 class="text-xs font-black text-indigo-400 uppercase tracking-widest mb-4">Resolusi Admin</h2>
                        <form @submit.prevent="submitResolution" class="space-y-6">
                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1 block mb-2">Ubah Status</label>
                                <select v-model="form.status" class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm p-4 outline-none focus:ring-4 focus:ring-indigo-500/20">
                                    <option value="open">Open</option>
                                    <option value="investigating">Investigating</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-[10px] font-black text-slate-500 uppercase tracking-widest ml-1 block mb-2">Catatan Resolusi</label>
                                <textarea v-model="form.resolution_notes" rows="4" placeholder="Tulis catatan penyelesaian atau sanksi di sini..." class="w-full bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-2xl text-sm p-4 outline-none focus:ring-4 focus:ring-indigo-500/20"></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" :disabled="form.processing" class="px-6 py-3 bg-indigo-600 text-white rounded-xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 transition-all flex items-center gap-2">
                                    <Send class="w-4 h-4" />
                                    {{ form.processing ? 'Menyimpan...' : 'Update Resolusi' }}
                                </button>
                            </div>
                        </form>
                    </Card>
                </div>
                
                <div class="space-y-6">
                    <Card class="p-6 border-none shadow-sm rounded-[2rem] bg-slate-50 dark:bg-slate-800/50">
                        <h3 class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4">Informasi Pelapor</h3>
                        <div class="space-y-3">
                            <p class="text-sm font-bold text-slate-900">{{ ticket.reporter.name }}</p>
                            <p class="text-xs text-slate-500">{{ ticket.reporter.email }}</p>
                        </div>
                    </Card>

                    <Card v-if="ticket.reportedUser" class="p-6 border-none shadow-sm rounded-[2rem] border border-red-100 dark:border-red-900/30">
                        <h3 class="text-xs font-black text-red-400 uppercase tracking-widest mb-4">Informasi Terlapor</h3>
                        <div class="space-y-3">
                            <p class="text-sm font-bold text-slate-900">{{ ticket.reportedUser.name }}</p>
                            <p class="text-xs text-slate-500">{{ ticket.reportedUser.email }}</p>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
