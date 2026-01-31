<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed, ref, type ImgHTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ ImgHTMLAttributes {
    src?: string;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    error: [event: Event];
}>();

const hasError = ref(false);

const classes = computed(() => cn('aspect-square h-full w-full', props.class));

const handleError = (event: Event) => {
    hasError.value = true;
    emit('error', event);
};
</script>

<template>
    <img
        v-if="src && !hasError"
        :src="src"
        :class="classes"
        @error="handleError"
    />
</template>
