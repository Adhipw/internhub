<script setup lang="ts">
import { watch, onMounted } from 'vue';

const props = defineProps<{
  title?: string;
  description?: string;
}>();

const updateHead = () => {
  if (props.title) {
    document.title = props.title.includes('InternHub') 
      ? props.title 
      : `${props.title} - InternHub`;
  }
  
  if (props.description) {
    let metaDesc = document.querySelector('meta[name="description"]');
    if (!metaDesc) {
      metaDesc = document.createElement('meta');
      metaDesc.setAttribute('name', 'description');
      document.head.appendChild(metaDesc);
    }
    metaDesc.setAttribute('content', props.description);
  }
};

onMounted(updateHead);
watch(() => props.title, updateHead);
watch(() => props.description, updateHead);
</script>

<template>
  <!-- This component doesn't render anything in the DOM -->
  <slot />
</template>
