<?php
require_once __DIR__ . '/config.php';
$titulo = 'Carrito | M-PARTS';
include __DIR__ . '/includes/header.php';
?>
<main class="wrap cart-page">
  <h1 class="cart-title">Tu carrito</h1>
  <div id="cart-empty" class="empty">Tu carrito está vacío. <a href="index.php">Ver productos</a></div>
  <div id="cart-content" hidden>
    <div id="cart-items" class="cart-items"></div>
    <div class="cart-summary">
      <div><small>Total</small><strong id="cart-total">$0</strong></div>
      <button id="checkout-wa" class="btn wa" type="button">Confirmar y enviar pedido por WhatsApp</button>
      <button id="clear-cart" class="btn ghost" type="button">Vaciar carrito</button>
    </div>
  </div>
</main>
<script>
window.MPARTS_WHATSAPP = <?= json_encode(WHATSAPP) ?>;
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
