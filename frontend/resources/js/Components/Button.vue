<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    variant?: 'primary' | 'secondary' | 'outline' | 'ghost' | 'danger' | 'accent' | 'success' | 'warning';
    size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl';
    loading?: boolean;
    disabled?: boolean;
    type?: 'button' | 'submit' | 'reset';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    size: 'md',
    loading: false,
    disabled: false,
    type: 'button',
    class: '',
});

const baseStyles = "inline-flex items-center justify-center font-bold tracking-tight transition-all duration-300 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed disabled:active:scale-100";

const variants = {
    primary: "bg-primary-600 text-white hover:bg-primary-700 shadow-sm border border-transparent hover:border-primary-800",
    secondary: "bg-neutral-100 text-neutral-900 hover:bg-neutral-200 dark:bg-neutral-800 dark:text-white dark:hover:bg-neutral-700 border border-transparent",
    outline: "bg-transparent border border-neutral-200 text-neutral-900 hover:border-neutral-300 hover:bg-neutral-50 dark:border-neutral-800 dark:text-white dark:hover:border-neutral-700 dark:hover:bg-neutral-800/50",
    ghost: "bg-transparent text-neutral-600 hover:bg-neutral-100 dark:text-neutral-400 dark:hover:bg-neutral-800",
    danger: "bg-rose-600 text-white hover:bg-rose-700 shadow-sm border border-transparent hover:border-rose-800",
    accent: "bg-accent-500 text-white hover:bg-accent-600 shadow-sm border border-transparent hover:border-accent-700",
    success: "bg-emerald-600 text-white hover:bg-emerald-700 shadow-sm border border-transparent hover:border-emerald-800",
    warning: "bg-amber-500 text-white hover:bg-amber-600 shadow-sm border border-transparent hover:border-amber-700",
};

const sizes = {
    xs: "px-3 py-1.5 text-[10px] font-medium rounded-lg",
    sm: "px-4 py-2 text-xs rounded-xl",
    md: "px-6 py-3 text-sm rounded-2xl",
    lg: "px-8 py-4 text-base rounded-2xl",
    xl: "px-10 py-5 text-lg rounded-[1.5rem]",
};

const classes = computed(() => {
    return [
        baseStyles,
        variants[props.variant],
        sizes[props.size],
        props.class
    ].join(' ');
});
</script>

<template>
    <button :type="type" :class="classes" :disabled="disabled || loading">
        <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <slot />
    </button>
</template>
