<script setup>
import { useToastStore } from "@/stores/toast";

const toast = useToastStore();
</script>

<template>
    <div class="toast-stack" aria-live="polite">
        <TransitionGroup name="toast">
            <div
                v-for="item in toast.items"
                :key="item.id"
                class="toast"
                :class="`toast--${item.type}`"
            >
                <span class="toast__message">{{ item.message }}</span>
                <button
                    type="button"
                    class="toast__close"
                    aria-label="Fermer"
                    @click="toast.remove(item.id)"
                >
                    ×
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
