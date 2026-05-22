<script setup>
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    CategoryScale,
    LinearScale,
    Filler,
} from "chart.js";
import { Bar, Doughnut, Line } from "vue-chartjs";
import { computed } from "vue";

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    LineElement,
    PointElement,
    ArcElement,
    CategoryScale,
    LinearScale,
    Filler
);

const props = defineProps({
    type: { type: String, default: "bar" },
    data: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
    height: { type: Number, default: 280 },
});

const components = { bar: Bar, doughnut: Doughnut, line: Line };

const chartComponent = computed(() => components[props.type] || Bar);

const defaultOptions = computed(() => ({
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: "bottom",
            labels: {
                boxWidth: 12,
                padding: 14,
                font: { size: 12 },
                color: "#9ca3b8",
            },
        },
    },
    scales:
        props.type === "line" || props.type === "bar"
            ? {
                  x: {
                      ticks: { color: "#9ca3b8" },
                      grid: { color: "rgba(255,255,255,0.06)" },
                  },
                  y: {
                      ticks: { color: "#9ca3b8" },
                      grid: { color: "rgba(255,255,255,0.06)" },
                  },
              }
            : undefined,
    ...props.options,
}));
</script>

<template>
    <div class="chart-wrap" :style="{ height: `${height}px` }">
        <component
            :is="chartComponent"
            :data="data"
            :options="defaultOptions"
        />
    </div>
</template>
