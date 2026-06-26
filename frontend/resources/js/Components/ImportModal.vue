<script setup lang="ts">
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import Button from '@/Components/Button.vue';
import FileUpload from '@/Components/FileUpload.vue';
import { 
    Download, CheckCircle2, AlertCircle, 
    XCircle, Info, Loader2, ArrowRight
} from 'lucide-vue-next';
import { useLangStore } from '@/Stores/lang';
import api from '@/Services/api';

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const props = defineProps<{
    show: boolean;
    title: string;
    endpoint: string;
    templateUrl?: string;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'success'): void;
}>();

const selectedFile = ref<File | null>(null);
const loading = ref(false);
const result = ref<any>(null);
const error = ref<string | null>(null);

const handleFileSelected = (file: File) => {
    selectedFile.value = file;
    error.value = null;
    result.value = null;
};

const handleImport = async () => {
    if (!selectedFile.value) return;

    loading.value = true;
    error.value = null;
    
    const formData = new FormData();
    formData.append('file', selectedFile.value);

    try {
        const response = await api.post(props.endpoint, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        result.value = response.data.data;
    } catch (err: any) {
        error.value = err.response?.data?.message || 'Import failed. Please check the file format.';
    } finally {
        loading.value = false;
    }
};

const close = () => {
    selectedFile.value = null;
    result.value = null;
    error.value = null;
    emit('close');
};
</script>

<template>
    <Modal :show="show" max-width="md" @close="close">
        <div class="p-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white">{{ title }}</h3>
                    <p class="text-sm text-slate-500">{{ t('import.subtitle') }}</p>
                </div>
                <button class="p-2 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors" @click="close">
                    <XCircle class="w-6 h-6 text-slate-400" />
                </button>
            </div>

            <div v-if="!result" class="space-y-6">
                <div v-if="templateUrl" class="bg-primary-50 dark:bg-primary-900/10 p-4 rounded-xl border border-primary-100 dark:border-primary-900/20">
                    <div class="flex items-start gap-3">
                        <Info class="w-5 h-5 text-primary-600 shrink-0 mt-0.5" />
                        <div>
                            <p class="text-xs font-bold text-primary-900 dark:text-primary-100 mb-2">{{ t('import.template_hint') }}</p>
                            <a 
                                :href="templateUrl" 
                                class="inline-flex items-center gap-2 text-xs font-bold text-primary-600 hover:text-primary-700 uppercase tracking-widest"
                                target="_blank"
                            >
                                <Download class="w-3.5 h-3.5" />
                                {{ t('import.download_template') }}
                            </a>
                        </div>
                    </div>
                </div>

                <FileUpload label="Pilih File CSV" accept=".csv" :max-size="2" @file-selected="handleFileSelected" @clear="selectedFile = null" />

                <div v-if="error" class="flex items-center gap-2 p-4 bg-red-50 dark:bg-red-900/10 text-red-600 rounded-xl text-xs font-bold border border-red-100 dark:border-red-900/20">
                    <AlertCircle class="w-5 h-5 shrink-0" />
                    {{ error }}
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <Button variant="ghost" :disabled="loading" @click="close">{{ t('common.cancel') }}</Button>
                    <Button 
                        variant="primary" 
                        :disabled="!selectedFile || loading" 
                        class="px-8 shadow-lg shadow-primary-500/20"
                        @click="handleImport"
                    >
                        <template v-if="loading">
                            <Loader2 class="w-4 h-4 mr-2 animate-spin" />
                            {{ t('import.processing') }}
                        </template>
                        <template v-else>
                            {{ t('import.start') }}
                            <ArrowRight class="w-4 h-4 ml-2" />
                        </template>
                    </Button>
                </div>
            </div>

            <div v-else class="space-y-6 text-center py-4">
                <div class="w-20 h-20 bg-emerald-50 dark:bg-emerald-900/20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <CheckCircle2 class="w-10 h-10 text-emerald-500" />
                </div>
                
                <h4 class="text-2xl font-bold text-slate-900 dark:text-white">{{ t('import.complete') }}</h4>
                
                <div class="grid grid-cols-2 gap-4 mt-8">
                    <div class="bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <p class="text-2xl font-bold text-slate-900 dark:text-white">{{ result.imported }}</p>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ t('import.success_count') }}</p>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800">
                        <p class="text-2xl font-bold" :class="result.errors.length > 0 ? 'text-red-500' : 'text-slate-400'">{{ result.errors.length }}</p>
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ t('import.error_count') }}</p>
                    </div>
                </div>

                <div v-if="result.errors.length > 0" class="mt-6 text-left max-h-48 overflow-y-auto bg-red-50/50 dark:bg-red-900/10 p-4 rounded-xl border border-red-100 dark:border-red-900/20">
                    <p class="text-[10px] font-bold text-red-600 uppercase tracking-widest mb-3">{{ t('import.error_details') }}</p>
                    <ul class="space-y-2">
                        <li v-for="(err, idx) in result.errors" :key="idx" class="text-[11px] text-slate-600 dark:text-slate-400 leading-tight">
                            <span class="font-bold text-red-600">Row {{ err.row }}:</span> {{ err.messages.join(', ') }}
                        </li>
                    </ul>
                </div>

                <div class="pt-6">
                    <Button variant="primary" class="w-full" @click="close">{{ t('common.done') }}</Button>
                </div>
            </div>
        </div>
    </Modal>
</template>
