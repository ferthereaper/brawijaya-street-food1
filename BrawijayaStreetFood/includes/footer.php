</main>

<footer class="foot">
    <div class="foot-info">
        <div>
            <strong>Alamat</strong>
            <p><?= e(ALAMAT_SINGKAT) ?></p>
        </div>
        <div>
            <strong>Jam Operasional</strong>
            <p><?= e(JAM_BUKA) ?></p>
        </div>
        <div>
            <strong>Hubungi Kami</strong>
            <p class="ikon-baris">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#25d366" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.2 14.2c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.2-4.6-4-4.7-4.2-.1-.2-1.1-1.5-1.1-2.8 0-1.3.7-2 1-2.2.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.9 2.1c.1.2.1.4 0 .6l-.4.6c-.2.2-.3.4-.1.7.2.3.8 1.3 1.7 2.1 1.200 1 2.100 1.300 2.400 1.500.3.100.5.100.7-.1.2-.2.8-.9 1-1.200.2-.3.4-.2.7-.1l2 .9c.3.1.5.2.6.4.1.2.1.8-.2 1.500Z"/></svg>
                <?= e(KONTAK_WA) ?>
            </p>
            <p class="ikon-baris">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#fff" d="M6.6 10.8a15 15 0 0 0 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.100.4 2.300.6 3.600.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1A17 17 0 0 1 3 4c0-.6.4-1 1-1h3.500c.6 0 1 .4 1 1 0 1.300.2 2.500.6 3.600.1.300 0 .7-.2 1l-2.300 2.200Z"/></svg>
                <?= e(KONTAK_TELP) ?>
            </p>
            <p class="ikon-baris kecil-teks">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="#fff" d="M3 5h18a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Zm9 7.200L4.500 7v1.500l7.500 5 7.500-5V7L12 12.200Z"/></svg>
                <?= e(KONTAK_EMAIL) ?>
            </p>
        </div>
    </div>
    <div class="foot-copy">&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</div>
</footer>
</div>
<script src="<?= e(url('assets/js/app.js')) ?>"></script>
</body>
</html>
