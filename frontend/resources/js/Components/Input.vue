<script setup lang="ts">
import { computed, useAttrs } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    modelValue?: string | number;
    type?: string;
    placeholder?: string;
    label?: string;
    error?: string | string[];
    disabled?: boolean;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    type: 'text',
    placeholder: '',
    label: '',
    error: '',
    disabled: false,
    class: '',
});

const emit = defineEmits(['update:modelValue']);
const attrs = useAttrs();

const inputId = computed(() => {
    const id = attrs.id;
    return typeof id === 'string' ? id : undefined;
});

const hasError = computed(() => {
    if (!props.error) return false;
    return Array.isArray(props.error) ? props.error.length > 0 : !!props.error;
});

const firstError = computed(() => {
    if (!props.error) return '';
    return Array.isArray(props.error) ? props.error[0] : props.error;
});

const inputClasses = computed(() => {
    return [
        "w-full px-4 py-3 bg-white dark:bg-neutral-900 border border-slate-200 dark:border-neutral-700 rounded-xl text-sm font-medium text-slate-900 dark:text-white transition-all duration-200 shadow-sm",
        "placeholder:text-slate-400 dark:placeholder:text-neutral-500",
        "focus:outline-none focus:ring-0 focus:border-slate-900 dark:focus:border-slate-300",
        hasError.value ? "border-red-500 focus:border-red-500" : "hover:border-slate-300 dark:hover:border-neutral-600",
        props.disabled ? "opacity-50 cursor-not-allowed bg-slate-100 dark:bg-neutral-800" : "",
        props.class
    ].join(' ');
});
</script>

<template>
    <div class="space-y-1">
        <label v-if="label" :for="inputId" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
            {{ label }}
        </label>
        <div class="relative">
            <input
                v-bind="attrs"
                :type="type"
                :value="modelValue"
                :placeholder="placeholder"
                :disabled="disabled"
                :class="inputClasses"
                @input="emit('update:modelValue', ($event.target as HTMLInputElement).value)"
            />
            <slot name="suffix" />
        </div>
        <p v-if="hasError" class="text-[11px] font-bold text-red-500 ml-1">
            {{ firstError }}
        </p>
    </div>
</template>
