import { watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useToastStore } from "@/stores/toast";

export function useFlashToast() {
    const page = usePage();
    const toast = useToastStore();

    function consumeFlash() {
        const flash = page.props.flash;
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    }

    function consumeErrors() {
        const errors = page.props.errors || {};
        Object.values(errors).forEach((msg) => {
            if (typeof msg === "string" && msg) {
                toast.error(msg, 6500);
            }
        });
    }

    watch(
        () => page.props.flash,
        () => consumeFlash(),
        { deep: true, immediate: true }
    );

    watch(
        () => page.props.errors,
        () => consumeErrors(),
        { deep: true, immediate: true }
    );
}
