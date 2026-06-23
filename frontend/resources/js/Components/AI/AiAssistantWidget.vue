<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import api from '@/Services/api';
import AiPanel from './AiPanel.vue';
import { 
    Sparkles, 
    X,
    UserCircle,
    FileText,
    Star,
    Edit3,
    MessageSquare,
    ArrowLeft
} from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const isOpen = ref(false);
const activeFeature = ref(null);
const isLoading = ref(false);
const result = ref('');
const error = ref('');

// Support/Chat State
const userQuestion = ref('');

// CV Specific State
const cvText = ref('');
const hasConsent = ref(false);

const features = computed(() => {
    const base = [
        { id: 'support', name: 'Support AI', icon: MessageSquare, action: 'chat', endpoint: '/ai/public/faq', method: 'post' },
    ];

    if (user.value) {
        return [
            ...base,
            { id: 'profile', name: 'Review Profile', icon: UserCircle, action: 'review-profile', endpoint: '/ai/review-profile', method: 'post' },
            { id: 'cv', name: 'Summarize CV', icon: FileText, action: 'summarize-cv', endpoint: '/ai/summarize-cv', method: 'post' },
            { id: 'recommend', name: 'Recommendations', icon: Star, action: 'recommendations', endpoint: '/ai/recommendations', method: 'get' },
            { id: 'letter', name: 'Draft Cover Letter', icon: Edit3, action: 'draft-letter', endpoint: '/ai/draft-letter', method: 'post' },
            { id: 'interview', name: 'Interview Prep', icon: MessageSquare, action: 'interview-prep', endpoint: '/ai/interview-prep', method: 'post' },
        ];
    }

    return base;
});

const togglePanel = () => {
    isOpen.value = !isOpen.value;
    if (!isOpen.value) {
        activeFeature.value = null;
        result.value = '';
        error.value = '';
        userQuestion.value = '';
    }
};

const selectFeature = (feature) => {
    activeFeature.value = feature;
    result.value = '';
    error.value = '';
    
    // Auto-run for simple GET features
    if (feature.method === 'get') {
        runFeature();
    }
};

const runFeature = async (payload = {}) => {
    isLoading.value = true;
    error.value = '';
    result.value = '';

    try {
        let response;
        if (activeFeature.value.method === 'post') {
            // Specialized payload for CV
            if (activeFeature.value.id === 'cv') {
                payload = { cv_text: cvText.value, consent: hasConsent.value };
            } else if (activeFeature.value.id === 'support') {
                payload = { question: userQuestion.value };
            }
            response = await api.post(activeFeature.value.endpoint, payload);
        } else {
            response = await api.get(activeFeature.value.endpoint);
        }
        
        // Extract content based on endpoint response structure
        result.value = response.data.answer || response.data.tips || response.data.summary || response.data.recommendations || response.data.draft || response.data.prep_material;
    } catch (err) {
        error.value = err.response?.data?.error || err.response?.data?.message || 'AI request failed.';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="fixed bottom-6 right-6 z-50">
        <!-- Floating Button -->
        <button 
            class="bg-indigo-600 hover:bg-indigo-700 text-white p-4 rounded-full shadow-2xl transition-all hover:scale-110 active:scale-95 group"
            @click="togglePanel"
        >
            <Sparkles v-if="!isOpen" class="w-6 h-6" />
            <X v-else class="w-6 h-6" />
            
            <span class="absolute right-full mr-3 bg-gray-900 text-white px-2 py-1 rounded text-xs whitespace-nowrap opacity-0 group-hover:opacity-100 transition-opacity">
                AI Career Assistant
            </span>
        </button>

        <!-- AI Panel Integration -->
        <AiPanel 
            :is-open="isOpen" 
            :loading="isLoading"
            title="AI Career Assistant"
            @close="togglePanel"
        >
            <!-- Feature Selection List -->
            <div v-if="!activeFeature" class="grid grid-cols-1 gap-3">
                <p class="text-sm text-gray-500 mb-2">Select a tool to get started:</p>
                <button 
                    v-for="feature in features" 
                    :key="feature.id"
                    class="flex items-center p-3 rounded-xl border border-gray-100 hover:border-indigo-200 hover:bg-indigo-50 transition-all text-left group"
                    @click="selectFeature(feature)"
                >
                    <div class="bg-gray-50 group-hover:bg-white p-2 rounded-lg shadow-sm mr-3 transition-colors">
                        <component :is="feature.icon" class="w-5 h-5 text-indigo-600" />
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-gray-900">{{ feature.name }}</div>
                        <div class="text-[10px] text-gray-400 uppercase tracking-wider">AI Tool</div>
                    </div>
                </button>
            </div>

            <!-- Active Feature UI -->
            <div v-else class="space-y-6">
                <button class="text-xs font-bold text-indigo-600 flex items-center hover:underline" @click="activeFeature = null">
                    <ArrowLeft class="w-3 h-3 mr-1" /> Back to Tools
                </button>

                <div class="flex items-center space-x-3 pb-4 border-b border-gray-50">
                    <component :is="activeFeature.icon" class="w-6 h-6 text-indigo-600" />
                    <h4 class="font-bold text-gray-900">{{ activeFeature.name }}</h4>
                </div>

                <!-- Support AI Specific Input -->
                <div v-if="activeFeature.id === 'support'" class="space-y-4">
                    <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                        <p class="text-xs text-indigo-700 leading-relaxed font-medium">
                            Tanyakan apa saja tentang InternHub, bantuan teknis, atau panduan magang.
                        </p>
                    </div>
                    <textarea 
                        v-model="userQuestion"
                        placeholder="Ketik pertanyaan Anda di sini..."
                        class="w-full rounded-xl border-gray-200 text-sm h-32 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                    ></textarea>
                    
                    <button 
                        :disabled="!userQuestion || isLoading"
                        class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 disabled:opacity-50 transition-all shadow-lg shadow-indigo-100"
                        @click="runFeature"
                    >
                        Tanya AI
                    </button>
                </div>

                <!-- CV Summary Specific Input -->
                <div v-if="activeFeature.id === 'cv'" class="space-y-4">
                    <textarea 
                        v-model="cvText"
                        placeholder="Paste your CV text here..."
                        class="w-full rounded-xl border-gray-200 text-sm h-40 focus:ring-indigo-500 focus:border-indigo-500"
                    ></textarea>
                    
                    <label class="flex items-start space-x-3 cursor-pointer group">
                        <input v-model="hasConsent" type="checkbox" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        <span class="text-xs text-gray-500 leading-relaxed group-hover:text-gray-700">
                            I consent to processing my CV data through an external AI provider (Google Gemini) for analysis.
                        </span>
                    </label>

                    <button 
                        :disabled="!cvText || !hasConsent || isLoading"
                        class="w-full bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 disabled:opacity-50 transition-all shadow-lg shadow-indigo-100"
                        @click="runFeature"
                    >
                        Analyze CV
                    </button>
                </div>

                <!-- Generic Trigger for other POST features -->
                <div v-else-if="activeFeature.method === 'post' && activeFeature.id !== 'cv' && activeFeature.id !== 'support'" class="text-center py-4">
                    <button 
                        :disabled="isLoading"
                        class="px-8 bg-indigo-600 text-white py-3 rounded-xl font-bold hover:bg-indigo-700 disabled:opacity-50 transition-all shadow-lg shadow-indigo-100"
                        @click="runFeature"
                    >
                        Run Analysis
                    </button>
                </div>

                <!-- Results -->
                <div v-if="error" class="p-3 bg-red-50 text-red-700 text-xs rounded-lg border border-red-100">
                    {{ error }}
                </div>

                <div v-if="result" class="bg-indigo-50/50 rounded-2xl p-4 border border-indigo-100 animate-in fade-in slide-in-from-bottom-2 duration-300">
                    <div class="flex items-center space-x-2 mb-3">
                        <Sparkles class="w-4 h-4 text-indigo-600" />
                        <span class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest">AI Result</span>
                    </div>
                    <div class="prose prose-sm text-gray-700 whitespace-pre-wrap leading-relaxed">
                        {{ result }}
                    </div>
                </div>
            </div>
        </AiPanel>
    </div>
</template>

<style scoped>
.shadow-2xl {
    box-shadow: 0 25px 50px -12px rgba(79, 70, 229, 0.25);
}
</style>
