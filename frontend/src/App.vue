<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterView, useRoute } from 'vue-router'

const route = useRoute()
const viewportWidth = ref(typeof window !== 'undefined' ? window.innerWidth : 0)
const isFinePointer = ref(
  typeof window !== 'undefined' ? window.matchMedia('(pointer: fine)').matches : false,
)
const routeRefreshKey = ref(0)
const lastRouteRefreshAt = ref(0)

const isDesktopBlocked = computed(() => viewportWidth.value >= 768 && isFinePointer.value)
const activeRouteKey = computed(() => `${route.fullPath}:${routeRefreshKey.value}`)

function updateDeviceState() {
  viewportWidth.value = window.innerWidth
  isFinePointer.value = window.matchMedia('(pointer: fine)').matches
}

function shouldBlockShortcut(event) {
  const key = String(event.key || '').toLowerCase()
  const ctrlOrMeta = event.ctrlKey || event.metaKey

  if (key === 'f12') {
    return true
  }

  if (ctrlOrMeta && event.shiftKey && ['i', 'j', 'c'].includes(key)) {
    return true
  }

  if (ctrlOrMeta && key === 'u') {
    return true
  }

  return false
}

function onKeydown(event) {
  if (shouldBlockShortcut(event)) {
    event.preventDefault()
    event.stopPropagation()
  }
}

function onContextMenu(event) {
  event.preventDefault()
}

function refreshActiveRoute() {
  const now = Date.now()

  if (now - lastRouteRefreshAt.value < 800) {
    return
  }

  lastRouteRefreshAt.value = now
  routeRefreshKey.value += 1
}

function onVisibilityChange() {
  if (document.visibilityState === 'visible') {
    refreshActiveRoute()
  }
}

function onWindowFocus() {
  if (document.visibilityState === 'visible') {
    refreshActiveRoute()
  }
}

function onPageShow(event) {
  if (event.persisted) {
    refreshActiveRoute()
  }
}

onMounted(() => {
  updateDeviceState()
  window.addEventListener('resize', updateDeviceState)
  window.addEventListener('keydown', onKeydown)
  window.addEventListener('contextmenu', onContextMenu)
  window.addEventListener('focus', onWindowFocus)
  window.addEventListener('pageshow', onPageShow)
  document.addEventListener('visibilitychange', onVisibilityChange)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateDeviceState)
  window.removeEventListener('keydown', onKeydown)
  window.removeEventListener('contextmenu', onContextMenu)
  window.removeEventListener('focus', onWindowFocus)
  window.removeEventListener('pageshow', onPageShow)
  document.removeEventListener('visibilitychange', onVisibilityChange)
})
</script>

<template>
  <main v-if="isDesktopBlocked" class="desktop-blocker">
    <section class="desktop-blocker-card">
      <span class="desktop-blocker-badge">Access Denied</span>
      <h1>Akses hanya untuk perangkat mobile</h1>
      <p>
        Halaman ini tidak bisa dibuka melalui laptop, PC, atau komputer. Silakan gunakan
        handphone untuk melanjutkan.
      </p>
    </section>
  </main>
  <RouterView v-else v-slot="{ Component }">
    <component :is="Component" :key="activeRouteKey" />
  </RouterView>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Outfit:wght@500;700&display=swap');

:root {
  --lake-900: #0a4154;
  --lake-700: #0f6d87;
  --lake-500: #1e9fbe;
  --leaf-700: #2d7d58;
  --leaf-500: #4ab37c;
  --sand-100: #f3fbfd;
  --text-main: #0f2f39;
  --text-soft: #64838d;
  --danger: #b5354c;
  --success: #2c8a5f;
  --card-shadow: 0 20px 45px rgba(15, 91, 112, 0.16);
}

* {
  box-sizing: border-box;
}

html,
body {
  margin: 0;
  padding: 0;
  height: 100%;
  min-height: 100%;
}

body {
  font-family: 'Manrope', 'Segoe UI', sans-serif;
  color: var(--text-main);
}

#app {
  height: 100%;
  min-height: 100vh;
}

.desktop-blocker {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 24px 16px;
  background:
    radial-gradient(circle at top left, rgba(123, 214, 196, 0.22), transparent 34%),
    linear-gradient(180deg, #eef8fc 0%, #def2f5 48%, #d5efeb 100%);
}

.desktop-blocker-card {
  width: 100%;
  max-width: 420px;
  padding: 28px 24px;
  border-radius: 28px;
  text-align: center;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(242, 250, 252, 0.96) 100%);
  border: 1px solid rgba(19, 118, 146, 0.1);
  box-shadow: 0 18px 34px rgba(28, 89, 109, 0.09);
}

.desktop-blocker-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 32px;
  padding: 0 14px;
  border-radius: 999px;
  background: linear-gradient(180deg, #e9f9fb 0%, #def4f8 100%);
  color: #176980;
  font-size: 0.74rem;
  font-weight: 800;
}

.desktop-blocker-card h1 {
  margin: 16px 0 10px;
  color: #123045;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.5rem;
  line-height: 1.15;
}

.desktop-blocker-card p {
  margin: 0;
  color: #57727d;
  font-size: 0.95rem;
  line-height: 1.6;
}

body.auth-body {
  background:
    radial-gradient(circle at 15% 15%, rgba(107, 194, 230, 0.38), transparent 45%),
    radial-gradient(circle at 88% 82%, rgba(88, 184, 128, 0.32), transparent 48%),
    linear-gradient(165deg, #e6f8ff 0%, #d5f3ea 52%, #e7f9ff 100%);
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 24px 14px;
}

body.is-modal-open {
  overflow: hidden;
}

.deposit-alert,
.deposit-history {
  width: 100%;
  box-sizing: border-box;
  padding: 18px 16px;
  border-radius: 24px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(242, 250, 252, 0.96) 100%);
  border: 1px solid rgba(19, 118, 146, 0.1);
  box-shadow: 0 18px 34px rgba(28, 89, 109, 0.09);
}

.deposit-alert p {
  margin: 0;
  font-size: 0.88rem;
  line-height: 1.55;
}

.deposit-alert p + p {
  margin-top: 6px;
}

.deposit-alert.is-error {
  border-color: rgba(190, 87, 87, 0.16);
  color: #8a3535;
  background: linear-gradient(180deg, rgba(255, 249, 249, 0.98) 0%, rgba(253, 239, 239, 0.96) 100%);
}

.deposit-alert.is-success {
  border-color: rgba(40, 145, 117, 0.16);
  color: #1e6d55;
  background: linear-gradient(180deg, rgba(247, 255, 251, 0.98) 0%, rgba(234, 250, 242, 0.96) 100%);
}

.deposit-alert.is-autohide {
  transition: opacity 0.32s ease, transform 0.32s ease, max-height 0.32s ease, margin 0.32s ease, padding 0.32s ease;
}

.deposit-alert.is-autohide.is-hidden {
  opacity: 0;
  transform: translateY(-10px);
  max-height: 0;
  overflow: hidden;
  margin: 0;
  padding-top: 0;
  padding-bottom: 0;
}

.deposit-section-head,
.deposit-history-top,
.deposit-history-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.deposit-section-head h2 {
  margin: 8px 0 0;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.18rem;
  color: #143548;
}

.deposit-section-head p {
  margin: 6px 0 0;
  font-size: 0.76rem;
  color: #738a96;
}

.deposit-history-item,
.deposit-history-empty {
  padding: 14px;
  border-radius: 18px;
  background: linear-gradient(180deg, rgba(245, 252, 253, 0.98) 0%, rgba(234, 246, 249, 0.96) 100%);
  border: 1px solid rgba(18, 117, 144, 0.1);
}

.deposit-history-top span,
.deposit-history-bottom span,
.deposit-history-empty p {
  font-size: 0.74rem;
  color: #6c8390;
}

.deposit-history-top strong {
  display: block;
  margin-top: 6px;
  font-size: 0.88rem;
  color: #133446;
}

.deposit-status {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 12px;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 800;
}

.deposit-status.is-pending {
  background: rgba(255, 188, 67, 0.16);
  color: #b7780b;
}

.deposit-status.is-paid {
  background: rgba(61, 185, 133, 0.16);
  color: #167251;
}

.deposit-status.is-cancelled {
  background: rgba(222, 103, 103, 0.14);
  color: #984141;
}

.deposit-history-list {
  display: grid;
  gap: 10px;
  margin-top: 16px;
}

.deposit-history-item {
  display: grid;
  gap: 12px;
}

@media (max-width: 640px) {
  .deposit-alert,
  .deposit-history {
    padding: 14px 12px;
    border-radius: 22px;
  }
}

.deposit-form {
  display: grid;
  gap: 16px;
}

.deposit-field {
  display: grid;
  gap: 8px;
}

.deposit-field-label {
  font-size: 0.76rem;
  font-weight: 700;
  color: #31586a;
}

.deposit-input {
  width: 100%;
  min-height: 54px;
  padding: 0 16px;
  border: 1px solid rgba(21, 122, 148, 0.12);
  border-radius: 16px;
  background: #f8fcfd;
  color: #133549;
  font-size: 1rem;
  font-weight: 700;
  outline: none;
  box-sizing: border-box;
}

.deposit-input:focus {
  border-color: rgba(25, 165, 160, 0.45);
  box-shadow: 0 0 0 4px rgba(73, 203, 190, 0.14);
}

.deposit-flow-note {
  display: grid;
  gap: 4px;
  padding: 14px 15px;
  border-radius: 18px;
  background: linear-gradient(135deg, rgba(20, 122, 154, 0.08) 0%, rgba(37, 193, 164, 0.12) 100%);
  color: #24556a;
}

.deposit-flow-note strong {
  font-size: 0.76rem;
}

.deposit-flow-note span {
  font-size: 0.8rem;
  line-height: 1.5;
}

.deposit-submit-btn {
  min-height: 52px;
  border: 0;
  border-radius: 18px;
  background: linear-gradient(135deg, #16b9aa 0%, #2d8dd5 100%);
  color: #fff;
  font-size: 0.96rem;
  font-weight: 800;
  letter-spacing: 0.01em;
  box-shadow: 0 18px 28px rgba(26, 129, 152, 0.2);
}

.profile-page-body {
  background:
    radial-gradient(circle at top left, rgba(123, 214, 196, 0.22), transparent 34%),
    linear-gradient(180deg, #eef8fc 0%, #def2f5 48%, #d5efeb 100%);
}

.profile-main {
  padding-bottom: 184px;
}

.profile-shell {
  display: grid;
  gap: 12px;
}

.profile-top-card {
  border-radius: 28px;
  background: linear-gradient(180deg, #10c29e 0%, #0ea7bb 42%, #f7fbfc 42%, #ffffff 100%);
  box-shadow: 0 20px 38px rgba(20, 95, 123, 0.14);
  overflow: hidden;
}

.profile-top-head {
  display: grid;
  grid-template-columns: 72px minmax(0, 1fr);
  gap: 14px;
  padding: 18px 16px 16px;
  align-items: center;
}

.profile-avatar {
  display: grid;
  place-items: center;
  width: 72px;
  aspect-ratio: 1;
  border-radius: 24px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(233, 250, 247, 0.95) 100%);
  color: #0d6880;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.8rem;
  font-weight: 800;
  box-shadow: 0 14px 26px rgba(10, 88, 98, 0.18);
}

.profile-identity {
  min-width: 0;
  color: #ffffff;
}

.profile-badge-row {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 8px;
}

.profile-member-badge,
.profile-id-badge {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #f5ffff;
  font-size: 0.66rem;
  font-weight: 800;
}

.profile-identity h1 {
  margin: 0;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.24rem;
  line-height: 1.08;
}

.profile-identity p {
  margin: 6px 0 0;
  color: rgba(245, 255, 255, 0.92);
  font-size: 0.8rem;
  line-height: 1.4;
}

.profile-stats-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 8px;
  padding: 0 16px 14px;
}

.profile-stat-card {
  padding: 12px 10px;
  border-radius: 18px;
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(16, 112, 134, 0.08);
  text-align: center;
}

.profile-stat-card strong {
  display: block;
  color: #123045;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.98rem;
  line-height: 1.1;
  white-space: normal;
  overflow-wrap: anywhere;
}

.profile-stat-card span {
  display: block;
  margin-top: 6px;
  color: #68838f;
  font-size: 0.64rem;
  font-weight: 700;
  line-height: 1.3;
}

.profile-balance-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 0 16px 16px;
}

.profile-balance-copy span {
  display: block;
  color: #6f8793;
  font-size: 0.68rem;
  font-weight: 700;
}

.profile-balance-copy strong {
  display: block;
  margin-top: 5px;
  color: #103245;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.32rem;
  line-height: 1.05;
}

.profile-balance-actions {
  display: flex;
  gap: 8px;
}

.profile-balance-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 72px;
  min-height: 34px;
  padding: 0 12px;
  border-radius: 999px;
  text-decoration: none;
  font-size: 0.72rem;
  font-weight: 800;
}

.profile-balance-btn.is-primary {
  background: linear-gradient(90deg, #10c39a 0%, #1aa9b4 100%);
  color: #ffffff;
}

.profile-balance-btn.is-secondary {
  background: linear-gradient(180deg, #fff7ec 0%, #ffefd5 100%);
  color: #9b6313;
}

.profile-module {
  padding: 14px 12px;
  border-radius: 24px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(240, 249, 251, 0.97) 100%);
  border: 1px solid rgba(18, 112, 134, 0.1);
  box-shadow: 0 14px 30px rgba(16, 88, 108, 0.08);
}

.profile-module-title {
  padding: 0 2px 12px;
}

.profile-module-title span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 12px;
  border-left: 4px solid #0fc09f;
  color: #113246;
  font-size: 0.8rem;
  font-weight: 800;
}

.profile-module-grid {
  display: grid;
  gap: 10px 8px;
}

.profile-module-grid.is-five {
  grid-template-columns: repeat(5, minmax(0, 1fr));
}

.profile-module-grid.is-six {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.profile-module-grid.is-four {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.profile-module-item {
  text-decoration: none;
  color: #123045;
  display: grid;
  justify-items: center;
  gap: 6px;
  text-align: center;
}

.profile-module-icon {
  width: 50px;
  height: 42px;
  border-radius: 14px;
  background: linear-gradient(180deg, #f4fbfd 0%, #ebf7fb 100%);
  border: 1px solid rgba(16, 112, 134, 0.1);
  display: grid;
  place-items: center;
}

.profile-module-icon svg {
  width: 21px;
  height: 21px;
  fill: none;
  stroke: #1389ab;
  stroke-width: 1.9;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.profile-module-item span:last-child {
  font-size: 0.68rem;
  font-weight: 700;
  line-height: 1.2;
}

.profile-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}

.profile-summary-card {
  padding: 12px 11px;
  border-radius: 18px;
  background: linear-gradient(180deg, #f5fbfd 0%, #eef8fb 100%);
  border: 1px solid rgba(18, 112, 134, 0.08);
}

.profile-summary-card strong {
  display: block;
  color: #123045;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.98rem;
  line-height: 1.1;
}

.profile-summary-card span {
  display: block;
  margin-top: 6px;
  color: #6b8590;
  font-size: 0.66rem;
  font-weight: 700;
}

.profile-safe-logout {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  min-height: 48px;
  margin-top: 12px;
  border-radius: 18px;
  background: linear-gradient(90deg, #1ea1c4 0%, #12c49a 100%);
  color: #ffffff;
  text-decoration: none;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.9rem;
  font-weight: 800;
  border: 0;
  cursor: pointer;
  box-shadow: 0 14px 24px rgba(13, 150, 153, 0.18);
}

.profile-logout-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 38px;
  padding: 0 14px;
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(255, 248, 248, 0.98) 0%, rgba(249, 237, 237, 0.96) 100%);
  border: 1px solid rgba(190, 93, 93, 0.14);
  color: #8b3b3b;
  text-decoration: none;
  box-shadow: 0 10px 18px rgba(129, 76, 76, 0.08);
}

.profile-logout-btn svg {
  width: 18px;
  height: 18px;
  fill: none;
  stroke: currentColor;
  stroke-linecap: round;
  stroke-linejoin: round;
  stroke-width: 1.85;
}

.profile-logout-btn span {
  font-size: 0.74rem;
  font-weight: 700;
  white-space: nowrap;
}

.profile-footer {
  gap: 10px;
}
</style>
