(function () {
    'use strict';

    const state = { page: 1, q: '', sexo: '', orden: 'recientes', deleteId: null };
    const tbody = document.getElementById('patientsBody');
    const form = document.getElementById('patientForm');
    const modalEl = document.getElementById('patientModal');
    const deleteModalEl = document.getElementById('deletePatientModal');
    const submitBtn = document.getElementById('patientSubmit');
    const isAdmin = window.APP_USER && window.APP_USER.rol === 'admin';

    const searchInput = document.getElementById('patientSearch');
    const sexFilter = document.getElementById('patientSex');
    const orderFilter = document.getElementById('patientOrder');

    const renderStats = (stats) => {
        App.animateNumber(document.getElementById('patientStatTotal'), stats.total);
        App.animateNumber(document.getElementById('patientStatNuevos'), stats.nuevos_mes);
        App.animateNumber(document.getElementById('patientStatAlergias'), stats.con_alergias);
    };

    const renderRows = (items) => {
        if (!items.length) {
            tbody.innerHTML = `
                <tr><td colspan="6">
                    ${App.emptyState('bi-person-plus', 'No encontramos pacientes', 'Prueba con otra búsqueda o registra un nuevo paciente.')}
                </td></tr>`;
            return;
        }

        tbody.innerHTML = items.map((patient) => {
            const name = `${patient.nombres} ${patient.apellidos}`;
            const age = App.age(patient.fecha_nacimiento);
            const allergy = patient.alergias ? '<span class="allergy-dot" title="Paciente con alergias"></span>' : '';
            return `
                <tr>
                    <td>
                        <div class="person-cell">
                            <span class="avatar avatar-sm">${App.initials(name)}</span>
                            <div>
                                <a class="person-name" href="paciente.php?id=${patient.id}">${App.escapeHtml(name)} ${allergy}</a>
                                <small>${App.escapeHtml(patient.codigo || '—')}${age !== null ? ' · ' + age + ' años' : ''}</small>
                            </div>
                        </div>
                    </td>
                    <td class="d-none d-md-table-cell"><span class="date-text">${App.escapeHtml(patient.documento || '—')}</span></td>
                    <td class="d-none d-lg-table-cell">
                        <div class="contact-cell">
                            <span>${App.escapeHtml(patient.telefono || 'Sin teléfono')}</span>
                            <small>${App.escapeHtml(patient.email || 'Sin correo')}</small>
                        </div>
                    </td>
                    <td class="d-none d-sm-table-cell"><span class="date-text">${App.formatDate(patient.ultima_visita, 'Sin visitas')}</span></td>
                    <td class="d-none d-xl-table-cell text-center"><span class="count-pill">${patient.total_atenciones}</span></td>
                    <td class="text-end">
                        <div class="table-actions">
                            <a class="btn btn-icon btn-light btn-sm" href="paciente.php?id=${patient.id}" title="Ver historia clínica" aria-label="Ver historia clínica">
                                <i class="bi bi-folder2-open"></i>
                            </a>
                            <button class="btn btn-icon btn-light btn-sm" type="button" data-action="edit" data-id="${patient.id}" title="Editar paciente" aria-label="Editar paciente">
                                <i class="bi bi-pencil"></i>
                            </button>
                            ${isAdmin ? `<button class="btn btn-icon btn-light btn-sm btn-icon-danger" type="button" data-action="delete" data-id="${patient.id}" data-name="${App.escapeHtml(name)}" title="Eliminar paciente" aria-label="Eliminar paciente">
                                <i class="bi bi-trash3"></i>
                            </button>` : ''}
                        </div>
                    </td>
                </tr>`;
        }).join('');
    };

    const renderPagination = (pagination) => {
        const container = document.getElementById('patientsPagination');
        if (!container) return;
        const { page, pages, total, per_page: perPage } = pagination;
        const start = total === 0 ? 0 : (page - 1) * perPage + 1;
        const end = Math.min(page * perPage, total);
        document.getElementById('patientsInfo').textContent = total === 0
            ? 'Sin pacientes para mostrar'
            : `Mostrando ${start}–${end} de ${total} pacientes`;

        if (pages <= 1) {
            container.innerHTML = '';
            return;
        }

        const buttons = [];
        buttons.push(`<li class="page-item ${page === 1 ? 'disabled' : ''}">
            <button class="page-link" type="button" data-page="${page - 1}" aria-label="Anterior"><i class="bi bi-chevron-left"></i></button>
        </li>`);

        let startPage = Math.max(1, page - 2);
        let endPage = Math.min(pages, startPage + 4);
        startPage = Math.max(1, endPage - 4);

        for (let i = startPage; i <= endPage; i++) {
            buttons.push(`<li class="page-item ${i === page ? 'active' : ''}">
                <button class="page-link" type="button" data-page="${i}">${i}</button>
            </li>`);
        }

        buttons.push(`<li class="page-item ${page === pages ? 'disabled' : ''}">
            <button class="page-link" type="button" data-page="${page + 1}" aria-label="Siguiente"><i class="bi bi-chevron-right"></i></button>
        </li>`);

        container.innerHTML = buttons.join('');
    };

    const loadPatients = async () => {
        const params = new URLSearchParams({
            action: 'list',
            page: String(state.page),
            per_page: '10',
            orden: state.orden
        });
        if (state.q) params.set('q', state.q);
        if (state.sexo) params.set('sexo', state.sexo);

        try {
            const response = await App.apiFetch(`api/pacientes.php?${params.toString()}`);
            state.page = response.data.pagination.page;
            renderStats(response.data.stats);
            renderRows(response.data.items);
            renderPagination(response.data.pagination);
        } catch (error) {
            App.toast(error.message || 'No se pudo cargar la lista de pacientes.', 'danger');
            tbody.innerHTML = `<tr><td colspan="6">${App.emptyState('bi-cloud-slash', 'No se pudo cargar la información', 'Actualiza la página e intenta de nuevo.')}</td></tr>`;
        }
    };

    const clearForm = () => {
        form.reset();
        App.clearFormErrors(form);
        document.getElementById('patientId').value = '';
        document.getElementById('patientSexo').value = 'O';
        document.getElementById('patientModalLabel').textContent = 'Nuevo paciente';
        submitBtn.dataset.original = 'Guardar paciente';
        submitBtn.querySelector('.btn-label').textContent = 'Guardar paciente';
    };

    const fillForm = (patient) => {
        const values = {
            nombres: patient.nombres,
            apellidos: patient.apellidos,
            documento: patient.documento,
            fecha_nacimiento: patient.fecha_nacimiento,
            sexo: patient.sexo || 'O',
            telefono: patient.telefono,
            email: patient.email,
            direccion: patient.direccion,
            ocupacion: patient.ocupacion,
            alergias: patient.alergias,
            antecedentes: patient.antecedentes,
            notas: patient.notas
        };
        Object.entries(values).forEach(([name, value]) => {
            const field = form.elements[name];
            if (field) field.value = value || '';
        });
    };

    const openNew = () => {
        clearForm();
        App.modal('patientModal')?.show();
        setTimeout(() => document.getElementById('patientNombres').focus(), 350);
    };

    const openEdit = async (id) => {
        try {
            const response = await App.apiFetch(`api/pacientes.php?action=get&id=${encodeURIComponent(id)}`);
            clearForm();
            fillForm(response.data.patient);
            document.getElementById('patientId').value = response.data.patient.id;
            document.getElementById('patientModalLabel').textContent = 'Editar paciente';
            App.modal('patientModal')?.show();
        } catch (error) {
            App.toast(error.message || 'No se pudo cargar el paciente.', 'danger');
        }
    };

    const openDelete = (id, name) => {
        state.deleteId = Number(id);
        document.getElementById('deletePatientText').textContent =
            `Se eliminará el paciente "${name}" y toda su historia clínica. Esta acción no se puede deshacer.`;
        App.modal('deletePatientModal')?.show();
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        App.clearFormErrors(form);

        const id = document.getElementById('patientId').value;
        const data = new FormData(form);
        const action = id ? 'update' : 'create';

        App.setLoading(submitBtn, true, 'Guardando…');
        try {
            const response = await App.apiFetch(`api/pacientes.php?action=${action}`, {
                method: 'POST',
                body: data
            });
            App.toast(response.message || 'Paciente guardado.', 'success');
            App.hideModal('patientModal');
            if (!id) state.page = 1;
            await loadPatients();
        } catch (error) {
            App.showFormErrors(form, error.payload?.errors, error.message);
        } finally {
            App.setLoading(submitBtn, false);
        }
    });

    tbody.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button) return;
        const id = button.dataset.id;
        if (button.dataset.action === 'edit') openEdit(id);
        if (button.dataset.action === 'delete') openDelete(id, button.dataset.name || '');
    });

    document.getElementById('patientsPagination').addEventListener('click', (event) => {
        const button = event.target.closest('button[data-page]');
        if (!button || button.closest('.disabled') || button.closest('.active')) return;
        const page = Number(button.dataset.page);
        if (page > 0) {
            state.page = page;
            loadPatients();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });

    document.getElementById('confirmDeletePatient').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        if (!state.deleteId) return;
        button.disabled = true;
        try {
            const response = await App.apiFetch('api/pacientes.php?action=delete', {
                method: 'POST',
                body: (() => { const data = new FormData(); data.append('id', state.deleteId); return data; })()
            });
            App.toast(response.message || 'Paciente eliminado.', 'success');
            App.hideModal('deletePatientModal');
            state.deleteId = null;
            await loadPatients();
        } catch (error) {
            App.toast(error.message || 'No se pudo eliminar el paciente.', 'danger');
        } finally {
            button.disabled = false;
        }
    });

    document.getElementById('btnNewPatient').addEventListener('click', openNew);

    searchInput.addEventListener('input', App.debounce(() => {
        state.q = searchInput.value.trim();
        state.page = 1;
        loadPatients();
    }, 350));

    sexFilter.addEventListener('change', () => {
        state.sexo = sexFilter.value;
        state.page = 1;
        loadPatients();
    });

    orderFilter.addEventListener('change', () => {
        state.orden = orderFilter.value;
        state.page = 1;
        loadPatients();
    });

    document.getElementById('patientRefresh').addEventListener('click', () => loadPatients());

    modalEl.addEventListener('hidden.bs.modal', () => App.clearFormErrors(form));

    loadPatients();

    const params = new URLSearchParams(window.location.search);
    if (params.get('nuevo') === '1') {
        openNew();
    } else if (params.get('editar')) {
        openEdit(params.get('editar'));
    }
    if (params.has('nuevo') || params.has('editar')) {
        history.replaceState({}, '', 'pacientes.php');
    }
})();
