<script setup lang="ts">
interface Props {
    headers: { key: string; label: string; align?: 'left' | 'center' | 'right' }[];
    items: any[];
    loading?: boolean;
}

defineProps<Props>();
</script>

<template>
    <div class="overflow-x-auto bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-100 dark:border-neutral-800 shadow-premium-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="border-b border-neutral-50 dark:border-neutral-800">
                    <th 
                        v-for="header in headers" 
                        :key="header.key"
                        class="px-8 py-6 text-[10px] font-semibold text-sm tracking-wide text-neutral-400 dark:text-neutral-500"
                        :class="[`text-${header.align || 'left'}`]"
                    >
                        {{ header.label }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-50 dark:divide-neutral-800">
                <tr v-for="i in 5" v-if="loading" :key="i" class="">
                    <td v-for="h in headers" :key="h.key" class="px-8 py-6">
                        <div class="h-4 bg-neutral-100 dark:bg-neutral-800 rounded-lg w-full"></div>
                    </td>
                </tr>
                <tr 
                    v-for="(item, index) in items" 
                    v-else 
                    :key="index"
                    class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30 transition-colors"
                >
                    <td 
                        v-for="header in headers" 
                        :key="header.key"
                        class="px-8 py-6 text-sm font-medium text-neutral-600 dark:text-neutral-300"
                        :class="[`text-${header.align || 'left'}`]"
                    >
                        <slot :name="`cell(${header.key})`" :item="item">
                            {{ item[header.key] }}
                        </slot>
                    </td>
                </tr>
                <tr v-if="!loading && items.length === 0">
                    <td :colspan="headers.length" class="px-8 py-20 text-center">
                        <slot name="empty">
                            <p class="text-neutral-400 font-bold uppercase tracking-widest">Tidak ada data ditemukan</p>
                        </slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
