import { defineStore } from "pinia";

let nextId = 0;

export const useToastStore = defineStore("toast", {
    state: () => ({
        items: [],
    }),
    actions: {
        push(message, type = "success", duration = 4500) {
            const id = ++nextId;
            this.items.push({ id, message, type });
            if (duration > 0) {
                setTimeout(() => this.remove(id), duration);
            }
        },
        success(message) {
            this.push(message, "success");
        },
        error(message) {
            this.push(message, "error", 6000);
        },
        remove(id) {
            this.items = this.items.filter((t) => t.id !== id);
        },
    },
});
