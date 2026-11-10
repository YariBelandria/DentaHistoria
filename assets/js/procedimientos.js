(function () {
    'use strict';

    const state = {
        q: '',
        categoria: '',
        estado: '',
        categories: [],
        categoriesLoaded: false,
        deleteId: null
    };

    const grid = document.getElementById('proceduresGrid');
    const form = document.getElementById('procedureForm');
    const modalEl = document.getElementById('procedureModal');
    const submitBtn = document.getElementById('procedureSubmit');

    const categoryClass = (category) => ({
        'Preventiva': 'cat-preventiva',
        'Estética': 'cat-estetica',
        'Cirugía': 'cat-cirugia',
        'Endodoncia': 'cat-endodoncia',
        'Restauración': 'cat-restauracion',
        'Ortodoncia': 'cat-ortodoncia',
        'Diagnóstico': 'cat-diagnostico'
    }[category] || 'cat-general');

    const populateCategories = () => {
        const filter = document.getElementById('procCategory');
        const modalSelect = document.getElementById('procCategoria');

        if (filter && filter.options.length <= 1) {
            state.categories.forEach((category) => {
                filter.insertAdjacentHTML('beforeend', `<option value="${App.escapeHtml(category)}">${App.escapeHtml(category)}</option>`);
            });
        }
        if (modalSelect && modalSelect.options.length <= 1) {
            state.categories.forEach((category) => {
                modalSelect.insertAdjacentHTML('beforeend', `<option value="${App.escapeHtml(category)}">${App.escapeHtml(category)}</option>`);
            });
        }
    };

    const renderStats = (stats) => {
        App.animateNumber(document.getElementById('procStatTotal'), stats.total);
        App.animateNumber(document.getElementById('procStatActivos'), stats.activos);
        App.animateNumber(document.getElementById('procStatCategorias'), stats.categorias);
        App.animateNumber(document.getElementById('procStatUsos'), stats.usos);
    };

    const renderCards = (items) => {
        if (!items.length) {
            grid.innerHTML = App.emptyState('bi-clipboard2-x', 'No encontramos procedimientos', 'Prueba con otro filtro o agrega un nuevo procedimiento.');
            return;
        }

        grid.innerHTML = items.map((procedure) => {
            const active = Number(procedure.activo) === 1;
            return `
                <article class="procedure-card ${active ? '' : 'is-inactive'}">
                    <div class="procedure-card-top">
                        <span class="category-badge ${categoryClass(procedure.categoria)}">${App.escapeHtml(procedure.categoria)}</span>
                        <div class="table-actions">
                            <button class="btn btn-icon btn-light btn-sm" type="button" data-action="edit" data-id="${procedure.id}" title="Editar procedimiento" aria-label="Editar procedimiento"><i class="bi bi-pencil"></i></button>
                            <button class="btn btn-icon btn-light btn-sm" type="button" data-action="toggle" data-id="${procedure.id}" title="${active ? 'Desactivar' : 'Activar'} procedimiento" aria-label="${active ? 'Desactivar' : 'Activar'} procedimiento"><i class="bi ${active ? 'bi-pause-circle' : 'bi-play-circle'}"></i></button>
                            <button class="btn btn-icon btn-light btn-sm btn-icon-danger" type="button" data-action="delete" data-id="${procedure.id}" data-name="${App.escapeHtml(procedure.nombre)}" title="Eliminar procedimiento" aria-label="Eliminar procedimiento"><i class="bi bi-trash3"></i></button>
                        </div>
                    </div>
                    <h3>${App.escapeHtml(procedure.nombre)}</h3>
                    <p class="procedure-desc">${procedure.descripcion ? App.escapeHtml(procedure.descripcion) : 'Sin descripción registrada.'}</p>
                    <div class="procedure-meta">
                        <span><i class="bi bi-clock"></i> ${Number(procedure.duracion_min)} min</span>
                        <span><i class="bi bi-activity"></i> ${Number(procedure.usos)} uso${Number(procedure.usos) === 1 ? '' : 's'}</span>
                    </div>
                    <div class="procedure-card-foot">
                        <strong class="procedure-price">$${Number(procedure.precio).toFixed(2)}</strong>
                        <span class="status-pill ${active ? 'estado-realizado' : 'estado-pendiente'}">${active ? 'Activo' : 'Inactivo'}</span>
                    </div>
                </article>`;
        }).join('');
    };

    const loadProcedures = async () => {
        const params = new URLSearchParams({ action: 'list' });
        if (state.q) params.set('q', state.q);
        if (state.categoria) params.set('categoria', state.categoria);
        if (state.estado) params.set('estado', state.estado);

        try {
            const response = await App.apiFetch(`api/procedimientos.php?${params.toString()}`);
            const { items, stats, categorias } = response.data;
            if (!state.categoriesLoaded) {
                state.categories = categorias;
                state.categoriesLoaded = true;
                populateCategories();
            }
            renderStats(stats);
            renderCards(items);
        } catch (error) {
            App.toast(error.message || 'No se pudo cargar el catálogo.', 'danger');
            grid.innerHTML = App.emptyState('bi-cloud-slash', 'No se pudo cargar el catálogo', 'Actualiza la página e intenta de nuevo.');
        }
    };

    const clearForm = () => {
        form.reset();
        App.clearFormErrors(form);
        document.getElementById('procedureId').value = '';
        document.getElementById('procPrecio').value = '0.00';
        document.getElementById('procDuracion').value = '30';
        document.getElementById('procActivo').checked = true;
        document.getElementById('procedureModalLabel').textContent = 'Nuevo procedimiento';
        submitBtn.dataset.original = 'Guardar procedimiento';
        submitBtn.querySelector('.btn-label').textContent = 'Guardar procedimiento';
    };

    const openNew = () => {
        clearForm();
        App.modal('procedureModal')?.show();
        setTimeout(() => document.getElementById('procNombre').focus(), 350);
    };

    const openEdit = async (id) => {
        try {
            const response = await App.apiFetch(`api/procedimientos.php?action=get&id=${encodeURIComponent(id)}`);
            const procedure = response.data.procedure;
            clearForm();
            document.getElementById('procedureId').value = procedure.id;
            document.getElementById('procNombre').value = procedure.nombre || '';
            document.getElementById('procCategoria').value = procedure.categoria || '';
            document.getElementById('procPrecio').value = Number(procedure.precio).toFixed(2);
            document.getElementById('procDuracion').value = procedure.duracion_min || 30;
            document.getElementById('procDescripcion').value = procedure.descripcion || '';
            document.getElementById('procActivo').checked = Number(procedure.activo) === 1;
            document.getElementById('procedureModalLabel').textContent = 'Editar procedimiento';
            App.modal('procedureModal')?.show();
        } catch (error) {
            App.toast(error.message || 'No se pudo cargar el procedimiento.', 'danger');
        }
    };

    const openDelete = (id, name) => {
        state.deleteId = Number(id);
        document.getElementById('deleteProcedureText').textContent =
            `El procedimiento "${name}" se eliminará del catálogo. Si tiene atenciones asociadas, solo se desactivará.`;
        App.modal('deleteProcedureModal')?.show();
    };

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        App.clearFormErrors(form);

        const id = document.getElementById('procedureId').value;
        const data = new FormData(form);
        data.set('activo', document.getElementById('procActivo').checked ? '1' : '0');

        App.setLoading(submitBtn, true, 'Guardando…');
        try {
            const response = await App.apiFetch(`api/procedimientos.php?action=${id ? 'update' : 'create'}`, {
                method: 'POST',
                body: data
            });
            App.toast(response.message || 'Procedimiento guardado.', 'success');
            App.hideModal('procedureModal');
            await loadProcedures();
        } catch (error) {
            App.showFormErrors(form, error.payload?.errors, error.message);
        } finally {
            App.setLoading(submitBtn, false);
        }
    });

    grid.addEventListener('click', async (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button) return;
        const id = button.dataset.id;

        if (button.dataset.action === 'edit') {
            openEdit(id);
            return;
        }

        if (button.dataset.action === 'delete') {
            openDelete(id, button.dataset.name || '');
            return;
        }

        if (button.dataset.action === 'toggle') {
            button.disabled = true;
            try {
                const data = new FormData();
                data.append('id', id);
                const response = await App.apiFetch('api/procedimientos.php?action=toggle', { method: 'POST', body: data });
                App.toast(response.message || 'Procedimiento actualizado.', 'success');
                await loadProcedures();
            } catch (error) {
                App.toast(error.message || 'No se pudo actualizar el procedimiento.', 'danger');
                button.disabled = false;
            }
        }
    });

    document.getElementById('confirmDeleteProcedure').addEventListener('click', async (event) => {
        const button = event.currentTarget;
        if (!state.deleteId) return;
        button.disabled = true;
        try {
            const data = new FormData();
            data.append('id', String(state.deleteId));
            const response = await App.apiFetch('api/procedimientos.php?action=delete', { method: 'POST', body: data });
            App.toast(response.message || 'Procedimiento eliminado.', 'success');
            App.hideModal('deleteProcedureModal');
            state.deleteId = null;
            await loadProcedures();
        } catch (error) {
            App.toast(error.message || 'No se pudo eliminar el procedimiento.', 'danger');
        } finally {
            button.disabled = false;
        }
    });

    document.getElementById('btnNewProcedure').addEventListener('click', openNew);

    document.getElementById('procSearch').addEventListener('input', App.debounce((event) => {
        state.q = event.target.value.trim();
        loadProcedures();
    }, 320));

    document.getElementById('procCategory').addEventListener('change', (event) => {
        state.categoria = event.target.value;
        loadProcedures();
    });

    document.getElementById('procStatus').addEventListener('change', (event) => {
        state.estado = event.target.value;
        loadProcedures();
    });

    document.getElementById('procRefresh').addEventListener('click', () => loadProcedures());
    modalEl.addEventListener('hidden.bs.modal', () => App.clearFormErrors(form));

    loadProcedures();

    if (new URLSearchParams(window.location.search).get('nuevo') === '1') {
        openNew();
        history.replaceState({}, '', 'procedimientos.php');
    }
})();
