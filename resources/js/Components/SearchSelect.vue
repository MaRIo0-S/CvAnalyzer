<script setup>
import { computed, ref, watch } from "vue";

const props = defineProps({
    modelValue: { type: [String, Number], default: "" },
    items: { type: Array, default: () => [] },
    label: { type: String, default: "Choisir" },
    placeholder: { type: String, default: "Rechercher…" },
    disabled: { type: Boolean, default: false },
    emptyText: { type: String, default: "Aucun résultat." },
});

const emit = defineEmits(["update:modelValue"]);

const query = ref("");
const open = ref(false);
const searching = ref(false);
let searchTimer = null;

const selected = computed(() =>
    props.items.find((i) => String(i.id) === String(props.modelValue))
);

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();
    if (!q) return props.items;
    return props.items.filter((i) => {
        const label = (i.label || "").toLowerCase();
        const desc = (i.description || "").toLowerCase();
        return label.includes(q) || desc.includes(q);
    });
});

watch(query, () => {
    searching.value = true;
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        searching.value = false;
    }, 220);
});

watch(
    () => props.modelValue,
    (id) => {
        if (id && selected.value) {
            query.value = selected.value.label;
        } else if (!id) {
            query.value = "";
        }
    },
    { immediate: true }
);

function pick(item) {
    emit("update:modelValue", item.id);
    query.value = item.label;
    open.value = false;
}

function clear() {
    emit("update:modelValue", "");
    query.value = "";
    open.value = false;
}

function onFocus() {
    if (!props.disabled) open.value = true;
}

function onBlur() {
    setTimeout(() => {
        open.value = false;
    }, 180);
}
</script>

<template>
    <div class="search-select" :class="{ 'search-select--disabled': disabled }">
        <label class="search-select__label">{{ label }}</label>
        <div class="search-select__field">
            <input
                v-model="query"
                type="text"
                class="search-select__input"
                :placeholder="placeholder"
                :disabled="disabled"
                autocomplete="off"
                @focus="onFocus"
                @blur="onBlur"
                @input="open = true"
            />
            <span
                v-if="searching"
                class="search-select__spinner"
                aria-hidden="true"
            />
            <button
                v-if="modelValue && !disabled"
                type="button"
                class="search-select__clear"
                title="Effacer"
                @mousedown.prevent="clear"
            >
                ×
            </button>
        </div>
        <ul v-if="open && !disabled" class="search-select__list">
            <li v-if="searching" class="search-select__hint">Recherche…</li>
            <li
                v-else-if="filtered.length === 0"
                class="search-select__hint"
            >
                {{ emptyText }}
            </li>
            <li
                v-for="item in filtered"
                :key="item.id"
                class="search-select__option"
                :class="{
                    'search-select__option--active':
                        String(item.id) === String(modelValue),
                }"
                @mousedown.prevent="pick(item)"
            >
                <strong>{{ item.label }}</strong>
                <span
                    v-if="item.description"
                    class="search-select__option-desc"
                >
                    {{ item.description.slice(0, 80)
                    }}{{ item.description.length > 80 ? "…" : "" }}
                </span>
            </li>
        </ul>
    </div>
</template>
