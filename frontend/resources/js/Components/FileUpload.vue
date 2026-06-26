<script setup lang="ts">
import { ref, watch } from 'vue';
import { Upload, X, FileText, CheckCircle2, AlertCircle, ExternalLink, FileType2 } from 'lucide-vue-next';
import { useLangStore } from '@/Stores/lang';

const langStore = useLangStore();
const t = (key: string) => langStore.t(key);

const props = defineProps<{
    label: string;
    accept?: string;
    maxSize?: number; // in MB
    currentFile?: string | null;
    modelValue?: File | null;
    error?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', file: File | null): void;
    (e: 'clear'): void;
}>();

const isDragging = ref(false);
const selectedFile = ref<File | null>(props.modelValue || null);
const error = ref<string | null>(null);

watch(() => props.modelValue, (newVal) => {
    selectedFile.value = newVal || null;
});

const handleFileChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    if (input.files && input.files.length > 0) {
        validateAndSelect(input.files[0]);
    }
};

const handleDrop = (e: DragEvent) => {
    isDragging.value = false;
    if (e.dataTransfer?.files && e.dataTransfer.files.length > 0) {
        validateAndSelect(e.dataTransfer.files[0]);
    }
};

const validateAndSelect = (file: File) => {
    error.value = null;
    
    // Generic validation based on 'accept' prop
    if (props.accept) {
        const acceptedTypes = props.accept.split(',').map(type => type.trim());
        const isAccepted = acceptedTypes.some(type => {
            if (type.startsWith('.')) {
                return file.name.toLowerCase().endsWith(type.toLowerCase());
            }
            return file.type === type;
        });

        if (!isAccepted) {
            error.value = `Tipe file tidak didukung. Harap gunakan: ${props.accept}`;
            return;
        }
    }

    if (props.maxSize && file.size > props.maxSize * 1024 * 1024) {
        error.value = `Ukuran file terlalu besar. Maksimal ${props.maxSize}MB.`;
        return;
    }

    selectedFile.value = file;
    emit('update:modelValue', file);
};

const clearFile = () => {
    selectedFile.value = null;
    error.value = null;
    emit('update:modelValue', null);
    emit('clear');
};
</script>

<template>
    <div class="space-y-4">
        <label v-if="label" class="text-sm font-bold text-slate-700 dark:text-slate-300 flex items-center gap-2">
            {{ label }}
            <span v-if="currentFile && !selectedFile" class="text-[10px] bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-2 py-0.5 rounded-full uppercase tracking-wider font-bold">Tersedia</span>
            <span v-if="selectedFile" class="text-[10px] bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-400 px-2 py-0.5 rounded-full uppercase tracking-wider font-bold">Baru</span>
        </label>

        <div 
            :class="[
                'relative border-2 border-dashed rounded-2xl p-8 transition-all duration-300 flex flex-col items-center justify-center gap-4 cursor-pointer group',
                isDragging ? 'border-primary-500 bg-primary-50/80 dark:bg-primary-900/20 scale-[1.02] shadow-[0_0_40px_rgba(8,112,184,0.2)] ring-4 ring-primary-500/20' : 'border-slate-200 dark:border-slate-700 hover:border-primary-300 dark:hover:border-primary-700 hover:bg-slate-50/50 dark:hover:bg-slate-800/30'
            ]"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop"
            @click="($refs.fileInput as HTMLInputElement).click()"
        >
            <input 
                ref="fileInput" 
                type="file" 
                class="hidden" 
                :accept="accept"
                @change="handleFileChange"
            />

            <!-- Empty State / Drop Zone -->
            <div v-if="!selectedFile" class="text-center w-full flex flex-col items-center">
                <div 
                    class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6 transition-all duration-500 shadow-sm"
                    :class="[isDragging ? 'bg-primary-500 shadow-lg shadow-primary-500/30 scale-110' : 'bg-white dark:bg-slate-800 group-hover:scale-105']"
                >
                    <Upload 
                        class="transition-all duration-500" 
                        :class="[isDragging ? 'w-10 h-10 text-white animate-bounce' : 'w-8 h-8 text-slate-400 group-hover:text-primary-500 group-hover:-translate-y-1']" 
                    />
                </div>
                
                <p class="text-base font-bold transition-colors" :class="isDragging ? 'text-primary-600 dark:text-primary-400' : 'text-slate-900 dark:text-white'">
                    {{ isDragging ? 'Lepaskan File Sekarang!' : 'Seret & Lepas Dokumen di Sini' }}
                </p>
                <p class="text-xs font-bold text-slate-500 dark:text-slate-400 mt-2 uppercase tracking-widest flex items-center justify-center gap-2">
                    <span>Atau</span>
                    <span class="text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 px-3 py-1 rounded-lg cursor-pointer hover:bg-primary-100 transition-colors">Cari File</span>
                </p>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-4 opacity-80">
                    {{ accept ? `Format Didukung: ${accept}` : 'Semua format didukung' }} 
                    <span v-if="maxSize" class="ml-1 px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded">Maks {{ maxSize }}MB</span>
                </p>
                
                <!-- Existing File Card -->
                <div v-if="currentFile" class="mt-8 w-full max-w-sm flex items-center gap-4 bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-sm relative overflow-hidden group/file hover:border-primary-300 transition-colors cursor-default" @click.stop>
                    <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-green-500"></div>
                    <div class="w-12 h-12 bg-green-50 dark:bg-green-900/20 rounded-xl flex items-center justify-center shrink-0 shadow-inner">
                        <CheckCircle2 class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div class="flex-1 min-w-0 text-left">
                        <p class="text-sm font-bold text-slate-900 dark:text-white truncate">Dokumen Tersimpan</p>
                        <a :href="currentFile" target="_blank" class="text-[10px] text-primary-600 hover:text-primary-700 dark:text-primary-400 font-bold uppercase tracking-widest flex items-center gap-1 mt-1 z-10 w-max hover:underline bg-primary-50 dark:bg-primary-900/20 px-2 py-0.5 rounded">
                            Lihat Dokumen <ExternalLink class="w-3 h-3" />
                        </a>
                    </div>
                </div>
            </div>

            <!-- Selected File State -->
            <div v-else class="w-full flex items-center gap-5 bg-white dark:bg-slate-800 p-6 rounded-2xl border border-primary-200 dark:border-primary-900/50 shadow-xl shadow-primary-500/5 relative overflow-hidden group/card cursor-default" @click.stop>
                <!-- Decorative background elements -->
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-primary-50 dark:bg-primary-900/20 rounded-full blur-2xl opacity-50"></div>
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-primary-500"></div>
                
                <div class="w-14 h-14 bg-gradient-to-br from-primary-500 to-primary-600 rounded-2xl flex items-center justify-center shrink-0 shadow-lg shadow-primary-500/30 transform group-hover/card:scale-110 group-hover/card:rotate-3 transition-transform duration-300">
                    <FileType2 class="w-7 h-7 text-white" />
                </div>
                <div class="flex-1 min-w-0 text-left relative z-10">
                    <p class="text-sm font-bold text-slate-900 dark:text-white truncate" :title="selectedFile.name">{{ selectedFile.name }}</p>
                    <div class="flex items-center gap-3 mt-1.5">
                        <p class="text-[10px] font-mono font-bold text-slate-500 bg-slate-100 dark:bg-slate-700 px-2 py-0.5 rounded uppercase tracking-wider">
                            {{ (selectedFile.size / 1024).toFixed(1) }} KB
                        </p>
                        <p class="text-[10px] font-bold text-green-600 dark:text-green-400 flex items-center gap-1">
                            <CheckCircle2 class="w-3 h-3" /> Siap diunggah
                        </p>
                    </div>
                </div>
                <button 
                    class="p-3 bg-red-50 hover:bg-red-500 dark:bg-red-900/20 text-red-500 hover:text-white rounded-xl transition-all duration-300 shadow-sm hover:shadow-red-500/30 relative z-10 group/btn"
                    @click.stop="clearFile"
                    title="Hapus file"
                >
                    <X class="w-5 h-5 group-hover/btn:rotate-90 transition-transform duration-300" />
                </button>
            </div>
        </div>

        <div v-if="error" class="flex items-center gap-3 p-4 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-2xl text-xs font-bold border border-red-200 dark:border-red-900/30 shadow-sm animate-shake">
            <AlertCircle class="w-5 h-5 shrink-0" />
            {{ error }}
        </div>
    </div>
</template>

<style scoped>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    75% { transform: translateX(4px); }
}
.animate-shake {
    animation: shake 0.4s ease-in-out;
}
</style>
