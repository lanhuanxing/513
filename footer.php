<footer class="glass-footer">
  <p>&copy; <?= date('Y') ?> TechStore. All rights reserved.</p>
</footer>

<style>
.glass-footer {
  width: 100%;
  padding: 1.2rem 1rem;
  text-align: center;
  background: rgba(0, 0, 0, .35);
  backdrop-filter: blur(8px);
  color: rgba(255, 255, 255, .8);
  font-size: .85rem;
  position: absolute;
  bottom: 0;
  left: 0;
}
body { position: relative; min-height: 100vh; }
/* 让内容不贴底 */
.main-wrapper { padding-bottom: 4rem; }
</style>