<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pageTitle = 'Procedimientos';
$active = 'procedimientos';
require __DIR__ . '/includes/layout/header.php';
?>

<div class="page-heading reveal">
    <div>
        <span class="eyebrow"><i class="bi bi-clipboard2-pulse"></i> Catálogo clínico</span>
        <h2>Procedimientos odontológicos</h2>
        <p>Administra los tratamientos y servicios que ofrece la clínica.</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" type="button" id="btnNewProcedure">
            <i class="bi bi-plus-lg"></i> Nuevo procedimiento
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="mini-stat reveal" style="--delay:.05s">
            <span class="mini-stat-icon stat-icon-teal"><i class="bi bi-grid-3x3-gap-fill"></i></span>
            <div><strong id="procStatTotal">—</strong><span>Total</span></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="mini-stat reveal" style="--delay:.1s">
            <span class="mini-stat-icon stat-icon-green"><i class="bi bi-check2-circle"></i></span>
            <div><strong id="procStatActivos">—</strong><span>Activos</span></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="mini-stat reveal" style="--delay:.15s">
            <span class="mini-stat-icon stat-icon-violet"><i class="bi bi-collection-fill"></i></span>
            <div><strong id="procStatCategorias">—</strong><span>Categorías</span></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="mini-stat reveal" style="--delay:.2s">
            <span class="mini-stat-icon stat-icon-blue"><i class="bi bi-activity"></i></span>
            <div><strong id="procStatUsos">—</strong><span>Aplicaciones</span></div>
        </div>
    </div>
</div>

<section class="panel-card reveal" style="--delay:.22s">
    <div class="panel-head panel-head-toolbar">
        <div class="toolbar-search">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control" id="procSearch" placeholder="Buscar procedimiento…" aria-label="Buscar procedimiento">
        </div>
        <div class="toolbar-filters">
            <select class="form-select" id="procCategory" aria-label="Filtrar por categoría">
                <option value="">Todas las categorías</option>
            </select>
            <select class="form-select" id="procStatus" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <option value="activos">Solo activos</option>
                <option value="inactivos">Solo inactivos</option>
            </select>
            <button class="btn btn-icon btn-light" type="button" id="procRefresh" title="Actualizar" aria-label="Actualizar lista">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <div class="procedure-grid" id="proceduresGrid">
        <?php for ($i = 0; $i < 6; $i++): ?>
            <div class="procedure-card skeleton-card">
                <div class="skeleton-line skeleton"></div>
                <div class="skeleton-line skeleton" style="width:70%"></div>
                <div class="skeleton-line skeleton" style="width:90%"></div>
            </div>
        <?php endfor; ?>
    </div>
</section>

<!-- Modal de procedimiento -->
<div class="modal fade" id="procedureModal" tabindex="-1" aria-labelledby="procedureModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form id="procedureForm" novalidate>
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Catálogo de servicios</span>
                        <h5 class="modal-title" id="procedureModalLabel">Nuevo procedimiento</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="procedureAlert" role="alert"></div>
                    <input type="hidden" id="procedureId" name="id">

                    <div class="form-section">
                        <span class="form-section-title"><i class="bi bi-info-circle"></i> Datos del procedimiento</span>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="procNombre">Nombre <span class="required">*</span></label>
                                <input type="text" class="form-control" id="procNombre" name="nombre" maxlength="150" required placeholder="Ej. Limpieza dental profunda">
                                <div class="field-error" data-error="nombre"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="procCategoria">Categoría <span class="required">*</span></label>
                                <select class="form-select" id="procCategoria" name="categoria" required>
                                    <option value="">Selecciona una categoría</option>
                                </select>
                                <div class="field-error" data-error="categoria"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="procPrecio">Precio (USD)</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="procPrecio" name="precio" min="0" step="0.01" value="0.00">
                                </div>
                                <div class="field-error" data-error="precio"></div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="procDuracion">Duración (min)</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="procDuracion" name="duracion_min" min="5" max="480" step="5" value="30">
                                    <span class="input-group-text">min</span>
                                </div>
                                <div class="field-error" data-error="duracion_min"></div>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="procDescripcion">Descripción</label>
                                <textarea class="form-control" id="procDescripcion" name="descripcion" rows="3" maxlength="1000" placeholder="Describe en qué consiste el procedimiento…"></textarea>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch switch-card">
                                    <input class="form-check-input" type="checkbox" role="switch" id="procActivo" name="activo" value="1" checked>
                                    <label class="form-check-label" for="procActivo">
                                        <strong>Procedimiento activo</strong>
                                        <small>Disponible para registrar en las atenciones.</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="procedureSubmit">
                        <span class="btn-label">Guardar procedimiento</span>
                        <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmación de eliminación -->
<div class="modal fade" id="deleteProcedureModal" tabindex="-1" aria-labelledby="deleteProcedureLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content confirm-modal">
            <div class="confirm-icon"><i class="bi bi-trash3"></i></div>
            <h5 id="deleteProcedureLabel">¿Eliminar procedimiento?</h5>
            <p id="deleteProcedureText">Esta acción no se puede deshacer.</p>
            <div class="confirm-actions">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteProcedure">Sí, eliminar</button>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = ['assets/js/procedimientos.js'];
require __DIR__ . '/includes/layout/footer.php';
?>
