(function () {
    'use strict';

    const bodyFont = () => getComputedStyle(document.body).fontFamily || 'Plus Jakarta Sans, sans-serif';
    const cssVar = (name, fallback) => {
        const value = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return value || fallback;
    };
    const hexToRgb = (hex) => {
        const clean = String(hex).replace('#', '').trim();
        if (!/^[0-9a-f]{6}$/i.test(clean)) return '47, 140, 232';
        return [0, 2, 4].map((index) => parseInt(clean.slice(index, index + 2), 16)).join(', ');
    };
    const alpha = (hex, opacity) => `rgba(${hexToRgb(hex)}, ${opacity})`;
    const chartPalette = () => [
        cssVar('--brand-600', '#2478dd'),
        cssVar('--celeste-500', '#2eb7ec'),
        cssVar('--violet', '#8b5cf6'),
        cssVar('--warning', '#d97706'),
        cssVar('--info', '#0ea5e9'),
        cssVar('--success', '#10b981'),
        cssVar('--pink-text', '#be185d'),
        cssVar('--accent-strong', '#1a5ab0')
    ];
    const chartTheme = () => ({
        accent: cssVar('--accent', '#2478dd'),
        ink: cssVar('--ink', '#12233c'),
        muted: cssVar('--muted-light', '#8699b3'),
        line: cssVar('--line-soft', '#e4eefa'),
        card: cssVar('--card', '#f9fcff'),
        tooltipBg: document.documentElement.getAttribute('data-theme') === 'dark' ? '#0b1524' : '#12233c',
        tooltipText: '#f3f9ff',
        palette: chartPalette(),
        font: bodyFont()
    });

    let chartInstances = [];
    let latestChartData = null;

    const setText = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };

    const trend = (id, current, previous) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.className = 'stat-trend';
        if (!previous) {
            el.innerHTML = current > 0
                ? '<i class="bi bi-stars"></i> Nuevo'
                : '<i class="bi bi-dash"></i> Sin datos';
            el.classList.add(current > 0 ? 'stat-trend-up' : 'stat-trend-neutral');
            return;
        }
        const delta = Math.round(((current - previous) / previous) * 100);
        if (delta > 0) {
            el.innerHTML = `<i class="bi bi-arrow-up-right"></i> ${delta}%`;
            el.classList.add('stat-trend-up');
        } else if (delta < 0) {
            el.innerHTML = `<i class="bi bi-arrow-down-right"></i> ${Math.abs(delta)}%`;
            el.classList.add('stat-trend-down');
        } else {
            el.innerHTML = '<i class="bi bi-dash"></i> Igual';
            el.classList.add('stat-trend-neutral');
        }
    };

    const renderRecentPatients = (patients) => {
        const tbody = document.getElementById('recentPatients');
        if (!tbody) return;
        if (!patients.length) {
            tbody.innerHTML = `<tr><td colspan="4">${App.emptyState('bi-people', 'Aún no hay pacientes', 'Registra el primer paciente para comenzar.')}</td></tr>`;
            return;
        }
        tbody.innerHTML = patients.map((patient) => {
            const name = `${patient.nombres} ${patient.apellidos}`;
            const age = App.age(patient.fecha_nacimiento);
            return `
                <tr>
                    <td>
                        <div class="person-cell">
                            <span class="avatar avatar-sm">${App.initials(name)}</span>
                            <div>
                                <a class="person-name" href="paciente.php?id=${patient.id}">${App.escapeHtml(name)}</a>
                                <small>${age === null ? 'Edad no registrada' : age + ' años'}${patient.telefono ? ' · ' + App.escapeHtml(patient.telefono) : ''}</small>
                            </div>
                        </div>
                    </td>
                    <td><span class="code-pill">${App.escapeHtml(patient.codigo || '—')}</span></td>
                    <td><span class="date-text">${App.formatDate(patient.ultima_visita, 'Sin visitas')}</span></td>
                    <td class="text-end">
                        <a class="btn btn-icon btn-light btn-sm" href="paciente.php?id=${patient.id}" title="Ver historia clínica" aria-label="Ver historia clínica">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </td>
                </tr>`;
        }).join('');
    };

    const renderUpcoming = (items) => {
        const container = document.getElementById('upcomingList');
        if (!container) return;
        if (!items.length) {
            container.innerHTML = App.emptyState('bi-calendar2-x', 'Sin citas próximas', 'Las próximas citas programadas aparecerán aquí.');
            return;
        }
        container.innerHTML = items.map((item) => {
            const date = new Date(item.proxima_cita + 'T00:00:00');
            const day = date.getDate();
            const month = new Intl.DateTimeFormat('es', { month: 'short' }).format(date).replace('.', '').toUpperCase();
            return `
                <a class="upcoming-item" href="paciente.php?id=${item.paciente_id}">
                    <span class="upcoming-date"><strong>${String(day).padStart(2, '0')}</strong><small>${month}</small></span>
                    <span class="upcoming-main">
                        <strong>${App.escapeHtml(item.paciente)}</strong>
                        <small>${App.escapeHtml(item.motivo || 'Consulta odontológica')}</small>
                    </span>
                    <span class="upcoming-time">${App.escapeHtml(item.telefono || '')}</span>
                </a>`;
        }).join('');
    };

    const renderActivity = (records) => {
        const container = document.getElementById('recentRecords');
        if (!container) return;
        if (!records.length) {
            container.innerHTML = App.emptyState('bi-journal-medical', 'Sin actividad reciente', 'Las atenciones registradas aparecerán aquí.');
            return;
        }
        container.innerHTML = records.map((record) => {
            const procedures = (record.procedimientos || '').split('||').filter(Boolean);
            return `
                <a class="activity-item" href="paciente.php?id=${record.paciente_id}">
                    <span class="activity-icon"><i class="bi bi-journal-medical"></i></span>
                    <span class="activity-main">
                        <span class="activity-top">
                            <strong>${App.escapeHtml(record.paciente)}</strong>
                            <span class="status-pill ${App.statusClass(record.estado)}">${App.statusLabel(record.estado)}</span>
                        </span>
                        <span class="activity-title">${App.escapeHtml(record.motivo || 'Atención odontológica')}</span>
                        <span class="activity-meta">
                            <span><i class="bi bi-calendar3"></i> ${App.formatDate(record.fecha)}</span>
                            ${procedures.length ? `<span><i class="bi bi-clipboard2-pulse"></i> ${procedures.map(App.escapeHtml).join(', ')}</span>` : ''}
                        </span>
                    </span>
                    <span class="activity-arrow"><i class="bi bi-arrow-right"></i></span>
                </a>`;
        }).join('');
    };

    const initCharts = (data) => {
        if (!window.Chart) return;

        latestChartData = data;
        chartInstances.forEach((chart) => chart.destroy());
        chartInstances = [];

        const theme = chartTheme();
        const font = theme.font;
        const baseTooltip = {
            backgroundColor: theme.tooltipBg,
            titleColor: theme.tooltipText,
            bodyColor: theme.tooltipText,
            padding: 12,
            cornerRadius: 10,
            titleFont: { family: font, weight: '700' },
            bodyFont: { family: font }
        };

        const monthlyCanvas = document.getElementById('monthlyChart');
        const monthlyEmpty = document.getElementById('monthlyEmpty');
        const total = data.monthly.values.reduce((sum, value) => sum + Number(value), 0);

        if (monthlyCanvas) {
            if (total === 0) {
                monthlyCanvas.classList.add('d-none');
                monthlyEmpty?.classList.remove('d-none');
            } else {
                monthlyCanvas.classList.remove('d-none');
                monthlyEmpty?.classList.add('d-none');
                const monthlyChart = new Chart(monthlyCanvas, {
                    type: 'line',
                    data: {
                        labels: data.monthly.labels,
                        datasets: [{
                            label: 'Atenciones',
                            data: data.monthly.values,
                            borderColor: theme.accent,
                            backgroundColor: (context) => {
                                const chart = context.chart;
                                const { ctx, chartArea } = chart;
                                if (!chartArea) return alpha(theme.accent, 0.12);
                                const gradient = ctx.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                                gradient.addColorStop(0, alpha(theme.accent, 0.30));
                                gradient.addColorStop(1, alpha(theme.accent, 0.01));
                                return gradient;
                            },
                            fill: true,
                            tension: 0.42,
                            borderWidth: 3,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: theme.card,
                            pointBorderColor: theme.accent,
                            pointBorderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { intersect: false, mode: 'index' },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                ...baseTooltip,
                                displayColors: false,
                                callbacks: {
                                    label: (context) => `${context.parsed.y} atenciones`
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: { color: theme.muted, font: { family: font, size: 11, weight: '600' } }
                            },
                            y: {
                                beginAtZero: true,
                                ticks: { precision: 0, color: theme.muted, font: { family: font, size: 11 } },
                                border: { display: false },
                                grid: { color: theme.line }
                            }
                        }
                    }
                });
                chartInstances.push(monthlyChart);
            }
        }

        const categoryCanvas = document.getElementById('categoryChart');
        const legend = document.getElementById('categoryLegend');
        const hasCategories = data.categories.labels.length > 0;

        if (categoryCanvas && legend) {
            if (!hasCategories) {
                categoryCanvas.classList.add('d-none');
                legend.innerHTML = App.emptyState('bi-pie-chart', 'Sin datos todavía', 'Registra atenciones para ver la distribución.');
            } else {
                categoryCanvas.classList.remove('d-none');
                const palette = theme.palette;
                const categoryTotal = data.categories.values.reduce((sum, value) => sum + Number(value), 0);
                const centerText = {
                    id: 'centerText',
                    afterDraw(chart) {
                        const { ctx, chartArea } = chart;
                        ctx.save();
                        const centerX = (chartArea.left + chartArea.right) / 2;
                        const centerY = (chartArea.top + chartArea.bottom) / 2;
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillStyle = theme.ink;
                        ctx.font = `800 26px ${font}`;
                        ctx.fillText(String(categoryTotal), centerX, centerY - 7);
                        ctx.fillStyle = theme.muted;
                        ctx.font = `600 10px ${font}`;
                        ctx.fillText('APLICACIONES', centerX, centerY + 13);
                        ctx.restore();
                    }
                };

                const categoryChart = new Chart(categoryCanvas, {
                    type: 'doughnut',
                    data: {
                        labels: data.categories.labels,
                        datasets: [{
                            data: data.categories.values,
                            backgroundColor: data.categories.labels.map((_, index) => palette[index % palette.length]),
                            borderWidth: 0,
                            hoverOffset: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: '68%',
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                ...baseTooltip,
                                padding: 10,
                                cornerRadius: 9
                            }
                        }
                    },
                    plugins: [centerText]
                });
                chartInstances.push(categoryChart);

                legend.innerHTML = data.categories.labels.map((label, index) => {
                    const value = Number(data.categories.values[index]);
                    const percent = categoryTotal ? Math.round((value / categoryTotal) * 100) : 0;
                    return `
                        <span class="legend-item">
                            <span class="legend-dot" style="background:${palette[index % palette.length]}"></span>
                            <span class="legend-name">${App.escapeHtml(label)}</span>
                            <span class="legend-value">${value}<small>${percent}%</small></span>
                        </span>`;
                }).join('');
            }
        }
    };

    document.addEventListener('dh:themechange', () => {
        if (latestChartData) initCharts(latestChartData);
    });

    const load = async () => {
        try {
            const response = await App.apiFetch('api/dashboard.php');
            const data = response.data;

            App.animateNumber(document.getElementById('statPacientes'), data.stats.pacientes);
            App.animateNumber(document.getElementById('statAtenciones'), data.stats.atenciones_mes);
            App.animateNumber(document.getElementById('statProcedimientos'), data.stats.procedimientos);
            App.animateNumber(document.getElementById('statProximas'), data.stats.proximas);

            setText('statPacientesSub', `${data.stats.nuevos_mes} nuevo(s) este mes`);
            setText('statAtencionesSub', data.stats.atenciones_mes_anterior
                ? `vs. ${data.stats.atenciones_mes_anterior} el mes pasado`
                : 'Actividad de este mes');

            trend('patientsTrend', data.stats.nuevos_mes, data.stats.nuevos_mes_anterior);
            trend('recordsTrend', data.stats.atenciones_mes, data.stats.atenciones_mes_anterior);

            renderRecentPatients(data.recent_patients);
            renderUpcoming(data.upcoming);
            renderActivity(data.recent_records);
            initCharts(data);
        } catch (error) {
            App.toast(error.message || 'No se pudo cargar el panel.', 'danger');
            const fallback = App.emptyState('bi-cloud-slash', 'No se pudo cargar la información', 'Intenta actualizar la página.');
            ['recentPatients', 'upcomingList', 'recentRecords', 'categoryLegend'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) el.innerHTML = id === 'recentPatients' ? `<tr><td colspan="4">${fallback}</td></tr>` : fallback;
            });
            ['statPacientes', 'statAtenciones', 'statProcedimientos', 'statProximas'].forEach((id) => {
                const el = document.getElementById(id);
                if (el) { el.classList.remove('skeleton'); el.textContent = '—'; }
            });
        }
    };

    load();
})();
