<script setup lang="ts">
import logger from '@/Lib/logger';
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useLangStore } from '@/Stores/lang';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Card from '@/Components/Card.vue';
import Input from '@/Components/Input.vue';
import Button from '@/Components/Button.vue';
import { ArrowLeftIcon, MapPinIcon } from '@heroicons/vue/24/outline';

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const props = defineProps<{
    industries: any[];
}>();

const form = useForm({
  title: '',
  description: '',
  requirements: '',
  benefits: '',
  type: 'Office',
  location: '',
  industry_id: '',
  latitude: undefined as number | undefined,
  longitude: undefined as number | undefined,
  is_paid: false,
  stipend: '',
  deadline_at: '',
  status: 'published',
});

const showCoordinates = ref(false);
const detecting = ref(false);

const detectLocation = () => {
  if (!navigator.geolocation) {
    alert('Browser Anda tidak mendukung deteksi lokasi.');
    return;
  }
  
  detecting.value = true;
  
  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const { latitude, longitude } = position.coords;
      form.latitude = latitude;
      form.longitude = longitude;
      showCoordinates.value = true;
      
      // Reverse Geocoding using Nominatim (Free)
      try {
        const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`);
        const data = await response.json();
        if (data.display_name) {
          form.location = data.display_name;
        }
      } catch (err) {
        logger.error('Reverse geocoding failed:', err);
      } finally {
        detecting.value = false;
        alert('Lokasi dan alamat berhasil ditemukan!');
      }
    },
    (error) => {
      detecting.value = false;
      alert('Gagal mendeteksi lokasi. Pastikan izin lokasi aktif dan sinyal stabil.');
    },
    { enableHighAccuracy: false, timeout: 5000, maximumAge: 0 } // Faster detection
  );
};

const submit = () => {
  // Ensure date is in YYYY-MM-DD format to avoid year 12026 bugs
  if (form.deadline_at) {
    try {
      const d = new Date(form.deadline_at);
      if (!isNaN(d.getTime())) {
        form.deadline_at = d.toISOString().split('T')[0];
      }
    } catch (e) {
      logger.error('Date formatting failed:', e);
    }
  }

  form.post('/hr/internships', {
      onError: (errors: any) => {
          logger.error('Failed to create internship:', errors);
      }
  });
};
</script>

<template>
  <DashboardLayout>
    <template #header>
      <div class="flex items-center">
        <Link href="/hr/internships" class="mr-6 p-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl transition-all">
          <ArrowLeftIcon class="w-5 h-5" />
        </Link>
        <div>
          <h2 class="text-2xl font-display font-bold text-slate-900 dark:text-white tracking-tight">{{ t('hr.internships.create_title') }}</h2>
          <p class="text-sm text-slate-500 font-medium">{{ t('hr.internships.create_desc') }}</p>
        </div>
      </div>
    </template>

    <div class="mt-10 max-w-4xl">
      <form class="space-y-10" @submit.prevent="submit">
        <!-- Section: Basic Info -->
        <section class="space-y-6">
          <div class="pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">{{ t('hr.internships.basic_info') }}</h3>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <Input 
                  v-model="form.title"
                  :label="t('hr.internships.job_title')"
                  :placeholder="t('hr.internships.title_placeholder')"
                  required
                  :error="form.errors.title"
                />
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Bidang Industri</label>
              <select v-model="form.industry_id" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm focus:ring-primary-500 focus:border-primary-500 h-[48px] transition-all px-4 font-semibold" required>
                <option value="">Pilih Industri</option>
                <option v-for="industry in industries" :key="industry.id" :value="industry.id">
                  {{ industry.name }}
                </option>
              </select>
              <div v-if="form.errors.industry_id" class="mt-1 text-[11px] font-bold text-red-500 ml-1">{{ Array.isArray(form.errors.industry_id) ? form.errors.industry_id[0] : form.errors.industry_id }}</div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Tipe Magang</label>
              <select v-model="form.type" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm focus:ring-primary-500 focus:border-primary-500 h-[48px] transition-all px-4 font-semibold">
                <option value="Office">Office (WFO)</option>
                <option value="WFH">Remote (WFH)</option>
                <option value="Hybrid">Hybrid</option>
              </select>
            </div>
            
            <div class="md:col-span-2 grid grid-cols-1 gap-6">
              <div class="space-y-4">
                <div class="flex items-center justify-between">
                  <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest">{{ t('common.location') }}</label>
                  <button 
                    type="button"
                    :disabled="detecting"
                    class="text-[10px] font-bold text-primary-600 uppercase tracking-widest flex items-center gap-1.5 hover:text-primary-700 transition-colors disabled:opacity-50"
                    @click="detectLocation"
                  >
                    <MapPinIcon v-if="!detecting" class="w-3.5 h-3.5" />
                    <span v-else class="w-3.5 h-3.5 border-2 border-primary-600 border-t-transparent rounded-full animate-spin"></span>
                    {{ detecting ? 'Mencari Alamat...' : 'Deteksi Lokasi Saya' }}
                  </button>
                </div>
                <Input 
                  v-model="form.location"
                  placeholder="Contoh: Menara Mandiri, Jl. Jend. Sudirman Kav. 54-55, Jakarta"
                  required
                  :error="form.errors.location"
                />
              </div>

              <!-- Advanced: Coordinates Toggle -->
              <div class="pt-2">
                <button 
                  type="button" 
                  class="text-[10px] font-bold text-slate-400 uppercase tracking-widest flex items-center gap-2 hover:text-slate-600 transition-colors"
                  @click="showCoordinates = !showCoordinates"
                >
                  <span>{{ showCoordinates ? '− Sembunyikan Koordinat' : '+ Tambahkan Koordinat Map (Opsional)' }}</span>
                </button>
                
                <div v-if="showCoordinates" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6 animate-fade-in">
                  <Input 
                    v-model="form.latitude"
                    label="Latitude"
                    placeholder="-6.123456"
                    type="number"
                    step="any"
                    :error="form.errors.latitude"
                  />
                  <Input 
                    v-model="form.longitude"
                    label="Longitude"
                    placeholder="106.123456"
                    type="number"
                    step="any"
                    :error="form.errors.longitude"
                  />
                  <p class="col-span-full text-[10px] text-slate-400 italic">
                    Koordinat membantu mahasiswa menemukan lowongan terdekat dari lokasi mereka. 
                    <a href="https://www.google.com/maps" target="_blank" class="text-primary-600 underline">Cari di Google Maps</a>
                  </p>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- Section: Content -->
        <section class="space-y-6">
          <div class="pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">{{ t('internships.job_desc') }} & {{ t('hr.internships.requirements') }}</h3>
          </div>
          
          <div class="space-y-6">
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">{{ t('hr.internships.about_role') }}</label>
              <textarea 
                v-model="form.description"
                rows="6"
                class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm focus:ring-primary-500 focus:border-primary-500 transition-all p-4"
                :placeholder="t('hr.internships.desc_placeholder')"
                required
              ></textarea>
              <div v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">{{ t('internships.main_qualifications') }}</label>
                <textarea 
                  v-model="form.requirements"
                  rows="4"
                  class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm focus:ring-primary-500 focus:border-primary-500 transition-all p-4"
                  :placeholder="t('hr.internships.req_placeholder')"
                ></textarea>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">{{ t('hr.internships.benefits') }}</label>
                <textarea 
                  v-model="form.benefits"
                  rows="4"
                  class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm focus:ring-primary-500 focus:border-primary-500 transition-all p-4"
                  :placeholder="t('hr.internships.benefits_placeholder')"
                ></textarea>
              </div>
            </div>
          </div>
        </section>

        <!-- Section: Settings -->
        <section class="space-y-6">
          <div class="pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">{{ t('common.settings') }} & {{ t('internships.deadline') }}</h3>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-start">
            <div class="space-y-2">
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Benefit Gaji/Uang Saku</label>
              <label class="flex items-center gap-3 cursor-pointer group p-3 bg-slate-50 dark:bg-slate-900 rounded-xl border border-slate-100 dark:border-slate-800">
                <input v-model="form.is_paid" type="checkbox" class="w-5 h-5 rounded border-slate-300 text-primary-600 focus:ring-primary-500" />
                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">Magang Berbayar</span>
              </label>
            </div>
            
            <Input 
              v-if="form.is_paid"
              v-model="form.stipend"
              label="Besaran Uang Saku (Per Bulan)"
              placeholder="Contoh: Rp 2.000.000 atau Kompetitif"
              :error="form.errors.stipend"
            />
            
            <Input 
              v-model="form.deadline_at"
              :label="t('internships.deadline')"
              type="date"
              required
              :error="form.errors.deadline_at"
            />
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">{{ t('hr.internships.pub_status') }}</label>
              <select v-model="form.status" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm focus:ring-primary-500 focus:border-primary-500 h-[42px] transition-all">
                <option value="published">{{ t('hr.internships.pub_now') }}</option>
                <option value="draft">{{ t('common.save_as_draft') }}</option>
              </select>
            </div>
          </div>
        </section>

        <div class="pt-6 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end space-x-4">
          <Link href="/hr/internships" class="text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">{{ t('common.cancel') }}</Link>
          <Button type="submit" :loading="form.processing" class="px-10">
            {{ t('hr.internships.save_internship') }}
          </Button>
        </div>
      </form>
    </div>
  </DashboardLayout>
</template>

