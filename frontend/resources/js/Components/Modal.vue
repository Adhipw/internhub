<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue';
import { X } from 'lucide-vue-next';

interface Props {
    show?: boolean;
    maxWidth?: 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl';
    closeable?: boolean;
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    show: false,
    maxWidth: '2xl',
    closeable: true,
    title: '',
});

const emit = defineEmits(['close']);

watch(() => props.show, () => {
    if (props.show) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = 'visible';
    }
});

const close = () => {
    if (props.closeable) {
        emit('close');
    }
};

const closeOnEscape = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.show) {
        close();
    }
};

onMounted(() => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = 'visible';
});

const maxWidthClass = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-md',
    lg: 'sm:max-w-lg',
    xl: 'sm:max-w-xl',
    '2xl': 'sm:max-w-2xl',
    '3xl': 'sm:max-w-3xl',
    '4xl': 'sm:max-w-4xl',
}[props.maxWidth];
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="ease-out duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="ease-in duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-show="show" class="fixed inset-0 overflow-y-auto px-4 py-6 sm:px-0 z-[100]" scroll-region>
                <!-- Backdrop -->
                <div class="fixed inset-0 transform transition-all bg-neutral-900/60" aria-hidden="true" @click="close"></div>

                <Transition
                    enter-active-class="ease-out duration-300"
                    enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-active-class="ease-in duration-200"
                    leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                    leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                >
                    <div
                        v-show="show"
                        class="mb-6 bg-white dark:bg-neutral-900 rounded-2xl overflow-hidden shadow-2xl transform transition-all sm:w-full sm:mx-auto border border-neutral-100 dark:border-neutral-800 relative z-[110]"
                        :class="maxWidthClass"
                        @click.stop
                    >
                        <!-- Modal Header -->
                        <div v-if="title" class="px-10 pt-10 pb-6 flex items-center justify-between">
                            <h3 class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">{{ title }}</h3>
                            <button 
                                v-if="closeable"
                                class="w-10 h-10 flex items-center justify-center rounded-xl bg-neutral-50 dark:bg-neutral-800 text-neutral-400 hover:text-neutral-900 dark:hover:text-white transition-all"
                                @click="close"
                            >
                                <X class="w-6 h-6" />
                            </button>
                        </div>
                        
                        <div class="px-10 pb-10 pt-4">
                            <slot v-if="show" />
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
