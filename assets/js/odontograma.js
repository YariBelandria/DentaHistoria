(function () {
    'use strict';

    const patientId = Number(document.getElementById('recordPatientId')?.value || 0);
    const upperRow = document.getElementById('upperTeeth');
    const lowerRow = document.getElementById('lowerTeeth');
    const summary = document.getElementById('chartSummary');
    const legend = document.getElementById('chartLegend');
    const editorEmpty = document.getElementById('toothEditorEmpty');
    const editorBody = document.getElementById('toothEditorBody');
    const stateOptions = document.getElementById('stateOptions');
    const notesInput = document.getElementById('toothNotes');
    const saveButton = document.getElementById('saveTooth');
    const healthyButton = document.getElementById('markHealthy');
    const tabCount = document.getElementById('chartCount');

    if (!patientId || !upperRow || !lowerRow) return;

    const TEETH = {
        upper: ['18', '17', '16', '15', '14', '13', '12', '11', '21', '22', '23', '24', '25', '26', '27', '28'],
        lower: ['48', '47', '46', '45', '44', '43', '42', '41', '31', '32', '33', '34', '35', '36', '37', '38']
    };

    const STATES = [
        { key: 'sano', label: 'Sano', color: '#94a3b8' },
        { key: 'caries', label: 'Caries', color: '#dc2626' },
        { key: 'obturado', label: 'Calza / obturado', color: '#2563eb' },
        { key: 'corona', label: 'Corona', color: '#b45309' },
        { key: 'endodoncia', label: 'Endodoncia', color: '#7c3aed' },
        { key: 'extraccion', label: 'Extracción', color: '#475569' },
        { key: 'ausente', label: 'Ausente', color: '#94a3b8' },
        { key: 'implante', label: 'Implante', color: '#1a5ab0' },
        { key: 'sellante', label: 'Sellante', color: '#2eb7ec' }
    ];

    const stateMeta = (key) => STATES.find((item) => item.key === key) || STATES[0];

    const state = {
        teeth: {},
        selectedTooth: null,
        currentState: 'sano',
        loading: true
    };

    const toothData = (tooth) => state.teeth[tooth] || { estado: 'sano', notas: '' };

    const findingsCount = () => Object.values(state.teeth).filter((item) => item.estado && item.estado !== 'sano').length;

    const toothMarkup = (tooth) => {
        const data = toothData(tooth);
        const meta = stateMeta(data.estado);
        const selected = state.selectedTooth === tooth;
        return `
            <button type="button" class="tooth-state ${selected ? 'is-selected' : ''}"
                    data-tooth="${tooth}" data-state="${data.estado}"
                    title="Pieza ${tooth} · ${meta.label}"
                    aria-label="Pieza ${tooth}, ${meta.label}"
                    aria-pressed="${selected ? 'true' : 'false'}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><use href="#tooth-shape"></use></svg>
                <span>${tooth}</span>
            </button>`;
    };

    const renderRow = (container, teeth) => {
        container.innerHTML = teeth.map((tooth, index) =>
            `${index === 8 ? '<span class="dental-midline" aria-hidden="true"></span>' : ''}${toothMarkup(tooth)}`
        ).join('');
    };

    const renderChart = () => {
        renderRow(upperRow, TEETH.upper);
        renderRow(lowerRow, TEETH.lower);
        renderSummary();
    };

    const renderSummary = () => {
        const total = findingsCount();
        const healthy = 32 - total;
        if (tabCount) tabCount.textContent = String(total);
        summary.innerHTML = `
            <span><i class="bi bi-activity"></i> <strong>${total}</strong> pieza${total === 1 ? '' : 's'} con hallazgos</span>
            <span><i class="bi bi-check2-circle"></i> <strong>${healthy}</strong> sana${healthy === 1 ? '' : 's'}</span>
            <span class="chart-summary-note">Haz clic en una pieza para editarla</span>`;

        const counts = {};
        Object.values(state.teeth).forEach((item) => {
            if (!item.estado || item.estado === 'sano') return;
            counts[item.estado] = (counts[item.estado] || 0) + 1;
        });

        const used = STATES.filter((item) => counts[item.key]);
        legend.innerHTML = used.length
            ? used.map((item) => `
                <span class="chart-legend-item">
                    <span class="state-dot" style="--dot:${item.color}"></span>
                    ${App.escapeHtml(item.label)} <strong>${counts[item.key]}</strong>
                </span>`).join('')
            : '<span class="chart-legend-empty"><i class="bi bi-stars"></i> Sin hallazgos registrados: todas las piezas están sanas.</span>';
    };

    const renderEditor = () => {
        if (!state.selectedTooth) {
            editorEmpty.classList.remove('d-none');
            editorBody.classList.add('d-none');
            return;
        }

        const data = toothData(state.selectedTooth);
        const meta = stateMeta(state.currentState);
        editorEmpty.classList.add('d-none');
        editorBody.classList.remove('d-none');

        document.getElementById('selectedTooth').textContent = state.selectedTooth;
        document.getElementById('selectedToothTitle').textContent = state.selectedTooth;
        document.getElementById('selectedToothStatus').textContent = meta.label;
        notesInput.value = data.notas || '';

        stateOptions.innerHTML = STATES.map((item) => `
            <button type="button" class="state-option ${item.key === state.currentState ? 'is-active' : ''}"
                    data-state="${item.key}" aria-pressed="${item.key === state.currentState ? 'true' : 'false'}">
                <span class="state-dot" style="--dot:${item.color}"></span>
                ${App.escapeHtml(item.label)}
            </button>`).join('');
    };

    const selectTooth = (tooth) => {
        state.selectedTooth = tooth;
        state.currentState = toothData(tooth).estado;
        document.querySelectorAll('.tooth-state').forEach((button) => {
            const selected = button.dataset.tooth === tooth;
            button.classList.toggle('is-selected', selected);
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        renderEditor();
    };

    const saveTooth = async (nextState, nextNotes) => {
        if (!state.selectedTooth) return;
        const tooth = state.selectedTooth;
        const data = new FormData();
        data.append('paciente_id', String(patientId));
        data.append('pieza', tooth);
        data.append('estado', nextState);
        data.append('notas', nextNotes || '');

        saveButton.disabled = true;
        healthyButton.disabled = true;
        try {
            const response = await App.apiFetch('api/odontograma.php?action=save', { method: 'POST', body: data });
            if (nextState === 'sano' && !nextNotes) {
                delete state.teeth[tooth];
            } else {
                state.teeth[tooth] = { estado: nextState, notas: nextNotes || '' };
            }
            state.currentState = nextState;
            renderChart();
            renderEditor();
            App.toast(response.message || `Pieza ${tooth} actualizada.`, 'success');
        } catch (error) {
            App.toast(error.message || 'No se pudo guardar la pieza.', 'danger');
        } finally {
            saveButton.disabled = false;
            healthyButton.disabled = false;
        }
    };

    const loadChart = async () => {
        try {
            const response = await App.apiFetch(`api/odontograma.php?action=get&paciente_id=${patientId}`);
            state.teeth = response.data.teeth || {};
            state.loading = false;
            renderChart();
            if (!state.selectedTooth) {
                editorEmpty.classList.remove('d-none');
                editorBody.classList.add('d-none');
            }
        } catch (error) {
            state.loading = false;
            App.toast(error.message || 'No se pudo cargar el odontograma.', 'danger');
            upperRow.innerHTML = '<div class="chart-error">No se pudo cargar el odontograma.</div>';
            lowerRow.innerHTML = '';
            summary.innerHTML = '';
            legend.innerHTML = '';
        }
    };

    document.getElementById('dentalChart').addEventListener('click', (event) => {
        const tooth = event.target.closest('.tooth-state');
        if (tooth) selectTooth(tooth.dataset.tooth);
    });

    stateOptions.addEventListener('click', (event) => {
        const option = event.target.closest('.state-option');
        if (!option) return;
        state.currentState = option.dataset.state;
        renderEditor();
    });

    saveButton.addEventListener('click', () => saveTooth(state.currentState, notesInput.value.trim()));
    healthyButton.addEventListener('click', () => saveTooth('sano', ''));

    notesInput.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
            event.preventDefault();
            saveTooth(state.currentState, notesInput.value.trim());
        }
    });

    document.getElementById('resetChart').addEventListener('click', () => App.modal('resetChartModal')?.show());

    document.getElementById('confirmResetChart').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        button.disabled = true;
        try {
            const data = new FormData();
            data.append('paciente_id', String(patientId));
            const response = await App.apiFetch('api/odontograma.php?action=reset', { method: 'POST', body: data });
            state.teeth = {};
            state.selectedTooth = null;
            state.currentState = 'sano';
            renderChart();
            renderEditor();
            App.hideModal('resetChartModal');
            App.toast(response.message || 'Odontograma restablecido.', 'success');
        } catch (error) {
            App.toast(error.message || 'No se pudo restablecer el odontograma.', 'danger');
        } finally {
            button.disabled = false;
        }
    });

    loadChart();
})();
