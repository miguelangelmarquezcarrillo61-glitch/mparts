<footer class="foot"><div class="wrap foot-grid">
  <div><div class="logo"><b>M-</b>PARTS</div><p class="muted">Repuestos y accesorios para motos. Precios en pesos colombianos (COP).</p></div>
  <div><h4>Contacto</h4><?php foreach (CONTACTO as $k => $v): ?><p class="muted"><?= h($k) ?>: <?= h($v) ?></p><?php endforeach; ?></div>
  <div><h4>Síguenos</h4><div class="social">
    <?php foreach (SOCIAL as $n => $u): if ($u): ?><a href="<?= h($u) ?>" target="_blank" rel="noopener"><?= h($n) ?></a><?php endif; endforeach; ?>
    <a href="https://wa.me/<?= WHATSAPP ?>?text=<?= urlencode('Hola M-PARTS, ¿qué deseas consultar o pedir?') ?>" target="_blank" rel="noopener">WhatsApp</a>
  </div></div>
</div>
<div class="wrap copy">Proyecto Integrador · FESC · <?= date('Y') ?> · <a href="admin/">Acceso administrador</a></div>
<script src="assets/js/cart.js?v=1"></script>
</footer>
</body></html>
