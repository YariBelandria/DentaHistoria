<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pageTitle = 'Pacientes';
$active = 'pacientes';
require __DIR__ . '/includes/layout/header.php';
?>

<div class="page-heading reveal">
    <div>
        <span class="eyebrow"><i class="bi bi-people"></i> Gestión de pacientes</span>
        <h2>Pacientes</h2>
        <p>Consulta, registra y mantén al día la información de cada paciente.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-primary" href="procedimientos.php">
            <i class="bi bi-clipboard2-pulse"></i> Procedimientos
        </a>
        <button class="btn btn-primary" type="button" id="btnNewPatient">
            <i class="bi bi-person-plus"></i> Nuevo paciente
        </button>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="mini-stat reveal" style="--delay:.05s">
            <span class="mini-stat-icon stat-icon-teal"><i class="bi bi-people-fill"></i></span>
            <div><strong id="patientStatTotal">—</strong><span>Pacientes</span></div>
        </div>
    </div>
    <div class="col-4">
        <div class="mini-stat reveal" style="--delay:.1s">
            <span class="mini-stat-icon stat-icon-green"><i class="bi bi-person-plus-fill"></i></span>
            <div><strong id="patientStatNuevos">—</strong><span>Nuevos este mes</span></div>
        </div>
    </div>
    <div class="col-4">
        <div class="mini-stat reveal" style="--delay:.15s">
            <span class="mini-stat-icon stat-icon-rose"><i class="bi bi-exclamation-triangle-fill"></i></span>
            <div><strong id="patientStatAlergias">—</strong><span>Con alergias</span></div>
        </div>
    </div>
</div>

<section class="panel-card reveal" style="--delay:.2s">
    <div class="panel-head panel-head-toolbar">
        <div class="toolbar-search">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control" id="patientSearch" placeholder="Buscar por nombre, cédula, código o teléfono…" aria-label="Buscar paciente">
        </div>
        <div class="toolbar-filters">
            <select class="form-select" id="patientSex" aria-label="Filtrar por sexo">
                <option value="">Todos</option>
                <option value="F">Femenino</option>
                <option value="M">Masculino</option>
                <option value="O">No especificado</option>
            </select>
            <select class="form-select" id="patientOrder" aria-label="Ordenar pacientes">
                <option value="recientes">Más recientes</option>
                <option value="nombre">Nombre A–Z</option>
                <option value="ultima">Última visita</option>
                <option value="codigo">Código</option>
            </select>
            <button class="btn btn-icon btn-light" type="button" id="patientRefresh" title="Actualizar" aria-label="Actualizar lista">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table align-middle table-hover data-table mb-0" id="patientsTable">
            <thead>
                <tr>
                    <th>Paciente</th>
                    <th class="d-none d-md-table-cell">Documento</th>
                    <th class="d-none d-lg-table-cell">Contacto</th>
                    <th class="d-none d-sm-table-cell">Última visita</th>
                    <th class="d-none d-xl-table-cell text-center">Atenciones</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="patientsBody">
                <?php for ($i = 0; $i < 6; $i++): ?>
                    <tr>
                        <td colspan="6"><div class="skeleton-line skeleton"></div></td>
                    </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <div class="card-footer-bar">
        <span class="table-info" id="patientsInfo">Cargando pacientes…</span>
        <nav aria-label="Paginación de pacientes">
            <ul class="pagination pagination-sm mb-0" id="patientsPagination"></ul>
        </nav>
    </div>
</section>

<!-- Modal de paciente -->
<div class="modal fade" id="patientModal" tabindex="-1" aria-labelledby="patientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="patientForm" novalidate>
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow">Expediente del paciente</span>
                        <h5 class="modal-title" id="patientModalLabel">Nuevo paciente</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="patientAlert" role="alert"></div>
                    <input type="hidden" id="patientId" name="id">

                    <div class="form-section">
                        <span class="form-section-title"><i class="bi bi-person-vcard"></i> Datos personales</span>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="patientNombres">Nombres <span class="required">*</span></label>
                                <input type="text" class="form-control" id="patientNombres" name="nombres" maxlength="100" required placeholder="Ej. María Fernanda">
                                <div class="field-error" data-error="nombres"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="patientApellidos">Apellidos <span class="required">*</span></label>
                                <input type="text" class="form-control" id="patientApellidos" name="apellidos" maxlength="100" required placeholder="Ej. González Rivas">
                                <div class="field-error" data-error="apellidos"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="patientDocumento">Cédula / Documento</label>
                                <input type="text" class="form-control" id="patientDocumento" name="documento" maxlength="30" placeholder="V-12345678">
                                <div class="field-error" data-error="documento"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="patientNacimiento">Fecha de nacimiento</label>
                                <input type="date" class="form-control" id="patientNacimiento" name="fecha_nacimiento" max="<?= date('Y-m-d') ?>">
                                <div class="field-error" data-error="fecha_nacimiento"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="patientSexo">Sexo</label>
                                <select class="form-select" id="patientSexo" name="sexo">
                                    <option value="O">No especificado</option>
                                    <option value="F">Femenino</option>
                                    <option value="M">Masculino</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="patientOcupacion">Ocupación</label>
                                <input type="text" class="form-control" id="patientOcupacion" name="ocupacion" maxlength="100" placeholder="Ej. Docente">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="patientDireccion">Dirección</label>
                                <input type="text" class="form-control" id="patientDireccion" name="direccion" maxlength="255" placeholder="Calle, edificio, ciudad">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <span class="form-section-title"><i class="bi bi-telephone"></i> Datos de contacto</span>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="patientTelefono">Teléfono</label>
                                <input type="tel" class="form-control" id="patientTelefono" name="telefono" maxlength="30" placeholder="+58 412 000 0000">
                                <div class="field-error" data-error="telefono"></div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="patientEmail">Correo electrónico</label>
                                <input type="email" class="form-control" id="patientEmail" name="email" maxlength="150" placeholder="paciente@correo.com">
                                <div class="field-error" data-error="email"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <span class="form-section-title"><i class="bi bi-heart-pulse"></i> Información clínica</span>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="patientAlergias">Alergias</label>
                                <textarea class="form-control" id="patientAlergias" name="alergias" rows="2" placeholder="Medicamentos, látex, anestésicos…"></textarea>
                                <div class="form-hint">Se mostrarán como alerta en la historia clínica.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="patientAntecedentes">Antecedentes médicos</label>
                                <textarea class="form-control" id="patientAntecedentes" name="antecedentes" rows="2" placeholder="Hipertensión, diabetes, anticoagulantes…"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="patientNotas">Notas internas</label>
                                <textarea class="form-control" id="patientNotas" name="notas" rows="2" placeholder="Observaciones generales del paciente…"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="patientSubmit">
                        <span class="btn-label">Guardar paciente</span>
                        <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmación de eliminación -->
<div class="modal fade" id="deletePatientModal" tabindex="-1" aria-labelledby="deletePatientLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content confirm-modal">
            <div class="confirm-icon confirm-icon-danger"><i class="bi bi-person-x"></i></div>
            <h5 id="deletePatientLabel">¿Eliminar paciente?</h5>
            <p id="deletePatientText">Se eliminará también toda su historia clínica. Esta acción no se puede deshacer.</p>
            <div class="confirm-actions">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeletePatient">Sí, eliminar</button>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = ['assets/js/pacientes.js'];
require __DIR__ . '/includes/layout/footer.php';
?>
