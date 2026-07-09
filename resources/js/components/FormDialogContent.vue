<script setup lang="ts">
import { DialogContent } from '@/components/ui/dialog';

defineOptions({
    inheritAttrs: false,
});
</script>

<template>
    <DialogContent
        v-bind="$attrs"
        @interact-outside="(e: Event) => e.preventDefault()"
        @pointer-down-outside="(e: Event) => e.preventDefault()"
    >
        <slot />
    </DialogContent>
</template>
