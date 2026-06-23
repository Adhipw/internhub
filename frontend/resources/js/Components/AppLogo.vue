<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    variant?: 'mark' | 'full' | 'horizontal' | 'compact';
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    showText?: boolean;
    className?: string;
    isDarkMode?: boolean;
    roleLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'full',
    size: 'md',
    showText: true,
    className: '',
    isDarkMode: false,
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs': return { icon: 'w-5 h-5', text: 'text-sm', gap: 'gap-1.5' };
        case 'sm': return { icon: 'w-7 h-7', text: 'text-base', gap: 'gap-2' };
        case 'md': return { icon: 'w-9 h-9', text: 'text-xl', gap: 'gap-2.5' };
        case 'lg': return { icon: 'w-12 h-12', text: 'text-3xl', gap: 'gap-3' };
        case 'xl': return { icon: 'w-20 h-20', text: 'text-5xl', gap: 'gap-5' };
        default: return { icon: 'w-9 h-9', text: 'text-xl', gap: 'gap-2.5' };
    }
});

const navy = computed(() => props.isDarkMode ? '#F8FAFC' : '#0a2540'); // Navy or White
const cobalt = computed(() => props.isDarkMode ? '#60A5FA' : '#0056B3'); // Cobalt or Light Blue

</script>

<template>
    <div 
        :class="[
            'flex items-center transition-all duration-300',
            sizeClasses.gap,
            className
        ]"
    >
        <!-- Professional Connection Mark -->
        <div 
            :class="[
                sizeClasses.icon, 
                'flex items-center justify-center shrink-0'
            ]"
        >
            <svg viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                <!-- Main Hub Node -->
                <circle cx="35" cy="65" r="22" :fill="navy" />
                
                <!-- Connection Path (Minimalist Bridge) -->
                <path 
                    d="M35 65 L75 25" 
                    :stroke="cobalt" 
                    stroke-width="12" 
                    stroke-linecap="round" 
                />
                
                <!-- Target Node (Growth/Future) -->
                <circle cx="75" cy="25" r="14" :fill="cobalt" />
                
                <!-- Subtle internal hub detail -->
                <circle cx="35" cy="65" r="8" fill="white" opacity="0.2" />
            </svg>
        </div>

        <!-- Wordmark: InternHub -->
        <div v-if="variant !== 'mark' && showText" class="flex items-center">
            <h1 
                :class="[
                    sizeClasses.text, 
                    'font-black tracking-tight flex items-center'
                ]"
            >
                <span :class="isDarkMode ? 'text-white' : 'text-slate-950'">Intern</span>
                <span :class="isDarkMode ? 'text-blue-400' : 'text-blue-600'">Hub</span>
            </h1>
            
            <!-- Professional Role Tag (Subtle & Modern) -->
            <div 
                v-if="roleLabel"
                class="ml-3 px-2.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-sm"
            >
                <span 
                    class="text-[9px] font-black uppercase tracking-widest text-slate-500 dark:text-slate-400"
                >
                    {{ roleLabel }}
                </span>
            </div>
        </div>
    </div>
</template>
