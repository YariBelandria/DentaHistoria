(function () {
    'use strict';

    const patientId = Number(document.getElementById('recordPatientId')?.value || 0);
    const state = {
        records: [],
        procedures: [],
        categories: [],
        filter: '',
        editingId: null,
        deleteId: null,
        selectedTeeth: new Set(),
        selectedProcedures: new Set(),
        proceduresLoaded: false
    };

    const form = document.getElementById('recordForm');
    const modalEl = document.getElementById('recordModal');
    const submitBtn = document.getElementById('recordSubmit');
    const timeline = document.getElementById('historyTimeline');

    const esc = App.escapeHtml;

    const formatTime = (time) => {
        if (!time) return '';
        const [hour, minute] = String(time).split(':');
        if (hour === undefined || minute === undefined) return '';
        return `${hour}:${minute}`;
    };

    const renderSummary = (summary) => {
        const set = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value;
        };
        set('summaryTotal', String(summary.total || 0));
        set('summaryLast', App.formatDate(summary.ultima_visita, 'Sin visitas'));
        set('summaryNext', App.formatDate(summary.proxima_cita, 'Sin programar'));
        set('summaryProcedures', String(summary.procedimientos_distintos || 0));
        const count = document.getElementById('historyCount');
        if (count) count.textContent = String(summary.total || 0);
    };

    const renderTimeline = () => {
        const records = state.filter
            ? state.records.filter((record) => record.estado === state.filter)
            : state.records;

        if (!state.records.length) {
            timeline.innerHTML = `
                <div class="empty-state empty-state-lg">
                    <span class="empty-icon"><i class="bi bi-journal-plus"></i></span>
                    <h4>La historia clínica está vacía</h4>
                    <p>Registra la primera atención para comenzar a construir el historial del paciente.</p>
                    <button class="btn btn-primary" type="button" data-open-record><i class="bi bi-plus-lg"></i> Registrar primera atención</button>
                </div>`;
            return;
        }

        if (!records.length) {
            timeline.innerHTML = App.emptyState('bi-funnel', 'Sin atenciones con este estado', 'Prueba con otro filtro para ver más registros.');
            return;
        }

        timeline.innerHTML = records.map((record) => {
            const procedures = (record.procedimientos_lista || []).map((name) =>
                `<span class="procedure-chip">${esc(name)}</span>`
            ).join('');

            const editable = record.puede_editar;
            const nextVisit = record.proxima_cita
                ? `<span><i class="bi bi-calendar-heart"></i> Próxima: ${App.formatDate(record.proxima_cita)}</span>`
                : '';

            return `
                <div class="timeline-item">
                    <span class="timeline-dot ${App.statusClass(record.estado)}"></span>
                    <article class="timeline-card">
                        <div class="timeline-card-head">
                            <div class="timeline-date">
                                <span class="timeline-date-main"><i class="bi bi-calendar3"></i> ${App.formatDate(record.fecha)}</span>
                                ${record.hora ? `<span class="timeline-hour">${formatTime(record.hora)}</span>` : ''}
                            </div>
                            <div class="timeline-head-actions">
                                <span class="status-pill ${App.statusClass(record.estado)}">${App.statusLabel(record.estado)}</span>
                                ${editable ? `
                                    <div class="record-actions">
                                        <button class="btn btn-icon btn-light btn-sm" type="button" data-action="edit-record" data-id="${record.id}" title="Editar atención" aria-label="Editar atención"><i class="bi bi-pencil"></i></button>
                                        <button class="btn btn-icon btn-light btn-sm btn-icon-danger" type="button" data-action="delete-record" data-id="${record.id}" title="Eliminar atención" aria-label="Eliminar atención"><i class="bi bi-trash3"></i></button>
                                    </div>` : ''}
                            </div>
                        </div>

                        <h3 class="timeline-title">${esc(record.motivo || 'Atención odontológica')}</h3>
                        ${procedures ? `<div class="procedure-chips">${procedures}</div>` : '<p class="record-empty-line">Sin procedimientos asociados.</p>'}

                        <div class="record-detail-grid">
                            <div>
                                <span class="record-label">Diagnóstico</span>
                                <p class="record-text ${record.diagnostico ? '' : 'record-empty'}">${record.diagnostico ? esc(record.diagnostico) : 'No registrado'}</p>
                            </div>
                            <div>
                                <span class="record-label">Tratamiento</span>
                                <p class="record-text ${record.tratamiento ? '' : 'record-empty'}">${record.tratamiento ? esc(record.tratamiento) : 'No registrado'}</p>
                            </div>
                            ${record.observaciones ? `
                            <div class="record-detail-full">
                                <span class="record-label">Observaciones</span>
                                <p class="record-text">${esc(record.observaciones)}</p>
                            </div>` : ''}
                        </div>

                        <div class="record-foot">
                            <span><i class="bi bi-person-badge"></i> ${esc(record.odontologo || 'Odontólogo no registrado')}</span>
                            <span><i class="bi bi-grid-3x3-gap"></i> Piezas: ${esc(record.dientes || 'No especificadas')}</span>
                            ${nextVisit}
                        </div>
                    </article>
                </div>`;
        }).join('');
    };

    const loadHistory = async () => {
        try {
            const response = await App.apiFetch(`api/atenciones.php?action=list&paciente_id=${patientId}`);
            state.records = response.data.records;
            renderSummary(response.data.summary);
            renderTimeline();
        } catch (error) {
            App.toast(error.message || 'No se pudo cargar la historia clínica.', 'danger');
            timeline.innerHTML = App.emptyState('bi-cloud-slash', 'No se pudo cargar la historia', 'Actualiza la página e intenta de nuevo.');
        }
    };

    const syncProcedureChecks = () => {
        document.querySelectorAll('.procedure-checkbox').forEach((checkbox) => {
            checkbox.checked = state.selectedProcedures.has(Number(checkbox.value));
        });
        const count = document.getElementById('selectedProcedures');
        if (count) {
            const total = state.selectedProcedures.size;
            count.textContent = `${total} seleccionado${total === 1 ? '' : 's'}`;
        }
    };

    const renderProcedures = () => {
        const container = document.getElementById('procedureOptions');
        if (!container) return;

        if (!state.procedures.length) {
            container.innerHTML = App.emptyState('bi-clipboard2-x', 'No hay procedimientos', 'Agrega procedimientos al catálogo para poder registrarlos.');
            return;
        }

        const grouped = {};
        state.categories.forEach((category) => { grouped[category] = []; });
        state.procedures.forEach((procedure) => {
            if (!grouped[procedure.categoria]) grouped[procedure.categoria] = [];
            grouped[procedure.categoria].push(procedure);
        });

        container.innerHTML = Object.entries(grouped).filter(([, items]) => items.length).map(([category, items]) => `
            <div class="procedure-group" data-group>
                <span class="procedure-group-title">${esc(category)}</span>
                <div class="procedure-group-items">
                    ${items.map((procedure) => `
                        <label class="procedure-option ${Number(procedure.activo) === 1 ? '' : 'is-inactive'}">
                            <input class="form-check-input procedure-checkbox" type="checkbox" value="${procedure.id}" data-name="${esc(procedure.nombre)}">
                            <span class="procedure-option-body">
                                <strong>${esc(procedure.nombre)}</strong>
                                <small>${Number(procedure.duracion_min)} min · $${Number(procedure.precio).toFixed(2)}${Number(procedure.activo) === 1 ? '' : ' · Inactivo'}</small>
                            </span>
                        </label>`).join('')}
                </div>
            </div>`).join('');

        syncProcedureChecks();
    };

    const loadProcedures = async () => {
        if (state.proceduresLoaded) return;
        try {
            const response = await App.apiFetch('api/procedimientos.php?action=list');
            state.procedures = response.data.items;
            state.categories = response.data.categorias;
            state.proceduresLoaded = true;
            renderProcedures();
        } catch (error) {
            App.toast(error.message || 'No se pudieron cargar los procedimientos.', 'danger');
        }
    };

    const setTeeth = (teeth) => {
        state.selectedTeeth = new Set(teeth.map(String));
        document.querySelectorAll('.tooth').forEach((button) => {
            const selected = state.selectedTeeth.has(button.dataset.tooth);
            button.classList.toggle('selected', selected);
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        const count = document.getElementById('selectedTeethCount');
        if (count) {
            const total = state.selectedTeeth.size;
            count.textContent = `${total} pieza${total === 1 ? '' : 's'}`;
        }
    };

    const clearForm = () => {
        form.reset();
        App.clearFormErrors(form);
        document.getElementById('recordId').value = '';
        document.getElementById('recordFecha').value = new Date().toISOString().slice(0, 10);
        document.getElementById('recordHora').value = new Date().toTimeString().slice(0, 5);
        document.getElementById('recordEstado').value = 'realizado';
        document.getElementById('recordModalLabel').textContent = 'Nueva atención';
        document.getElementById('procedureSearch').value = '';
        document.querySelectorAll('.procedure-group').forEach((group) => group.classList.remove('d-none'));
        document.querySelectorAll('.procedure-option').forEach((option) => option.classList.remove('d-none'));
        state.selectedProcedures.clear();
        setTeeth([]);
        syncProcedureChecks();
        submitBtn.dataset.original = 'Guardar atención';
        submitBtn.querySelector('.btn-label').textContent = 'Guardar atención';
    };

    const openNewRecord = async () => {
        await loadProcedures();
        clearForm();
        App.modal('recordModal')?.show();
        setTimeout(() => document.getElementById('recordMotivo').focus(), 350);
    };

    const openEditRecord = async (id) => {
        await loadProcedures();
        try {
            const response = await App.apiFetch(`api/atenciones.php?action=get&id=${encodeURIComponent(id)}`);
            const record = response.data.record;
            App.clearFormErrors(form);
            document.getElementById('recordId').value = record.id;
            document.getElementById('recordFecha').value = record.fecha || '';
            document.getElementById('recordHora').value = record.hora ? formatTime(record.hora) : '';
            document.getElementById('recordEstado').value = record.estado || 'realizado';
            document.getElementById('recordMotivo').value = record.motivo || '';
            document.getElementById('recordDiagnostico').value = record.diagnostico || '';
            document.getElementById('recordTratamiento').value = record.tratamiento || '';
            document.getElementById('recordObservaciones').value = record.observaciones || '';
            document.getElementById('recordProxima').value = record.proxima_cita || '';
            document.getElementById('recordModalLabel').textContent = 'Editar atención';
            state.selectedProcedures = new Set((record.procedimiento_ids || []).map(Number));
            setTeeth(record.dientes ? String(record.dientes).split(',') : []);
            syncProcedureChecks();
            submitBtn.dataset.original = 'Guardar cambios';
            submitBtn.querySelector('.btn-label').textContent = 'Guardar cambios';
            App.modal('recordModal')?.show();
        } catch (error) {
            App.toast(error.message || 'No se pudo cargar la atención.', 'danger');
        }
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        App.clearFormErrors(form);

        const id = document.getElementById('recordId').value;
        const action = id ? 'update' : 'create';
        const data = new FormData(form);
        data.append('paciente_id', String(patientId));
        data.set('dientes', Array.from(state.selectedTeeth).join(','));
        state.selectedProcedures.forEach((procedureId) => data.append('procedimientos[]', String(procedureId)));

        App.setLoading(submitBtn, true, id ? 'Guardando cambios…' : 'Registrando…');
        try {
            const response = await App.apiFetch(`api/atenciones.php?action=${action}`, {
                method: 'POST',
                body: data
            });
            App.toast(response.message || 'Atención guardada.', 'success');
            App.hideModal('recordModal');
            await loadHistory();
        } catch (error) {
            App.showFormErrors(form, error.payload?.errors, error.message);
        } finally {
            App.setLoading(submitBtn, false);
        }
    });

    document.querySelectorAll('#btnNewRecord, #btnNewRecordInline').forEach((button) => {
        button.addEventListener('click', openNewRecord);
    });

    document.getElementById('historyFilter').addEventListener('change', (event) => {
        state.filter = event.target.value;
        renderTimeline();
    });

    timeline.addEventListener('click', (event) => {
        const openButton = event.target.closest('[data-open-record]');
        if (openButton) {
            openNewRecord();
            return;
        }
        const actionButton = event.target.closest('button[data-action]');
        if (!actionButton) return;
        const id = Number(actionButton.dataset.id);
        if (actionButton.dataset.action === 'edit-record') {
            openEditRecord(id);
        } else if (actionButton.dataset.action === 'delete-record') {
            state.deleteId = id;
            App.modal('deleteRecordModal')?.show();
        }
    });

    document.getElementById('confirmDeleteRecord').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        if (!state.deleteId) return;
        button.disabled = true;
        try {
            const data = new FormData();
            data.append('id', String(state.deleteId));
            const response = await App.apiFetch('api/atenciones.php?action=delete', { method: 'POST', body: data });
            App.toast(response.message || 'Atención eliminada.', 'success');
            App.hideModal('deleteRecordModal');
            state.deleteId = null;
            await loadHistory();
        } catch (error) {
            App.toast(error.message || 'No se pudo eliminar la atención.', 'danger');
        } finally {
            button.disabled = false;
        }
    });

    document.getElementById('procedureOptions').addEventListener('change', (event) => {
        const checkbox = event.target.closest('.procedure-checkbox');
        if (!checkbox) return;
        const id = Number(checkbox.value);
        if (checkbox.checked) state.selectedProcedures.add(id);
        else state.selectedProcedures.delete(id);
        syncProcedureChecks();
    });

    document.getElementById('procedureSearch').addEventListener('input', (event) => {
        const term = event.target.value.trim().toLowerCase();
        document.querySelectorAll('.procedure-option').forEach((option) => {
            const name = (option.dataset.name || option.textContent || '').toLowerCase();
            option.classList.toggle('d-none', term !== '' && !name.includes(term));
            if (name.includes(term)) option.dataset.name = option.dataset.name || name;
        });
        document.querySelectorAll('.procedure-group').forEach((group) => {
            const visible = group.querySelectorAll('.procedure-option:not(.d-none)').length > 0;
            group.classList.toggle('d-none', !visible);
        });
    });

    document.querySelectorAll('.tooth').forEach((button) => {
        button.addEventListener('click', () => {
            const tooth = button.dataset.tooth;
            if (state.selectedTeeth.has(tooth)) state.selectedTeeth.delete(tooth);
            else state.selectedTeeth.add(tooth);
            setTeeth(Array.from(state.selectedTeeth));
        });
    });

    document.getElementById('clearTeeth').addEventListener('click', () => setTeeth([]));

    modalEl.addEventListener('hidden.bs.modal', () => App.clearFormErrors(form));

    loadHistory();
    loadProcedures();
})();
