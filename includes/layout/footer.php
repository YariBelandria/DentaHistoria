        </main>

        <footer class="app-footer">
            <span>© <?= date('Y') ?> <?= e(app_config('app.name', 'DentaHistoria')) ?>. Todos los derechos reservados.</span>
            <span class="footer-sep"><i class="bi bi-shield-check"></i> Información clínica protegida</span>
        </footer>
    </div>
</div>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
<?php if (!empty($pageScripts)): ?>
    <?php foreach ((array) $pageScripts as $script): ?>
        <script src="<?= e($script) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
