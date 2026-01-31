<script setup lang="ts">
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui';
import { ref, type HTMLAttributes } from 'vue';

interface Props extends /* @vue-ignore */ HTMLAttributes {
    modelValue?: string | null;
    accept?: string;
    maxSize?: number; // in MB
    aspectRatio?: 'square' | 'video' | 'banner';
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    accept: 'image/*',
    maxSize: 5,
    aspectRatio: 'square',
    placeholder: 'Click or drag to upload',
});

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
    'file': [file: File];
    'error': [message: string];
}>();

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const previewUrl = ref<string | null>(props.modelValue || null);

const aspectClasses = {
    square: 'aspect-square',
    video: 'aspect-video',
    banner: 'aspect-[3/1]',
};

const handleFileSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        processFile(file);
    }
};

const handleDrop = (event: DragEvent) => {
    event.preventDefault();
    isDragging.value = false;
    
    const file = event.dataTransfer?.files?.[0];
    if (file) {
        processFile(file);
    }
};

const processFile = (file: File) => {
    // Validate file type
    if (!file.type.startsWith('image/')) {
        emit('error', 'Please upload an image file');
        return;
    }
    
    // Validate file size
    if (file.size > props.maxSize * 1024 * 1024) {
        emit('error', `File size must be less than ${props.maxSize}MB`);
        return;
    }
    
    // Create preview
    const reader = new FileReader();
    reader.onload = (e) => {
        previewUrl.value = e.target?.result as string;
        emit('update:modelValue', previewUrl.value);
    };
    reader.readAsDataURL(file);
    
    emit('file', file);
};

const removeImage = () => {
    previewUrl.value = null;
    emit('update:modelValue', null);
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const triggerUpload = () => {
    fileInput.value?.click();
};
</script>

<template>
    <div :class="cn('relative', $props.class)">
        <input
            ref="fileInput"
            type="file"
            :accept="accept"
            class="hidden"
            @change="handleFileSelect"
        />
        
        <!-- Preview -->
        <div
            v-if="previewUrl"
            :class="cn(
                'relative overflow-hidden rounded-lg border',
                aspectClasses[aspectRatio]
            )"
        >
            <img
                :src="previewUrl"
                alt="Preview"
                class="h-full w-full object-cover"
            />
            <button
                type="button"
                class="absolute right-2 top-2 rounded-full bg-black/50 p-1.5 text-white transition-colors hover:bg-black/70"
                @click="removeImage"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <!-- Upload Zone -->
        <div
            v-else
            :class="cn(
                'flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed transition-colors',
                isDragging ? 'border-primary bg-primary/5' : 'border-muted-foreground/25 hover:border-primary',
                aspectClasses[aspectRatio]
            )"
            @click="triggerUpload"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop="handleDrop"
        >
            <svg
                class="mb-2 h-10 w-10 text-muted-foreground"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
            <p class="text-sm text-muted-foreground">{{ placeholder }}</p>
            <p class="mt-1 text-xs text-muted-foreground">
                Max {{ maxSize }}MB
            </p>
        </div>
    </div>
</template>
