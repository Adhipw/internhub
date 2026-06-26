<script setup lang="ts">
interface Props {
    width?: string;
    height?: string;
    circle?: boolean;
    rounded?: boolean;
    className?: string;
}

withDefaults(defineProps<Props>(), {
    width: '100%',
    height: '1rem',
    circle: false,
    rounded: true,
    className: '',
});
</script>

<template>
    <div 
        class="skeleton-loader bg-slate-200 dark:bg-slate-800 -gentle"
        :style="{ 
            width: width, 
            height: height,
        }"
        :class="[
            circle ? 'rounded-full' : (rounded ? 'rounded-xl' : ''),
            className
        ]"
    ></div>
</template>

<style scoped>
.skeleton-loader {
    position: relative;
    overflow: hidden;
}

.skeleton-loader::after {
    content: "";
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    transform: translateX(-100%);
    background-image: linear-gradient(
        90deg,
        rgba(255, 255, 255, 0) 0,
        rgba(255, 255, 255, 0.2) 20%,
        rgba(255, 255, 255, 0.5) 60%,
        rgba(255, 255, 255, 0)
    );
    animation: shimmer 2s infinite;
}

.dark .skeleton-loader::after {
    background-image: linear-gradient(
        90deg,
        rgba(15, 23, 42, 0) 0,
        rgba(30, 41, 59, 0.3) 20%,
        rgba(51, 65, 85, 0.4) 60%,
        rgba(15, 23, 42, 0)
    );
}

@keyframes shimmer {
    100% {
        transform: translateX(100%);
    }
}

@keyframes pulse-gentle {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.-gentle {
    animation: pulse-gentle 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
