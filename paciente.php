<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$patientId = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM pacientes WHERE id = ? LIMIT 1');
$stmt->execute([$patientId]);
$patient = $stmt->fetch();

$pageTitle = $patient ? 'Historia clínica' : 'Paciente no encontrado';
$active = 'pacientes';

if (!$patient) {
    http_response_code(404);
    require __DIR__ . '/includes/layout/header.php';
    ?>
    <div class="empty-state empty-state-lg">
        <span class="empty-icon"><i class="bi bi-person-x"></i></span>
        <h3>No encontramos este paciente</h3>
        <p>El registro que buscas no existe o fue eliminado.</p>
        <a href="pacientes.php" class="btn btn-primary"><i class="bi bi-arrow-left"></i> Volver a pacientes</a>
    </div>
    <?php
    require __DIR__ . '/includes/layout/footer.php';
    exit;
}

$fullName = trim($patient['nombres'] . ' ' . $patient['apellidos']);
$age = age_from_birthdate($patient['fecha_nacimiento']);
$teethGroups = dental_teeth();
?>

<?php require __DIR__ . '/includes/layout/header.php'; ?>

<section class="patient-hero reveal">
    <span class="hero-orb hero-orb-1" aria-hidden="true"></span>
    <span class="hero-orb hero-orb-2" aria-hidden="true"></span>
    <div class="patient-hero-top">
        <div class="patient-identity">
            <span class="avatar avatar-xl avatar-hero"><?= e(initials($fullName)) ?></span>
            <div>
                <span class="hero-eyebrow"><i class="bi bi-folder2-open"></i> Historia clínica · <?= e($patient['codigo']) ?></span>
                <h2><?= e($fullName) ?></h2>
                <div class="hero-meta">
                    <span><i class="bi bi-person"></i> <?= e($age === null ? 'Edad no registrada' : $age . ' años') ?></span>
                    <span><i class="bi bi-gender-ambiguous"></i> <?= e(sexo_label($patient['sexo'])) ?></span>
                    <?php if ($patient['documento']): ?>
                        <span><i class="bi bi-card-text"></i> <?= e($patient['documento']) ?></span>
                    <?php endif; ?>
                    <?php if ($patient['fecha_nacimiento']): ?>
                        <span><i class="bi bi-cake2"></i> <?= e(format_date($patient['fecha_nacimiento'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="hero-actions">
            <a class="btn btn-hero-ghost" href="pacientes.php"><i class="bi bi-arrow-left"></i> Volver</a>
            <div class="dropdown">
                <button class="btn btn-hero-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-file-earmark-pdf"></i> Historia en PDF
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="pdf/historia.php?id=<?= (int) $patient['id'] ?>" target="_blank" rel="noopener"><i class="bi bi-eye"></i> Abrir PDF</a></li>
                    <li><a class="dropdown-item" href="pdf/historia.php?id=<?= (int) $patient['id'] ?>&descargar=1"><i class="bi bi-download"></i> Descargar PDF</a></li>
                </ul>
            </div>
            <button class="btn btn-hero-primary" type="button" id="btnNewRecord">
                <i class="bi bi-plus-lg"></i> Nueva atención
            </button>
        </div>
    </div>

    <div class="patient-hero-bottom">
        <div class="hero-contact">
            <span><i class="bi bi-telephone"></i> <?= e($patient['telefono'] ?: 'Sin teléfono') ?></span>
            <span><i class="bi bi-envelope"></i> <?= e($patient['email'] ?: 'Sin correo') ?></span>
            <span class="hero-address"><i class="bi bi-geo-alt"></i> <?= e($patient['direccion'] ?: 'Sin dirección') ?></span>
        </div>
        <a class="hero-edit" href="pacientes.php?editar=<?= (int) $patient['id'] ?>"><i class="bi bi-pencil-square"></i> Editar datos</a>
    </div>
</section>

<?php if (!empty($patient['alergias'])): ?>
    <div class="allergy-alert reveal" role="alert">
        <span class="allergy-icon"><i class="bi bi-exclamation-triangle-fill"></i></span>
        <div>
            <strong>Alergias registradas</strong>
            <p><?= e($patient['alergias']) ?></p>
        </div>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="mini-stat reveal" style="--delay:.05s">
            <span class="mini-stat-icon stat-icon-teal"><i class="bi bi-journal-medical"></i></span>
            <div><strong id="summaryTotal">—</strong><span>Atenciones</span></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="mini-stat reveal" style="--delay:.1s">
            <span class="mini-stat-icon stat-icon-blue"><i class="bi bi-calendar2-check"></i></span>
            <div><strong id="summaryLast">—</strong><span>Última visita</span></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="mini-stat reveal" style="--delay:.15s">
            <span class="mini-stat-icon stat-icon-amber"><i class="bi bi-calendar-heart"></i></span>
            <div><strong id="summaryNext">—</strong><span>Próxima cita</span></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="mini-stat reveal" style="--delay:.2s">
            <span class="mini-stat-icon stat-icon-violet"><i class="bi bi-clipboard2-pulse"></i></span>
            <div><strong id="summaryProcedures">—</strong><span>Procedimientos</span></div>
        </div>
    </div>
</div>

<div class="patient-tabs-wrap reveal" style="--delay:.22s">
    <ul class="nav nav-tabs patient-tabs" id="patientTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="historyTab" data-bs-toggle="tab" data-bs-target="#historyPane" type="button" role="tab" aria-controls="historyPane" aria-selected="true">
                <i class="bi bi-clock-history"></i> Historia clínica
                <span class="tab-count" id="historyCount">—</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="dataTab" data-bs-toggle="tab" data-bs-target="#dataPane" type="button" role="tab" aria-controls="dataPane" aria-selected="false">
                <i class="bi bi-clipboard2-heart"></i> Datos clínicos
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="chartTab" data-bs-toggle="tab" data-bs-target="#chartPane" type="button" role="tab" aria-controls="chartPane" aria-selected="false">
                <i class="bi bi-grid-3x3-gap"></i> Odontograma
                <span class="tab-count" id="chartCount">—</span>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="historyPane" role="tabpanel" aria-labelledby="historyTab" tabindex="0">
            <div class="timeline-toolbar">
                <div>
                    <h3>Línea de tiempo de atenciones</h3>
                    <p>Consulta los tratamientos, diagnósticos y controles del paciente.</p>
                </div>
                <div class="timeline-toolbar-actions">
                    <select class="form-select form-select-sm" id="historyFilter" aria-label="Filtrar atenciones">
                        <option value="">Todas las atenciones</option>
                        <option value="realizado">Realizadas</option>
                        <option value="pendiente">Pendientes</option>
                        <option value="seguimiento">En seguimiento</option>
                    </select>
                    <button class="btn btn-primary btn-sm" type="button" id="btnNewRecordInline">
                        <i class="bi bi-plus-lg"></i> Registrar atención
                    </button>
                </div>
            </div>

            <div class="timeline" id="historyTimeline">
                <div class="timeline-skeleton">
                    <div class="skeleton-line skeleton"></div>
                    <div class="skeleton-line skeleton"></div>
                    <div class="skeleton-line skeleton"></div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="dataPane" role="tabpanel" aria-labelledby="dataTab" tabindex="0">
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="clinical-card">
                        <div class="clinical-card-head">
                            <span class="clinical-icon clinical-icon-danger"><i class="bi bi-exclamation-triangle"></i></span>
                            <div><h3>Alergias</h3><p>Información crítica para cualquier tratamiento.</p></div>
                        </div>
                        <p class="clinical-value <?= empty($patient['alergias']) ? 'clinical-empty' : '' ?>">
                            <?= e($patient['alergias'] ?: 'Sin alergias registradas.') ?>
                        </p>
                    </div>

                    <div class="clinical-card">
                        <div class="clinical-card-head">
                            <span class="clinical-icon clinical-icon-blue"><i class="bi bi-heart-pulse"></i></span>
                            <div><h3>Antecedentes médicos</h3><p>Condiciones relevantes para la atención odontológica.</p></div>
                        </div>
                        <p class="clinical-value <?= empty($patient['antecedentes']) ? 'clinical-empty' : '' ?>">
                            <?= e($patient['antecedentes'] ?: 'Sin antecedentes registrados.') ?>
                        </p>
                    </div>

                    <div class="clinical-card">
                        <div class="clinical-card-head">
                            <span class="clinical-icon clinical-icon-violet"><i class="bi bi-journal-text"></i></span>
                            <div><h3>Notas internas</h3><p>Observaciones del equipo clínico.</p></div>
                        </div>
                        <p class="clinical-value <?= empty($patient['notas']) ? 'clinical-empty' : '' ?>">
                            <?= e($patient['notas'] ?: 'Sin notas adicionales.') ?>
                        </p>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="clinical-card h-auto">
                        <div class="clinical-card-head">
                            <span class="clinical-icon clinical-icon-teal"><i class="bi bi-person-lines-fill"></i></span>
                            <div><h3>Datos del paciente</h3><p>Información de contacto y registro.</p></div>
                        </div>
                        <dl class="detail-list">
                            <div><dt>Código</dt><dd><?= e($patient['codigo']) ?></dd></div>
                            <div><dt>Documento</dt><dd><?= e($patient['documento'] ?: 'No registrado') ?></dd></div>
                            <div><dt>Fecha de nacimiento</dt><dd><?= e(format_date($patient['fecha_nacimiento'])) ?></dd></div>
                            <div><dt>Edad</dt><dd><?= e($age === null ? 'No registrada' : $age . ' años') ?></dd></div>
                            <div><dt>Sexo</dt><dd><?= e(sexo_label($patient['sexo'])) ?></dd></div>
                            <div><dt>Ocupación</dt><dd><?= e($patient['ocupacion'] ?: 'No registrada') ?></dd></div>
                            <div><dt>Teléfono</dt><dd><?= e($patient['telefono'] ?: 'No registrado') ?></dd></div>
                            <div><dt>Correo</dt><dd><?= e($patient['email'] ?: 'No registrado') ?></dd></div>
                            <div><dt>Dirección</dt><dd><?= e($patient['direccion'] ?: 'No registrada') ?></dd></div>
                            <div><dt>Registrado</dt><dd><?= e(format_date($patient['creado_en'])) ?></dd></div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="chartPane" role="tabpanel" aria-labelledby="chartTab" tabindex="0">
            <div class="odontogram-layout">
                <section class="chart-card">
                    <div class="chart-card-head">
                        <div>
                            <h3>Odontograma actual</h3>
                            <p>Haz clic en una pieza para revisar o registrar su estado clínico.</p>
                        </div>
                        <button class="btn btn-light btn-sm" type="button" id="resetChart">
                            <i class="bi bi-arrow-counterclockwise"></i> Restablecer
                        </button>
                    </div>

                    <div class="dental-chart" id="dentalChart" aria-label="Odontograma de 32 piezas permanentes">
                        <svg class="svg-defs" aria-hidden="true" focusable="false">
                            <symbol id="tooth-shape" viewBox="0 0 24 24">
                                <path d="M12 2.1c-3.6 0-5.3 1.4-8.3 1.4C2.2 3.5 1 4.6 1 6.9c0 3.5 2 5 2.6 8.3.5 3.5.5 9.2 2.9 9.2 2.1 0 1.9-4.4 2.9-6.7.6-1.8 1.2-2.7 3-2.7s2.4.9 3 2.7c1 2.3.8 6.7 2.9 6.7 2.4 0 2.4-5.7 2.9-9.2.6-3.3 2.6-4.8 2.6-8.3 0-2.3-1.2-3.4-2.7-3.4-3 0-4.7-1.4-8.3-1.4Z"/>
                            </symbol>
                        </svg>

                        <div class="dental-arch">
                            <span class="odontogram-label">Arcada superior</span>
                            <div class="dental-row" id="upperTeeth">
                                <div class="chart-skeleton"></div>
                            </div>
                        </div>
                        <div class="dental-arch">
                            <span class="odontogram-label">Arcada inferior</span>
                            <div class="dental-row" id="lowerTeeth">
                                <div class="chart-skeleton"></div>
                            </div>
                        </div>
                    </div>

                    <div class="chart-summary" id="chartSummary">
                        <span><i class="bi bi-activity"></i> Cargando odontograma…</span>
                    </div>
                    <div class="chart-legend" id="chartLegend"></div>
                </section>

                <aside class="tooth-editor" id="toothEditor">
                    <div class="tooth-editor-empty" id="toothEditorEmpty">
                        <span class="empty-icon"><i class="bi bi-hand-index-thumb"></i></span>
                        <h3>Selecciona una pieza</h3>
                        <p>Consulta su estado actual o registra un hallazgo nuevo.</p>
                    </div>

                    <div class="tooth-editor-body d-none" id="toothEditorBody">
                        <div class="tooth-editor-head">
                            <span class="selected-tooth" id="selectedTooth">—</span>
                            <div>
                                <h3>Pieza <span id="selectedToothTitle">—</span></h3>
                                <p id="selectedToothStatus">Sin seleccionar</p>
                            </div>
                        </div>

                        <span class="editor-label">Estado clínico</span>
                        <div class="state-options" id="stateOptions"></div>

                        <label class="form-label" for="toothNotes">Nota clínica <span class="text-muted fw-normal">(opcional)</span></label>
                        <textarea class="form-control" id="toothNotes" rows="3" maxlength="180" placeholder="Ej. Calza de resina, control en 6 meses…"></textarea>

                        <div class="tooth-editor-actions">
                            <button class="btn btn-primary w-100" type="button" id="saveTooth">
                                <i class="bi bi-check2"></i> Guardar pieza
                            </button>
                            <button class="btn btn-light w-100" type="button" id="markHealthy">
                                <i class="bi bi-eraser"></i> Marcar como sana
                            </button>
                        </div>
                        <p class="tooth-editor-hint"><i class="bi bi-info-circle"></i> Los cambios se guardan en la historia clínica del paciente.</p>
                    </div>
                </aside>
            </div>
        </div>
    </div>
</div>

<!-- Modal de atención -->
<div class="modal fade" id="recordModal" tabindex="-1" aria-labelledby="recordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form id="recordForm" novalidate>
                <div class="modal-header">
                    <div>
                        <span class="modal-eyebrow"><?= e($patient['codigo']) ?> · <?= e($fullName) ?></span>
                        <h5 class="modal-title" id="recordModalLabel">Nueva atención</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger d-none" id="recordAlert" role="alert"></div>
                    <input type="hidden" id="recordId" name="id">
                    <input type="hidden" id="recordPatientId" name="paciente_id" value="<?= (int) $patient['id'] ?>">

                    <div class="form-section">
                        <span class="form-section-title"><i class="bi bi-calendar2-check"></i> Datos de la atención</span>
                        <div class="row g-3">
                            <div class="col-sm-4">
                                <label class="form-label" for="recordFecha">Fecha <span class="required">*</span></label>
                                <input type="date" class="form-control" id="recordFecha" name="fecha" value="<?= date('Y-m-d') ?>" required>
                                <div class="field-error" data-error="fecha"></div>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" for="recordHora">Hora</label>
                                <input type="time" class="form-control" id="recordHora" name="hora" value="<?= date('H:i') ?>">
                                <div class="field-error" data-error="hora"></div>
                            </div>
                            <div class="col-sm-4">
                                <label class="form-label" for="recordEstado">Estado</label>
                                <select class="form-select" id="recordEstado" name="estado">
                                    <option value="realizado">Realizado</option>
                                    <option value="pendiente">Pendiente</option>
                                    <option value="seguimiento">En seguimiento</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label" for="recordMotivo">Motivo de la consulta <span class="required">*</span></label>
                                <input type="text" class="form-control" id="recordMotivo" name="motivo" maxlength="255" required placeholder="Ej. Control de rutina y limpieza dental">
                                <div class="field-error" data-error="motivo"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-head">
                            <span class="form-section-title"><i class="bi bi-clipboard2-pulse"></i> Procedimientos realizados</span>
                            <span class="selected-count" id="selectedProcedures">0 seleccionados</span>
                        </div>
                        <div class="procedure-picker">
                            <div class="procedure-picker-head">
                                <div class="toolbar-search toolbar-search-sm">
                                    <i class="bi bi-search"></i>
                                    <input type="search" class="form-control" id="procedureSearch" placeholder="Filtrar procedimientos…" aria-label="Filtrar procedimientos">
                                </div>
                            </div>
                            <div class="procedure-options" id="procedureOptions">
                                <div class="skeleton-line skeleton"></div>
                                <div class="skeleton-line skeleton"></div>
                            </div>
                        </div>
                        <div class="field-error" data-error="procedimientos"></div>
                    </div>

                    <div class="form-section">
                        <div class="form-section-head">
                            <span class="form-section-title"><i class="bi bi-grid-3x3-gap"></i> Odontograma · piezas tratadas</span>
                            <button class="btn btn-link btn-sm p-0 text-decoration-none" type="button" id="clearTeeth">Limpiar selección</button>
                        </div>
                        <div class="odontogram">
                            <span class="odontogram-label">Superior</span>
                            <div class="tooth-row">
                                <?php foreach (array_merge($teethGroups['upper_right'], $teethGroups['upper_left']) as $tooth): ?>
                                    <button type="button" class="tooth" data-tooth="<?= e($tooth) ?>" aria-pressed="false"><?= e($tooth) ?></button>
                                <?php endforeach; ?>
                            </div>
                            <span class="odontogram-label">Inferior</span>
                            <div class="tooth-row">
                                <?php foreach (array_merge($teethGroups['lower_right'], $teethGroups['lower_left']) as $tooth): ?>
                                    <button type="button" class="tooth" data-tooth="<?= e($tooth) ?>" aria-pressed="false"><?= e($tooth) ?></button>
                                <?php endforeach; ?>
                            </div>
                            <div class="odontogram-legend">
                                <span><i></i> Pieza seleccionada</span>
                                <span id="selectedTeethCount">0 piezas</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <span class="form-section-title"><i class="bi bi-journal-medical"></i> Detalle clínico</span>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="recordDiagnostico">Diagnóstico</label>
                                <textarea class="form-control" id="recordDiagnostico" name="diagnostico" rows="3" placeholder="Hallazgos y diagnóstico clínico…"></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="recordTratamiento">Tratamiento aplicado</label>
                                <textarea class="form-control" id="recordTratamiento" name="tratamiento" rows="3" placeholder="Procedimiento y plan indicado…"></textarea>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label" for="recordObservaciones">Observaciones</label>
                                <textarea class="form-control" id="recordObservaciones" name="observaciones" rows="2" placeholder="Indicaciones, evolución o notas para el paciente…"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label" for="recordProxima">Próxima cita</label>
                                <input type="date" class="form-control" id="recordProxima" name="proxima_cita">
                                <div class="field-error" data-error="proxima_cita"></div>
                                <div class="form-hint">Opcional. Aparecerá en la agenda.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="recordSubmit">
                        <span class="btn-label">Guardar atención</span>
                        <span class="spinner-border spinner-border-sm d-none" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirmación de eliminación de atención -->
<div class="modal fade" id="deleteRecordModal" tabindex="-1" aria-labelledby="deleteRecordLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content confirm-modal">
            <div class="confirm-icon confirm-icon-danger"><i class="bi bi-journal-x"></i></div>
            <h5 id="deleteRecordLabel">¿Eliminar atención?</h5>
            <p>Se borrará este registro de la historia clínica de forma permanente.</p>
            <div class="confirm-actions">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteRecord">Sí, eliminar</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirmación de restablecimiento del odontograma -->
<div class="modal fade" id="resetChartModal" tabindex="-1" aria-labelledby="resetChartLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content confirm-modal">
            <div class="confirm-icon"><i class="bi bi-arrow-counterclockwise"></i></div>
            <h5 id="resetChartLabel">¿Restablecer odontograma?</h5>
            <p>Todas las piezas volverán al estado "sano". Las notas clínicas de cada pieza también se eliminarán.</p>
            <div class="confirm-actions">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmResetChart">Sí, restablecer</button>
            </div>
        </div>
    </div>
</div>

<?php
$pageScripts = ['assets/js/paciente.js', 'assets/js/odontograma.js'];
require __DIR__ . '/includes/layout/footer.php';
?>
