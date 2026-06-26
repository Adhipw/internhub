<script setup lang="ts">
interface Props {
    class?: string;
}
defineProps<Props>();
</script>

<template>
    <div class="w-full max-w-md mx-auto animate-reveal">
        <div class="bg-white dark:bg-neutral-900 rounded-2xl p-10 md:p-12 shadow-2xl shadow-neutral-200/50 dark:shadow-none border border-neutral-100 dark:border-neutral-800 relative overflow-hidden">
            <!-- Subtle Decorative Gradient -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-primary-500/5 rounded-full blur-2xl opacity-50 -mr-16 -mt-16"></div>
            
            <div class="relative z-10">
                <slot />
            </div>
        </div>
    </div>
</template>
