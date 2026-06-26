<script setup lang="ts">
import { onMounted } from 'vue';
import { router as inertiaRouter } from '@inertiajs/vue3';

onMounted(async () => {
    inertiaRouter.visit('/dashboard');
});
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-slate-50 dark:bg-slate-950">
        <div class="text-center space-y-4">
            <div class="w-12 h-12 border-4 border-primary-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
            <p class="text-slate-600 dark:text-slate-400 font-medium ">Menyelesaikan autentikasi...</p>
        </div>
    </div>
</template>
