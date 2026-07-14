import {
    Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale,
    Tooltip, Legend, DoughnutController, ArcElement, BarController, BarElement, Filler,
} from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend, DoughnutController, ArcElement, BarController, BarElement, Filler);

const insights = window.workLiveEmployeeInsights || { history: window.workLiveEmployeeHistory || [], apps: [], domains: [] };
const palette = ['#6366f1', '#8b5cf6', '#06b6d4', '#10b981', '#f59e0b', '#f43f5e', '#64748b'];
const hours = (value) => `${Number(value || 0).toFixed(1)} h`;

function createCanvas(parent, className) {
    if (!parent) return null;
    const canvas = document.createElement('canvas');
    canvas.className = className;
    parent.append(canvas);
    return canvas;
}

function panelFor(title) {
    return [...document.querySelectorAll('section')].find((section) => section.querySelector('h3')?.textContent?.includes(title));
}

function lineChart() {
    const host = document.querySelector('.h-72');
    host?.querySelector('svg')?.remove();
    host?.querySelectorAll('.employee-history-labels, .flex.justify-between, .flex.justify-center').forEach((element) => element.remove());
    const canvas = document.getElementById('employee-activity-chart') || createCanvas(host, 'employee-chart-canvas');
    if (!canvas || !insights.history.length) return;
    const context = canvas.getContext('2d');
    const gradient = context.createLinearGradient(0, 0, 0, 320);
    gradient.addColorStop(0, 'rgba(99, 102, 241, .28)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');
    new Chart(canvas, {
        type: 'line',
        data: {
            labels: insights.history.map((item) => item.date),
            datasets: [
                { label: 'Activo', data: insights.history.map((item) => item.active), borderColor: '#4f46e5', backgroundColor: gradient, fill: true, borderWidth: 3, pointRadius: 3, pointHoverRadius: 6, pointBackgroundColor: '#fff', pointBorderColor: '#4f46e5', pointBorderWidth: 3, tension: .42 },
                { label: 'Inactivo', data: insights.history.map((item) => item.idle), borderColor: '#f59e0b', backgroundColor: 'transparent', borderWidth: 2, borderDash: [5, 5], pointRadius: 2, pointHoverRadius: 5, pointBackgroundColor: '#fff', pointBorderColor: '#f59e0b', pointBorderWidth: 2, tension: .42 },
            ],
        },
        options: {
            responsive: true, maintainAspectRatio: false, interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle', padding: 20, boxWidth: 7, color: '#64748b', font: { size: 11, weight: 700 } } },
                tooltip: { backgroundColor: '#0f172a', titleColor: '#fff', bodyColor: '#cbd5e1', padding: 12, cornerRadius: 10, displayColors: true, callbacks: { label: (context) => ` ${context.dataset.label}: ${hours(context.raw)}` } },
            },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: '#94a3b8', font: { size: 10, weight: 600 } } },
                y: { beginAtZero: true, grid: { color: '#eef2ff', borderDash: [4, 4] }, border: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 }, callback: (value) => `${value}h` } },
            },
        },
    });
}

function doughnutChart() {
    const panel = panelFor('Distribución de Foco por Aplicación');
    const canvas = document.getElementById('employee-app-chart') || createCanvas(panel, 'employee-mini-chart employee-app-canvas');
    if (!canvas || !insights.apps.length) return;
    panel?.classList.add('metrics-chart-enhanced');
    panel?.querySelector('h3')?.insertAdjacentHTML('afterbegin', '<i class="fa-solid fa-shapes metrics-heading-icon"></i>');
    panel?.querySelector('.mt-6')?.remove();
    const apps = insights.apps.slice(0, 7);
    new Chart(canvas, {
        type: 'bar',
        data: { labels: apps.map((item) => item.name), datasets: [{ data: apps.map((item) => item.hours), backgroundColor: apps.map((_, index) => palette[index]), borderRadius: 8, borderSkipped: false, barThickness: 13 }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', padding: 11, cornerRadius: 10, callbacks: { label: (context) => ` ${hours(context.raw)}` } } }, scales: { x: { beginAtZero: true, grid: { color: '#eef2ff' }, border: { display: false }, ticks: { color: '#94a3b8', font: { size: 9 }, callback: (value) => `${value}h` } }, y: { grid: { display: false }, border: { display: false }, ticks: { color: '#475569', font: { size: 11, weight: 800 } } } } },
    });
}

function domainChart() {
    const panel = panelFor('Top Dominios Visitados');
    const canvas = document.getElementById('employee-domain-chart') || createCanvas(panel, 'employee-mini-chart employee-domain-canvas');
    if (!canvas || !insights.domains.length) return;
    panel?.classList.add('metrics-chart-enhanced');
    panel?.querySelector('h3')?.insertAdjacentHTML('afterbegin', '<i class="fa-solid fa-globe metrics-heading-icon cyan"></i>');
    panel?.querySelector('.space-y-4')?.remove();
    new Chart(canvas, {
        type: 'bar', data: { labels: insights.domains.map((item) => item.name), datasets: [{ data: insights.domains.map((item) => item.hours), backgroundColor: '#6366f1', borderRadius: 8, borderSkipped: false, barThickness: 11 }] },
        options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', padding: 11, cornerRadius: 10, callbacks: { label: (context) => ` ${hours(context.raw)}` } } }, scales: { x: { beginAtZero: true, grid: { color: '#eef2ff' }, border: { display: false }, ticks: { color: '#94a3b8', font: { size: 9 }, callback: (value) => `${value}h` } }, y: { grid: { display: false }, border: { display: false }, ticks: { color: '#475569', font: { size: 10, weight: 700 } } } } },
    });
}

lineChart();
doughnutChart();
domainChart();
