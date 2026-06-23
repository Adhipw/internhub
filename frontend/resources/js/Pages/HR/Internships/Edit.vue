<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Head } from '@/Components';

import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import Input from '@/Components/Input.vue';
import Button from '@/Components/Button.vue';
import { route } from 'ziggy-js';
import { ArrowLeftIcon } from '@heroicons/vue/24/outline';

interface Props {
  internship: any;
}

const props = defineProps<Props>();

const form = useForm({
  title: props.internship.title,
  description: props.internship.description,
  requirements: props.internship.requirements ? (Array.isArray(props.internship.requirements) ? props.internship.requirements.join('\n') : props.internship.requirements) : '',
  benefits: props.internship.benefits,
  type: props.internship.type,
  location: props.internship.location,
  salary_range: props.internship.salary_range,
  deadline_at: props.internship.deadline_at,
  status: props.internship.status,
});

const submit = () => {
  form.put(route('hr.internships.update', props.internship.id));
};
</script>

<template>
  <Head :title="`Edit: ${internship.title}`" />

  <DashboardLayout>
    <template #header>
      <div class="flex items-center">
        <Link :href="route('hr.internships.index')" class="mr-6 p-2.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 bg-slate-50 dark:bg-slate-900 border border-slate-100 dark:border-slate-800 rounded-xl transition-all">
          <ArrowLeftIcon class="w-5 h-5" />
        </Link>
        <div>
          <h2 class="text-2xl font-display font-bold text-slate-900 dark:text-white tracking-tight">Edit Lowongan</h2>
          <p class="text-sm text-slate-500 font-medium">Perbarui informasi lowongan magang Anda.</p>
        </div>
      </div>
    </template>

    <div class="mt-10 max-w-4xl">
      <form @submit.prevent="submit" class="space-y-10">
        <!-- Section: Basic Info -->
        <section class="space-y-6">
          <div class="pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">Informasi Dasar</h3>
          </div>
          
          <div class="grid grid-cols-1 gap-6">
            <Input 
              v-model="form.title"
              label="Judul Posisi"
              required
              :error="form.errors.title"
            />
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Tipe Magang</label>
                <select v-model="form.type" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm focus:ring-primary-500 focus:border-primary-500 h-[42px] transition-all">
                  <option value="Office">Office (WFO)</option>
                  <option value="WFH">Remote (WFH)</option>
                  <option value="Hybrid">Hybrid</option>
                </select>
                <div v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</div>
              </div>
              <Input 
                v-model="form.location"
                label="Lokasi Kantor"
                required
                :error="form.errors.location"
              />
            </div>
          </div>
        </section>

        <!-- Section: Content -->
        <section class="space-y-6">
          <div class="pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">Deskripsi & Kebutuhan</h3>
          </div>
          
          <div class="space-y-6">
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Tentang Peran Ini</label>
              <textarea 
                v-model="form.description"
                rows="6"
                class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm focus:ring-primary-500 focus:border-primary-500 transition-all p-4"
                required
              ></textarea>
              <div v-if="form.errors.description" class="mt-1 text-xs text-red-600">{{ form.errors.description }}</div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Kualifikasi</label>
                <textarea 
                  v-model="form.requirements"
                  rows="4"
                  class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm focus:ring-primary-500 focus:border-primary-500 transition-all p-4"
                ></textarea>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Benefit & Fasilitas</label>
                <textarea 
                  v-model="form.benefits"
                  rows="4"
                  class="w-full rounded-2xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm focus:ring-primary-500 focus:border-primary-500 transition-all p-4"
                ></textarea>
              </div>
            </div>
          </div>
        </section>

        <!-- Section: Settings -->
        <section class="space-y-6">
          <div class="pb-2 border-b border-slate-100 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-widest">Pengaturan & Batas Waktu</h3>
          </div>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Input 
              v-model="form.salary_range"
              label="Estimasi Uang Saku"
              :error="form.errors.salary_range"
            />
            <Input 
              v-model="form.deadline_at"
              label="Batas Akhir Pendaftaran"
              type="date"
              required
              :error="form.errors.deadline_at"
            />
            <div>
              <label class="block text-xs font-bold text-slate-400 uppercase tracking-widest mb-2">Status Publikasi</label>
              <select v-model="form.status" class="w-full rounded-xl border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm focus:ring-primary-500 focus:border-primary-500 h-[42px] transition-all">
                <option value="published">Aktif / Publik</option>
                <option value="draft">Draft</option>
                <option value="closed">Ditutup</option>
              </select>
              <div v-if="form.errors.status" class="mt-1 text-xs text-red-600">{{ form.errors.status }}</div>
            </div>
          </div>
        </section>

        <div class="pt-6 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end space-x-4">
          <Link :href="route('hr.internships.index')" class="text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">Batal</Link>
          <Button type="submit" :loading="form.processing" class="px-10">
            Simpan Perubahan
          </Button>
        </div>
      </form>
    </div>
  </DashboardLayout>
</template>
