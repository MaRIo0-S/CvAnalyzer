import { watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import { useToastStore } from "@/stores/toast";

export function useFlashToast() {
    const page = usePage();
    const toast = useToastStore();
    let dernierFlash = "";
    let dernierErreurs = "";

    function consumeFlash() {
        const flash = page.props.flash;
        const cle = `${flash?.success ?? ""}|${flash?.error ?? ""}|${flash?.info ?? ""}`;
        if (!cle.replace(/\|/g, "") || cle === dernierFlash) {
            return;
        }
        dernierFlash = cle;

        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
        if (flash?.info) {
            toast.info(flash.info);
        }
    }

    function consumeErrors() {
        const errors = page.props.errors || {};
        const messages = Object.values(errors).filter(
            (msg) => typeof msg === "string" && msg
        );
        const cle = messages.join("|");
        if (!cle || cle === dernierErreurs) {
            return;
        }
        dernierErreurs = cle;

        const seen = new Set();
        messages.forEach((msg) => {
            if (!seen.has(msg)) {
                seen.add(msg);
                toast.error(msg, 6500);
            }
        });
    }

    watch(
        () => page.url,
        () => {
            dernierFlash = "";
            dernierErreurs = "";
            consumeFlash();
            consumeErrors();
        }
    );

    watch(
        () => page.props.flash,
        () => consumeFlash(),
        { immediate: true, deep: true }
    );

    watch(
        () => page.props.errors,
        () => {
            dernierErreurs = "";
            consumeErrors();
        },
        { immediate: true, deep: true }
    );
}
