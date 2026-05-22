<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, onUnmounted, ref, watch } from "vue";

const page = usePage();
const open = ref(false);
const btnRef = ref(null);
const panelTop = ref(0);
const panelRight = ref(0);

const data = computed(() => page.props.candidatNotifications);
const unread = computed(() => data.value?.unread_count ?? 0);
const items = computed(() => data.value?.items ?? []);

function placePanel() {
    const btn = btnRef.value;
    if (!btn) return;
    const r = btn.getBoundingClientRect();
    panelTop.value = r.bottom + 8;
    panelRight.value = window.innerWidth - r.right;
}

function close() {
    open.value = false;
}

function toggle() {
    if (open.value) {
        close();
        return;
    }
    placePanel();
    open.value = true;
    if (unread.value > 0) {
        router.post("/candidat/notifications/tout-lu", {}, { preserveScroll: true });
    }
}

watch(
    () => page.url,
    () => close()
);

onUnmounted(() => {
    close();
    document.querySelectorAll(".notif-bell__backdrop").forEach((el) => el.remove());
});
</script>

<template>
    <div v-if="data" class="notif-bell">
        <button
            ref="btnRef"
            type="button"
            class="notif-bell__btn"
            :aria-expanded="open"
            :aria-label="`Notifications (${unread} non lues)`"
            @click="toggle"
        >
            <svg
                class="notif-bell__icon"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path
                    d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"
                />
                <path d="M13.73 21a2 2 0 0 1-3.46 0" />
            </svg>
            <span v-if="unread > 0" class="notif-bell__badge">{{
                unread > 9 ? "9+" : unread
            }}</span>
        </button>

        <Teleport to="body">
            <div
                v-if="open"
                class="notif-bell__backdrop"
                @click="close"
            />
            <div
                v-if="open"
                class="notif-bell__panel notif-bell__panel--fixed"
                :style="{ top: panelTop + 'px', right: panelRight + 'px' }"
                @click.stop
            >
                <div class="notif-bell__head">
                    <strong>Notifications</strong>
                </div>
                <div class="notif-bell__scroll">
                    <ul v-if="items.length" class="notif-bell__list">
                        <li
                            v-for="n in items"
                            :key="n.id"
                            :class="[
                                'notif-bell__item',
                                { 'notif-bell__item--unread': !n.lu },
                            ]"
                        >
                            <p class="notif-bell__msg">{{ n.message }}</p>
                            <p class="notif-bell__meta">
                                {{ n.statut_label }} · {{ n.date }}
                            </p>
                        </li>
                    </ul>
                    <p v-else class="notif-bell__empty">Aucune notification.</p>
                </div>
                <Link
                    href="/candidat/statut"
                    class="notif-bell__footer"
                    @click="close"
                >
                    Voir ma candidature
                </Link>
            </div>
        </Teleport>
    </div>
</template>
