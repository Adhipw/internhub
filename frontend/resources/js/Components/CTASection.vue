<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import Button from './Button.vue';

interface Props {
    title: string;
    description: string;
    primaryActionText: string;
    primaryActionHref: string;
    secondaryActionText?: string;
    secondaryActionHref?: string;
}

defineProps<Props>();
</script>

<template>
    <section class="py-20">
        <div class="bg-primary-600 rounded-2xl p-12 md:p-24 text-center text-white relative overflow-hidden shadow-2xl shadow-primary-600/20">
            <div class="relative z-10 max-w-3xl mx-auto">
                <h2 class="text-3xl md:text-6xl font-extrabold mb-8 tracking-tight" v-html="title"></h2>
                <p class="text-xl text-primary-100 mb-12 font-medium opacity-90 leading-relaxed">{{ description }}</p>
                <div class="flex flex-col sm:flex-row justify-center gap-6">
                    <Link :href="primaryActionHref">
                        <Button variant="secondary" size="xl" class="w-full sm:w-auto !bg-white !text-primary-600">
                            {{ primaryActionText }}
                        </Button>
                    </Link>
                    <Link v-if="secondaryActionText" :href="secondaryActionHref || '#'">
                        <Button variant="outline" size="xl" class="w-full sm:w-auto !border-primary-400 !text-white hover:!bg-primary-700">
                            {{ secondaryActionText }}
                        </Button>
                    </Link>
                </div>
            </div>
            <!-- Decorative Elements -->
            <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-white/10 rounded-full blur-2xl opacity-50 -mr-64 -mt-64"></div>
            <div class="absolute bottom-0 left-0 w-[400px] h-[400px] bg-black/10 rounded-full blur-2xl opacity-50 -ml-48 -mb-48"></div>
        </div>
    </section>
</template>

