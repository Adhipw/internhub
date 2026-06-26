<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    variant?: 'primary' | 'secondary' | 'accent' | 'success' | 'warning' | 'danger' | 'info' | 'default' | 'error' | 'outline';
    size?: 'xs' | 'sm' | 'md' | 'lg';
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    variant: 'primary',
    size: 'md',
    class: '',
});

const variants = {
    primary: "bg-primary-50 text-primary-700 border-primary-100 dark:bg-primary-900/20 dark:text-primary-400 dark:border-primary-800/50",
    secondary: "bg-neutral-100 text-neutral-700 border-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:border-neutral-700",
    accent: "bg-accent-50 text-accent-700 border-accent-100 dark:bg-accent-900/20 dark:text-accent-400 dark:border-accent-800/50",
    success: "bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-800/50",
    warning: "bg-amber-50 text-amber-700 border-amber-100 dark:bg-amber-900/20 dark:text-amber-400 dark:border-amber-800/50",
    danger: "bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-800/50",
    info: "bg-sky-50 text-sky-700 border-sky-100 dark:bg-sky-900/20 dark:text-sky-400 dark:border-sky-800/50",
    default: "bg-neutral-100 text-neutral-700 border-neutral-200 dark:bg-neutral-800 dark:text-neutral-300 dark:border-neutral-700",
    error: "bg-rose-50 text-rose-700 border-rose-100 dark:bg-rose-900/20 dark:text-rose-400 dark:border-rose-800/50",
    outline: "bg-transparent text-neutral-500 border-neutral-200 dark:text-neutral-400 dark:border-neutral-700",
};

const sizes = {
    xs: "px-2 py-0.5 text-[9px]",
    sm: "px-2 py-0.5 text-[10px]",
    md: "px-3 py-1 text-[11px]",
    lg: "px-4 py-1.5 text-xs",
};

const classes = computed(() => {
    return [
        "inline-flex items-center font-semibold text-xs tracking-wide rounded-full border",
        variants[props.variant],
        sizes[props.size],
        props.class
    ].join(' ');
});
</script>

<template>
    <span :class="classes">
        <slot />
    </span>
</template>
