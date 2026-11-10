<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$user = current_user();
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Buenos días' : ($hour < 19 ? 'Buenas tardes' : 'Buenas noches');
$cleanName = preg_replace('/^(Dra?\.|Sr\.|Sra\.)\s+/iu', '', $user['nombre']);
$firstName = explode(' ', trim($cleanName))[0] ?? 'Doctor';

$pageTitle = 'Panel principal';
$active = 'dashboard';
require __DIR__ . '/includes/layout/header.php';
?>

<div class="page-heading reveal">
    <div>
        <span class="eyebrow"><i class="bi bi-activity"></i> Resumen de la clínica</span>
        <h2><?= e($greeting) ?>, <?= e($firstName) ?></h2>
        <p>Este es el estado de tu clínica hoy. Todo listo para seguir cuidando sonrisas.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-primary" href="procedimientos.php">
            <i class="bi bi-clipboard2-pulse"></i> Procedimientos
        </a>
        <a class="btn btn-primary" href="pacientes.php?nuevo=1">
            <i class="bi bi-person-plus"></i> Nuevo paciente
        </a>
    </div>
</div>

<div class="row g-3 g-xl-4 mb-4 stats-grid">
    <div class="col-sm-6 col-xl-3">
        <article class="stat-card reveal" style="--delay:.05s">
            <div class="stat-top">
                <span class="stat-icon stat-icon-teal"><i class="bi bi-people-fill"></i></span>
                <span class="stat-trend" id="patientsTrend"></span>
            </div>
            <p class="stat-label">Pacientes registrados</p>
            <strong class="stat-value skeleton" id="statPacientes">000</strong>
            <span class="stat-sub" id="statPacientesSub">Cargando información…</span>
        </article>
    </div>
    <div class="col-sm-6 col-xl-3">
        <article class="stat-card reveal" style="--delay:.1s">
            <div class="stat-top">
                <span class="stat-icon stat-icon-blue"><i class="bi bi-calendar2-check-fill"></i></span>
                <span class="stat-trend" id="recordsTrend"></span>
            </div>
            <p class="stat-label">Atenciones del mes</p>
            <strong class="stat-value skeleton" id="statAtenciones">00</strong>
            <span class="stat-sub" id="statAtencionesSub">Cargando información…</span>
        </article>
    </div>
    <div class="col-sm-6 col-xl-3">
        <article class="stat-card reveal" style="--delay:.15s">
            <div class="stat-top">
                <span class="stat-icon stat-icon-violet"><i class="bi bi-clipboard2-pulse-fill"></i></span>
                <span class="stat-trend stat-trend-neutral"><i class="bi bi-check2-circle"></i> Activos</span>
            </div>
            <p class="stat-label">Procedimientos</p>
            <strong class="stat-value skeleton" id="statProcedimientos">00</strong>
            <span class="stat-sub">Disponibles en el catálogo</span>
        </article>
    </div>
    <div class="col-sm-6 col-xl-3">
        <article class="stat-card reveal" style="--delay:.2s">
            <div class="stat-top">
                <span class="stat-icon stat-icon-amber"><i class="bi bi-calendar-heart-fill"></i></span>
                <span class="stat-trend stat-trend-neutral"><i class="bi bi-clock"></i> Agenda</span>
            </div>
            <p class="stat-label">Próximas citas</p>
            <strong class="stat-value skeleton" id="statProximas">00</strong>
            <span class="stat-sub">Pacientes por atender</span>
        </article>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <section class="panel-card reveal" style="--delay:.25s">
            <div class="panel-head">
                <div>
                    <span class="panel-kicker">Actividad clínica</span>
                    <h3>Atenciones por mes</h3>
                </div>
                <span class="soft-badge"><i class="bi bi-graph-up-arrow"></i> Últimos 6 meses</span>
            </div>
            <div class="chart-wrap">
                <canvas id="monthlyChart" aria-label="Gráfico de atenciones por mes" role="img"></canvas>
                <div class="chart-empty d-none" id="monthlyEmpty">
                    <i class="bi bi-bar-chart"></i>
                    <p>Aún no hay atenciones registradas en este período.</p>
                </div>
            </div>
        </section>
    </div>
    <div class="col-xl-4">
        <section class="panel-card reveal h-100" style="--delay:.3s">
            <div class="panel-head">
                <div>
                    <span class="panel-kicker">Agenda</span>
                    <h3>Próximas citas</h3>
                </div>
                <span class="icon-chip"><i class="bi bi-calendar2-week"></i></span>
            </div>
            <div class="upcoming-list" id="upcomingList">
                <div class="skeleton-line skeleton"></div>
                <div class="skeleton-line skeleton"></div>
                <div class="skeleton-line skeleton"></div>
            </div>
        </section>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-7">
        <section class="panel-card reveal" style="--delay:.35s">
            <div class="panel-head">
                <div>
                    <span class="panel-kicker">Pacientes</span>
                    <h3>Registros recientes</h3>
                </div>
                <a class="link-soft" href="pacientes.php">Ver todos <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-hover data-table mb-0">
                    <thead>
                        <tr>
                            <th>Paciente</th>
                            <th>Código</th>
                            <th>Última visita</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="recentPatients">
                        <tr><td colspan="4"><div class="skeleton-line skeleton"></div></td></tr>
                        <tr><td colspan="4"><div class="skeleton-line skeleton"></div></td></tr>
                        <tr><td colspan="4"><div class="skeleton-line skeleton"></div></td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-xl-5">
        <section class="panel-card reveal h-100" style="--delay:.4s">
            <div class="panel-head">
                <div>
                    <span class="panel-kicker">Distribución</span>
                    <h3>Procedimientos por área</h3>
                </div>
                <span class="soft-badge soft-badge-blue"><i class="bi bi-pie-chart"></i> 12 meses</span>
            </div>
            <div class="category-chart">
                <div class="category-canvas">
                    <canvas id="categoryChart" aria-label="Distribución de procedimientos por categoría" role="img"></canvas>
                </div>
                <div class="category-legend" id="categoryLegend"></div>
            </div>
        </section>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-12">
        <section class="panel-card reveal" style="--delay:.45s">
            <div class="panel-head">
                <div>
                    <span class="panel-kicker">Últimos movimientos</span>
                    <h3>Actividad clínica reciente</h3>
                </div>
                <span class="icon-chip"><i class="bi bi-clock-history"></i></span>
            </div>
            <div class="activity-list" id="recentRecords">
                <div class="skeleton-line skeleton"></div>
                <div class="skeleton-line skeleton"></div>
            </div>
        </section>
    </div>
</div>

<?php
$pageScripts = [
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js',
    'assets/js/dashboard.js',
];
require __DIR__ . '/includes/layout/footer.php';
?>
