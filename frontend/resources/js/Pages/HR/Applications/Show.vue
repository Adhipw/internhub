<script setup lang="ts">
import logger from '@/Lib/logger';
import { Link, router as inertiaRouter } from '@inertiajs/vue3';
import { ref, reactive, onMounted, computed } from 'vue';
import api from '@/Services/api';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Button from '@/Components/Button.vue';
import Input from '@/Components/Input.vue';
import Modal from '@/Components/Modal.vue';
import ApplicationMessages from '@/Components/ApplicationMessages.vue';
import { 
  ArrowLeftIcon, DocumentArrowDownIcon, CalendarIcon, CheckCircleIcon,
  XCircleIcon, ChatBubbleBottomCenterTextIcon, VideoCameraIcon,
  MapPinIcon, UsersIcon, AcademicCapIcon, SparklesIcon, IdentificationIcon
} from '@heroicons/vue/24/outline';
import type { Application } from '@/Types/application';
import type { User } from '@/Types/user';

const props = defineProps<{
    application?: Application;
    mentors?: { id: number, user_id: number, user: User }[];
}>();

const application = computed(() => props.application || null);
const mentors = computed(() => props.mentors || []);
const loading = ref(false);

const showStatusModal = ref(false);
const showInterviewModal = ref(false);
const showMentorModal = ref(false);
const selectedStatus = ref('');

const aiSummary = ref('');
const aiSummaryLoading = ref(false);

const generateAiSummary = async () => {
    if (!application.value) return;
    aiSummaryLoading.value = true;
    try {
        const response = await api.get(`/hr/applications/${application.value.id}/ai-summary`);
        aiSummary.value = response.data.data?.summary || 'Gagal memuat ringkasan.';
    } catch (error) {
        logger.error('Failed to generate AI summary:', error);
        aiSummary.value = 'Terjadi kesalahan sistem saat menghubungi AI Perekrutan.';
    } finally {
        aiSummaryLoading.value = false;
    }
};

const statusForm = reactive({
  status: '',
  notes: '',
  processing: false,
});

const interviewForm = reactive({
  scheduled_at: '',
  type: 'online',
  meeting_link: '',
  location: '',
  notes: '',
  processing: false,
});

const mentorForm = reactive({
  mentor_user_id: application.value?.interviewer_id || '' as string | number,
  processing: false,
  errors: {} as any,
});

const rejectionReasons = [
  { id: 'QUALIFICATION_MISMATCH', label: 'Kualifikasi belum sesuai posisi ini' },
  { id: 'QUOTA_FULL', label: 'Kuota magang sudah terpenuhi' },
  { id: 'FUTURE_CONSIDERATION', label: 'Akan kami pertimbangkan untuk posisi lain' },
  { id: 'SKILL_GAP', label: 'Terdapat gap keahlian yang signifikan' },
  { id: 'OTHER', label: 'Alasan lainnya' },
];

onMounted(() => {
    if (application.value && application.value.user?.detail) {
        generateAiSummary();
    }
});

const updateStatus = (status: string) => {
  selectedStatus.value = status;
  statusForm.status = status;
  showStatusModal.value = true;
};

const submitStatus = async () => {
  if (!application.value) return;
  statusForm.processing = true;
  try {
    await api.post(`/hr/applications/${application.value.id}/status`, statusForm);
    showStatusModal.value = false;
    statusForm.notes = '';
    inertiaRouter.reload({ only: ['application'] });
  } catch (error) {
    alert('Gagal memperbarui status.');
  } finally {
    statusForm.processing = false;
  }
};

const submitInterview = async () => {
  if (!application.value) return;
  interviewForm.processing = true;
  try {
    await api.post(`/hr/applications/${application.value.id}/schedule-interview`, interviewForm);
    showInterviewModal.value = false;
    inertiaRouter.reload({ only: ['application'] });
  } catch (error) {
    alert('Gagal menjadwalkan interview.');
  } finally {
    interviewForm.processing = false;
  }
};

const submitMentor = async () => {
  if (!application.value) return;
  mentorForm.processing = true;
  mentorForm.errors = {};
  try {
    await api.post(`/hr/applications/${application.value.id}/assign-mentor`, {
        mentor_user_id: mentorForm.mentor_user_id
    });
    showMentorModal.value = false;
    inertiaRouter.reload({ only: ['application'] });
  } catch (error: any) {
    if (error.response?.data?.errors) {
        mentorForm.errors = error.response.data.errors;
    } else {
        alert('Gagal menugaskan mentor.');
    }
  } finally {
    mentorForm.processing = false;
  }
};

const verifyDocument = async (id: number, status: string) => {
    let notes = '';
    if (status === 'rejected') {
        notes = prompt('Masukkan alasan penolakan:') || '';
        if (!notes) return;
    }

    try {
        await api.post(`/onboarding-documents/${id}/verify`, { status, notes });
        inertiaRouter.reload({ only: ['application'] });
    } catch (error) {
        alert('Gagal memverifikasi dokumen.');
    }
};
</script>

<template>
  <DashboardLayout>
    <template #header v-if="application">
        <div class="flex items-center justify-between pb-6 border-b border-gray-100 dark:border-gray-800">
          <div class="flex items-center">
            <Link href="/hr/applications" class="mr-6 p-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl transition-all">
              <ArrowLeftIcon class="w-5 h-5" />
            </Link>
            <div>
              <div class="flex items-center space-x-3 mb-1">
                <h2 class="text-2xl font-display font-bold text-slate-900 dark:text-white tracking-tight">{{ application.user.name }}</h2>
                <StatusBadge :status="application.status" size="sm" />
              </div>
              <p class="text-sm text-slate-500 font-medium">Melamar untuk <span class="text-slate-900 dark:text-slate-300">{{ application.internship.title }}</span> • Dikirim {{ application.created_at_human }}</p>
            </div>
          </div>
          <div class="flex items-center space-x-3">
            <Button variant="secondary" @click="showInterviewModal = true" v-if="application.status !== 'rejected'">
              <CalendarIcon class="w-4 h-4 mr-2" />
              Jadwal Interview
            </Button>
            <div class="h-6 w-px bg-slate-200 dark:bg-slate-700 mx-2"></div>
            <Button variant="danger" @click="updateStatus('rejected')" v-if="application.status !== 'rejected'">
              Tolak
            </Button>
            <Button variant="success" @click="updateStatus('accepted')" v-if="application.status !== 'accepted'">
              Terima Kandidat
            </Button>
          </div>
        </div>
      </template>

    <div v-if="loading" class="flex justify-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
    </div>

    <div v-else-if="application" class="mt-10 grid grid-cols-1 lg:grid-cols-12 gap-10">
      <!-- Left: Profile Insights -->
      <div class="lg:col-span-8 space-y-12">
        <!-- Candidate Overview Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div class="p-6 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800">
            <div class="flex items-center text-slate-400 mb-3">
              <AcademicCapIcon class="w-4 h-4 mr-2" />
              <span class="text-[10px] font-bold uppercase tracking-widest">Pendidikan Terakhir</span>
            </div>
            <p class="text-sm font-bold text-slate-900 dark:text-white truncate">
              {{ application.user.detail?.education?.[0]?.institution || 'N/A' }}
            </p>
          </div>
          <div class="p-6 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800">
            <div class="flex items-center text-slate-400 mb-3">
              <SparklesIcon class="w-4 h-4 mr-2" />
              <span class="text-[10px] font-bold uppercase tracking-widest">Top Skill</span>
            </div>
            <p class="text-sm font-bold text-slate-900 dark:text-white truncate">
              {{ application.user.detail?.skills?.[0] || 'N/A' }}
            </p>
          </div>
          <div class="p-6 bg-slate-50 dark:bg-slate-900/50 rounded-2xl border border-slate-100 dark:border-slate-800">
            <div class="flex items-center text-slate-400 mb-3">
              <IdentificationIcon class="w-4 h-4 mr-2" />
              <span class="text-[10px] font-bold uppercase tracking-widest">Kontak</span>
            </div>
            <p class="text-sm font-bold text-slate-900 dark:text-white truncate">
              {{ application.user.detail?.phone_number || 'N/A' }}
            </p>
          </div>
        </div>

        <!-- AI Candidate Smart Summary Card -->
        <section class="animate-reveal">
          <div class="p-8 bg-gradient-to-br from-primary-500/5 to-indigo-500/5 dark:from-primary-950/20 dark:to-indigo-950/20 border border-primary-100/50 dark:border-primary-900/30 rounded-3xl shadow-sm relative overflow-hidden">
            <!-- Glassmorphism decorative blur -->
            <div class="absolute -right-16 -top-16 w-36 h-36 bg-primary-600/10 rounded-full blur-3xl"></div>
            <div class="absolute -left-16 -bottom-16 w-36 h-36 bg-indigo-600/10 rounded-full blur-3xl"></div>

            <div class="flex items-center justify-between mb-6 relative z-10">
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-2xl bg-primary-600 flex items-center justify-center shadow-lg shadow-primary-500/20">
                  <SparklesIcon class="w-5 h-5 text-white animate-pulse" />
                </div>
                <div>
                  <h3 class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-widest">AI Candidate Smart Summary</h3>
                  <p class="text-[10px] text-slate-500 dark:text-neutral-400 font-medium">Analisis Resume & Portofolio Otomatis</p>
                </div>
              </div>
              <button 
                @click="generateAiSummary" 
                :disabled="aiSummaryLoading"
                class="px-4 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:text-primary-600 dark:hover:text-primary-400 hover:border-primary-200 text-xs font-black rounded-xl transition-all shadow-sm flex items-center gap-2"
              >
                <span v-if="aiSummaryLoading" class="animate-spin rounded-full h-3.5 w-3.5 border-b-2 border-primary-600 shrink-0"></span>
                <span>{{ aiSummaryLoading ? 'Menganalisis...' : (aiSummary ? 'Analisis Ulang' : 'Mulai Analisis AI') }}</span>
              </button>
            </div>

            <div class="relative z-10">
              <div v-if="aiSummaryLoading" class="space-y-3 py-4">
                <div class="h-4 bg-slate-100 dark:bg-slate-800 rounded-full w-3/4 animate-pulse"></div>
                <div class="h-4 bg-slate-100 dark:bg-slate-800 rounded-full w-5/6 animate-pulse"></div>
                <div class="h-4 bg-slate-100 dark:bg-slate-800 rounded-full w-2/3 animate-pulse"></div>
              </div>
              <div v-else-if="aiSummary" class="text-sm text-slate-700 dark:text-slate-300 space-y-4 leading-relaxed font-semibold">
                <div class="prose prose-slate dark:prose-invert max-w-none text-xs leading-relaxed space-y-3 whitespace-pre-line">
                  {{ aiSummary }}
                </div>
              </div>
              <div v-else class="text-center py-6 text-slate-400 dark:text-neutral-500 text-xs italic">
                Klik tombol di atas untuk menganalisis CV dan profil kandidat menggunakan Gemini AI secara real-time.
              </div>
            </div>
          </div>
        </section>

        <!-- Cover Letter / Intro -->
        <section>
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Pesan Pengantar (Cover Letter)</h3>
          <div class="p-8 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-3xl shadow-sm italic leading-relaxed text-slate-700 dark:text-slate-300">
            <p v-if="application.cover_letter" class="whitespace-pre-wrap">{{ application.cover_letter }}</p>
            <p v-else class="text-slate-400 italic">Kandidat tidak menyertakan pesan pengantar.</p>
          </div>
        </section>

        <!-- Documents Section -->
        <section>
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Dokumen Pendukung</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <a v-if="application.cv_snapshot" :href="'/storage-private/cvs/' + (application.cv_snapshot?.split('/').pop() || '')" target="_blank" class="flex items-center p-5 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl hover:border-primary-500 hover:shadow-md transition-all group">
              <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-xl group-hover:bg-primary-50 dark:group-hover:bg-primary-900/30 transition-colors">
                <DocumentArrowDownIcon class="w-6 h-6 text-slate-400 group-hover:text-primary-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-bold text-slate-900 dark:text-white">Curriculum Vitae</p>
                <p class="text-xs text-slate-500">Buka dalam tab baru</p>
              </div>
            </a>
            <a v-if="application.portfolio_snapshot" :href="'/storage-private/portfolios/' + (application.portfolio_snapshot?.split('/').pop() || '')" target="_blank" class="flex items-center p-5 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl hover:border-primary-500 hover:shadow-md transition-all group">
              <div class="p-3 bg-slate-50 dark:bg-slate-900 rounded-xl group-hover:bg-primary-50 dark:group-hover:bg-primary-900/30 transition-colors">
                <DocumentArrowDownIcon class="w-6 h-6 text-slate-400 group-hover:text-primary-600" />
              </div>
              <div class="ml-4">
                <p class="text-sm font-bold text-slate-900 dark:text-white">Portofolio</p>
                <p class="text-xs text-slate-500">Buka dalam tab baru</p>
              </div>
            </a>
          </div>
        </section>

        <!-- Onboarding Documents Section (Batch 7) -->
        <section v-if="application.status === 'accepted' || application.status === 'onboarding'">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Dokumen Onboarding (Verifikasi)</h3>
            <span class="px-2 py-0.5 bg-primary-50 text-primary-600 text-[10px] font-black rounded-lg">PROSES ONBOARDING</span>
          </div>

          <div class="grid grid-cols-1 gap-4">
            <div v-for="doc in application.onboarding_documents" :key="doc.id" class="p-6 bg-white dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-3xl shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="flex gap-4">
                        <div class="w-12 h-12 bg-slate-50 dark:bg-slate-900 rounded-xl flex items-center justify-center text-slate-400">
                            <DocumentArrowDownIcon class="w-6 h-6" />
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-900 dark:text-white capitalize">{{ doc.type.replace('_', ' ') }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <StatusBadge :status="doc.status || 'pending'" size="xs" />
                                <span class="text-[10px] text-slate-400 font-medium">Diunggah {{ doc.created_at_human }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a :href="'/api/v1/onboarding-documents/' + doc.id + '/download'" target="_blank" class="p-2.5 bg-slate-50 dark:bg-slate-900 text-slate-400 hover:text-primary-600 rounded-xl transition-all">
                            <DocumentArrowDownIcon class="w-5 h-5" />
                        </a>
                        <template v-if="doc.status === 'pending'">
                            <button @click="verifyDocument(doc.id, 'verified')" class="p-2.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-600 hover:text-white rounded-xl transition-all shadow-sm">
                                <CheckCircleIcon class="w-5 h-5" />
                            </button>
                            <button @click="verifyDocument(doc.id, 'rejected')" class="p-2.5 bg-rose-50 text-rose-600 hover:bg-rose-600 hover:text-white rounded-xl transition-all shadow-sm">
                                <XCircleIcon class="w-5 h-5" />
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <div v-if="!application.onboarding_documents || application.onboarding_documents.length === 0" class="p-10 bg-slate-50 dark:bg-slate-900/50 rounded-[2.5rem] border-2 border-dashed border-slate-100 dark:border-slate-800 text-center">
                <p class="text-sm text-slate-400 font-bold">Kandidat belum mengunggah dokumen onboarding.</p>
            </div>
          </div>
        </section>

        <!-- In-App Messaging Section -->
        <section>
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Pesan Masuk</h3>
          <ApplicationMessages :application-id="application.id" />
        </section>
      </div>

      <!-- Right: Timeline & Actions -->
      <div class="lg:col-span-4 space-y-10">
        <!-- Internal Notes -->
        <section>
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Catatan Internal HR</h3>
          <div class="p-6 bg-slate-50 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 rounded-2xl">
            <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed mb-6">{{ application.hr_notes || 'Belum ada catatan internal.' }}</p>
            <div class="grid grid-cols-1 gap-2">
              <button @click="updateStatus(application.status)" class="w-full py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg hover:bg-slate-50 transition-colors">
                Edit Catatan
              </button>
              <button @click="showMentorModal = true" class="w-full py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg hover:bg-slate-50 transition-colors">
                Tugaskan Mentor
              </button>
            </div>
          </div>
        </section>

        <!-- Application Timeline -->
        <section>
          <h3 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-6">Log Aktivitas</h3>
          <div class="space-y-8 relative before:absolute before:left-[11px] before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-100 dark:before:bg-slate-800">
            <div v-for="(event, index) in application.timeline" :key="index" class="relative pl-8">
              <div class="absolute left-0 top-1.5 w-[24px] h-[24px] rounded-full bg-white dark:bg-slate-900 border-2 border-slate-200 dark:border-slate-700 z-10 flex items-center justify-center">
                <div class="w-1.5 h-1.5 rounded-full bg-slate-300"></div>
              </div>
              <p class="text-sm font-bold text-slate-900 dark:text-white">{{ event.label }}</p>
              <p class="text-[10px] font-medium text-slate-400 uppercase mt-0.5">{{ event.date }}</p>
              <p class="mt-2 text-xs text-slate-500 leading-relaxed">{{ event.description }}</p>
            </div>
          </div>
        </section>
      </div>
    </div>

    <template v-if="application">
      <!-- Modals (Decision Style: Serious & Contextual) -->
      <Modal :show="showStatusModal" @close="showStatusModal = false" :title="selectedStatus === 'accepted' ? 'Terima Kandidat' : 'Konfirmasi Penolakan'">
      <form @submit.prevent="submitStatus" class="p-10 space-y-8">
        <div class="flex items-start gap-6 p-6 bg-slate-50 dark:bg-slate-900 rounded-[2rem] border border-slate-100 dark:border-slate-800">
          <div :class="['w-16 h-16 rounded-2xl flex items-center justify-center shrink-0', selectedStatus === 'accepted' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-rose-500/10 text-rose-600']">
            <CheckCircleIcon v-if="selectedStatus === 'accepted'" class="w-8 h-8" />
            <XCircleIcon v-else class="w-8 h-8" />
          </div>
          <div class="space-y-1">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Keputusan untuk</p>
            <h4 class="text-xl font-black text-slate-900 dark:text-white">{{ application.user.name }}</h4>
            <p class="text-sm text-slate-500 font-medium">Posisi: {{ application.internship.title }}</p>
          </div>
        </div>

        <div v-if="selectedStatus === 'rejected'" class="space-y-4">
          <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">Alasan Penolakan (Human-Friendly)</label>
          <div class="grid grid-cols-1 gap-2">
            <button 
              v-for="reason in rejectionReasons" 
              :key="reason.id"
              type="button"
              @click="statusForm.notes = reason.label"
              :class="['text-left px-5 py-3 rounded-xl text-sm font-medium transition-all border', statusForm.notes === reason.label ? 'bg-primary-50 border-primary-200 text-primary-700' : 'bg-white dark:bg-slate-800 border-slate-100 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:border-primary-200']"
            >
              {{ reason.label }}
            </button>
          </div>
        </div>

        <div class="space-y-4">
          <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">
            {{ selectedStatus === 'accepted' ? 'Catatan Onboarding / Pesan Selamat' : 'Catatan Tambahan (Internal)' }}
          </label>
          <textarea 
            v-model="statusForm.notes"
            rows="5"
            class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm focus:ring-primary-500 focus:border-primary-500 transition-all shadow-sm"
            :placeholder="selectedStatus === 'accepted' ? 'Selamat! Kamu terpilih...' : 'Tulis catatan tambahan di sini...'"
          ></textarea>
          <p class="text-[10px] text-slate-400 font-medium">
            <template v-if="selectedStatus === 'accepted'">Pesan ini akan ditampilkan kepada kandidat di dashboard mereka.</template>
            <template v-else>Pesan ini akan dikirimkan sebagai bagian dari email penolakan yang hangat.</template>
          </p>
        </div>

        <div class="flex justify-end items-center gap-4 pt-4">
          <button type="button" @click="showStatusModal = false" class="px-8 py-4 text-sm font-black text-slate-400 hover:text-slate-600 transition-colors">Batal</button>
          <Button type="submit" :variant="selectedStatus === 'rejected' ? 'danger' : 'success'" :loading="statusForm.processing" class="!px-12 !h-14 !rounded-2xl !text-sm !font-black shadow-xl">
            {{ selectedStatus === 'accepted' ? 'Terima Sekarang' : 'Konfirmasi Penolakan' }}
          </Button>
        </div>
      </form>
    </Modal>

    <Modal :show="showInterviewModal" @close="showInterviewModal = false" title="Jadwalkan Wawancara">
      <form @submit.prevent="submitInterview" class="p-8 space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <Input 
            v-model="interviewForm.scheduled_at"
            label="Tanggal & Waktu"
            type="datetime-local"
            required
          />
          <div>
            <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Tipe Wawancara</label>
            <select v-model="interviewForm.type" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm focus:ring-primary-500 focus:border-primary-500 transition-all h-[42px]">
              <option value="online">Online (Video Call)</option>
              <option value="offline">Offline (Tatap Muka)</option>
            </select>
          </div>
        </div>

        <div v-if="interviewForm.type === 'online'">
          <Input 
            v-model="interviewForm.meeting_link"
            label="Link Meeting"
            placeholder="https://meet.google.com/xxx-xxxx-xxx"
          />
        </div>
        <div v-else>
          <Input 
            v-model="interviewForm.location"
            label="Lokasi Kantor"
            placeholder="Gedung, Ruangan, atau Alamat"
          />
        </div>

        <div class="flex justify-end space-x-3">
          <Button type="button" variant="secondary" @click="showInterviewModal = false">Batal</Button>
          <Button type="submit" :loading="interviewForm.processing">Simpan Jadwal</Button>
        </div>
      </form>
    </Modal>

    <Modal :show="showMentorModal" @close="showMentorModal = false" title="Tugaskan Mentor">
      <form @submit.prevent="submitMentor" class="p-8 space-y-8">
        <div>
          <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Pilih Mentor Aktif</label>
          <select v-model="mentorForm.mentor_user_id" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm focus:ring-primary-500 focus:border-primary-500 transition-all h-[42px]">
            <option value="" disabled>Pilih mentor dari daftar...</option>
            <option v-for="mentor in mentors" :key="mentor.id" :value="mentor.user_id">
              {{ mentor.user.name }} ({{ mentor.user.role }})
            </option>
          </select>
          <div v-if="mentorForm.errors.mentor_user_id" class="mt-2 text-xs text-red-600">{{ mentorForm.errors.mentor_user_id }}</div>
        </div>

        <div class="flex justify-end space-x-3">
          <Button type="button" variant="secondary" @click="showMentorModal = false">Batal</Button>
          <Button type="submit" :loading="mentorForm.processing">Tugaskan</Button>
        </div>
      </form>
    </Modal>
    </template>
  </DashboardLayout>
</template>

