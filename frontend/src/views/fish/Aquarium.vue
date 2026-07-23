<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'
import AppShell from '../../components/layout/AppShell.vue'
import {
  marketNoticeItems,
  standardFooterItems,
  defaultHeaderActions,
} from '../../data/mockData'
import { getProducts, purchaseProduct } from '../../services/api'

const state = ref({
  items: [],
})
const isLoading = ref(false)
const loadError = ref('')
const isPurchasingKey = ref(null)
const purchaseError = ref('')
const purchaseSuccess = ref('')
let popupTimerId = null

function clearPurchasePopup() {
  purchaseError.value = ''
  purchaseSuccess.value = ''

  if (popupTimerId) {
    window.clearTimeout(popupTimerId)
    popupTimerId = null
  }
}

function showPurchasePopup(type, message) {
  clearPurchasePopup()

  if (type === 'error') {
    purchaseError.value = message
  } else {
    purchaseSuccess.value = message
  }

  popupTimerId = window.setTimeout(() => {
    clearPurchasePopup()
  }, 4000)
}

function formatCurrency(value) {
  const amount = Number(value)

  if (!Number.isFinite(amount)) {
    return 'Rp0'
  }

  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amount)
}

function extractProductsPage(payload) {
  if (Array.isArray(payload)) {
    return { results: payload, next: null }
  }

  if (!payload || typeof payload !== 'object') {
    return { results: [], next: null }
  }

  return {
    results: Array.isArray(payload.results) ? payload.results : [],
    next: payload.next ?? null,
  }
}

function mapProductToAquariumItem(product) {
  const durationDays = Number(product?.duration) || 0
  const profitRate = Number(product?.profit_rate || 0)
  return {
    key: String(product?.id),
    productId: product?.id ?? null,
    name: product?.name || 'Unnamed Product',
    image: product?.image || '/logo.png',
    imageAlt: product?.name || 'Product image',
    tags: ['Contract', `${durationDays} hari`],
    levelLabel: `${profitRate.toFixed(0)}`,
    productCycle: `${durationDays} hari`,
    contractPrice: formatCurrency(product?.price),
    description: product?.description || product?.specifications || '-',
  }
}

function resolvePurchaseErrorMessage(payload) {
  if (payload && typeof payload === 'object') {
    const nonFieldErrors = payload.non_field_errors
    if (Array.isArray(nonFieldErrors) && nonFieldErrors.length) {
      return String(nonFieldErrors[0])
    }

    const productErrors = payload.product_id
    if (Array.isArray(productErrors) && productErrors.length) {
      return String(productErrors[0])
    }

    const detail = payload.detail
    if (detail) {
      return String(detail)
    }
  }

  if (typeof payload === 'string' && payload.trim()) {
    return payload
  }

  return 'Aktifkan kontrak gagal.'
}

async function activateContract(item) {
  clearPurchasePopup()

  const productId = Number(item?.productId)
  if (!Number.isFinite(productId)) {
    showPurchasePopup('error', 'Produk tidak valid.')
    return
  }

  isPurchasingKey.value = item.key

  try {
    const { ok, data } = await purchaseProduct({ product_id: productId, quantity: 1 })

    if (!ok) {
      showPurchasePopup('error', resolvePurchaseErrorMessage(data))
      return
    }

    showPurchasePopup('success', 'Kontrak berhasil diaktifkan.')
    await loadAquarium()
  } catch (error) {
    showPurchasePopup(
      'error',
      error instanceof Error ? error.message : 'Tidak bisa terhubung ke server pembelian.',
    )
  } finally {
    isPurchasingKey.value = null
  }
}

async function loadAquarium() {
  isLoading.value = true
  loadError.value = ''

  try {
    const items = []
    let page = 1
    let hasNext = true
    let safety = 0

    while (hasNext && safety < 50) {
      const { ok, data } = await getProducts({ page })

      if (!ok) {
        loadError.value = 'Data produk gagal dimuat.'
        state.value.items = []
        return
      }

      const { results, next } = extractProductsPage(data)
      items.push(...results)

      if (!next || !results.length) {
        hasNext = false
      } else {
        page += 1
      }

      safety += 1
    }

    state.value.items = items
      .slice()
      .sort((left, right) => Number(left?.price || 0) - Number(right?.price || 0))
      .map(mapProductToAquariumItem)
  } catch (error) {
    loadError.value =
      error instanceof Error ? error.message : 'Tidak bisa terhubung ke server produk.'
    state.value.items = []
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadAquarium()
})

onBeforeUnmount(() => {
  if (popupTimerId) {
    window.clearTimeout(popupTimerId)
  }
})
</script>

<template>
  <AppShell
    body-class="aquarium-page-body"
    main-class="aquarium-main"
    :header-actions="defaultHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="standardFooterItems"
    active-footer-key="aquarium"
  >
    <section v-if="purchaseError || purchaseSuccess" class="aquarium-popup-wrap" aria-live="polite">
      <div class="aquarium-popup" :class="purchaseError ? 'is-error' : 'is-success'">
        <p>{{ purchaseError || purchaseSuccess }}</p>
        <button type="button" class="aquarium-popup-close" @click="clearPurchasePopup">Tutup</button>
      </div>
    </section>

    <section v-if="isLoading" class="aquarium-empty">
      <h2>Memuat Produk</h2>
      <p>Data produk sedang diambil dari server.</p>
    </section>

    <section v-else-if="loadError" class="aquarium-empty">
      <h2>Gagal Memuat Produk</h2>
      <p>{{ loadError }}</p>
    </section>

    <section v-else-if="state.items.length" class="aquarium-grid" aria-label="Fish in the aquarium">
      <article v-for="item in state.items" :key="item.key" class="aquarium-card">
        <img :src="item.image" :alt="item.imageAlt" class="aquarium-card-image" />

        <div class="aquarium-card-body">
          <div class="aquarium-card-tags" aria-hidden="true">
            <span v-for="tag in item.tags" :key="tag" class="aquarium-card-tag">{{ tag }}</span>
          </div>

          <h2>{{ item.name }}</h2>
          <span class="aquarium-card-level">Rp{{ item.levelLabel }}</span>

          <div class="aquarium-card-meta">
            <div class="aquarium-card-stat">
              <span>Siklus produk</span>
              <strong>{{ item.productCycle }}</strong>
            </div>
            <div class="aquarium-card-stat">
              <span>Harga kontrak</span>
              <strong>{{ item.contractPrice }}</strong>
            </div>
          </div>

          <div class="aquarium-market-action">
            <p class="aquarium-market-timer">
              {{ item.description }}
            </p>

            <button
              type="button"
              class="aquarium-market-button"
              :disabled="isPurchasingKey === item.key"
              @click="activateContract(item)"
            >
              {{ isPurchasingKey === item.key ? 'Memproses...' : 'Aktifkan kontrak' }}
            </button>
          </div>
        </div>
      </article>
    </section>

    <section v-else class="aquarium-empty">
      <h2>Belum ada produk</h2>
      <p>Produk akan tampil di sini ketika sudah tersedia.</p>
    </section>
  </AppShell>
</template>

<style>
.aquarium-page-body {
  min-height: 100vh;
}

.aquarium-popup-wrap {
  position: fixed;
  inset: 72px 14px auto;
  z-index: 50;
  display: flex;
  justify-content: center;
  pointer-events: none;
}

.aquarium-popup {
  width: min(100%, 430px);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px;
  border-radius: 18px;
  box-shadow: 0 16px 30px rgba(14, 78, 97, 0.18);
  pointer-events: auto;
}

.aquarium-popup.is-success {
  background: linear-gradient(180deg, rgba(247, 255, 251, 0.98) 0%, rgba(234, 250, 242, 0.96) 100%);
  border: 1px solid rgba(40, 145, 117, 0.16);
  color: #1e6d55;
}

.aquarium-popup.is-error {
  background: linear-gradient(180deg, rgba(255, 249, 249, 0.98) 0%, rgba(253, 239, 239, 0.96) 100%);
  border: 1px solid rgba(190, 87, 87, 0.16);
  color: #8a3535;
}

.aquarium-popup p {
  margin: 0;
  font-size: 0.84rem;
  line-height: 1.45;
}

.aquarium-popup-close {
  border: 0;
  border-radius: 999px;
  min-height: 34px;
  padding: 0 12px;
  background: rgba(255, 255, 255, 0.72);
  color: inherit;
  font-size: 0.75rem;
  font-weight: 800;
  cursor: pointer;
  white-space: nowrap;
}

.aquarium-main {
  align-items: stretch;
}

.aquarium-grid,
.aquarium-empty {
  width: 100%;
  max-width: 430px;
}

.aquarium-grid {
  display: grid;
  gap: 12px;
}

.aquarium-card {
  overflow: hidden;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(20, 108, 130, 0.12);
  box-shadow: 0 16px 28px rgba(12, 86, 104, 0.12);
}

.aquarium-card-image {
  display: block;
  width: 100%;
  aspect-ratio: 3 / 2;
  object-fit: cover;
}

.aquarium-card-body {
  padding: 14px 14px 16px;
  display: grid;
  gap: 10px;
}

.aquarium-card-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.aquarium-card-tag {
  display: inline-flex;
  align-items: center;
  min-height: 22px;
  padding: 0 9px;
  border-radius: 999px;
  background: linear-gradient(180deg, #edf9ff 0%, #e3f4fb 100%);
  border: 1px solid rgba(18, 125, 152, 0.12);
  color: #1d5668;
  font-size: 0.62rem;
  font-weight: 700;
}

.aquarium-card-body h2 {
  margin: 0;
  color: #123045;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.28rem;
}

.aquarium-card-level {
  display: inline-flex;
  align-items: center;
  width: fit-content;
  min-height: 24px;
  padding: 0 10px;
  border-radius: 999px;
  background: rgba(18, 190, 146, 0.12);
  color: #187458;
  font-size: 0.66rem;
  font-weight: 700;
}

.aquarium-card-meta {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.aquarium-card-stat {
  padding: 12px 12px 13px;
  border-radius: 16px;
  background: linear-gradient(180deg, rgba(245, 252, 255, 0.98) 0%, rgba(236, 249, 252, 0.95) 100%);
  border: 1px solid rgba(17, 115, 142, 0.1);
}

.aquarium-card-stat span {
  display: block;
  color: #56717a;
  font-size: 0.7rem;
}

.aquarium-card-stat strong {
  display: block;
  margin-top: 5px;
  color: #123045;
  font-size: 0.92rem;
  line-height: 1.4;
}

.aquarium-market-action {
  display: grid;
  gap: 7px;
}

.aquarium-market-timer {
  margin: 0;
  color: #5b727a;
  font-size: 0.74rem;
  line-height: 1.4;
}

.aquarium-market-timer.is-active {
  color: #bc611d;
  font-weight: 700;
}

.aquarium-market-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  min-height: 44px;
  border-radius: 15px;
  border: 0;
  background: linear-gradient(90deg, #10c39a 0%, #1aa9b4 100%);
  color: #ffffff;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.88rem;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 12px 22px rgba(15, 177, 146, 0.22);
  cursor: pointer;
}

.aquarium-market-button:disabled {
  background: linear-gradient(90deg, #a7b9c0 0%, #8ea2ac 100%);
  box-shadow: none;
  cursor: default;
}

.aquarium-empty {
  padding: 18px 16px;
  border-radius: 24px;
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(20, 108, 130, 0.12);
  box-shadow: 0 16px 28px rgba(12, 86, 104, 0.12);
  text-align: center;
}

.aquarium-empty h2 {
  margin: 0 0 8px;
  color: #123045;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.28rem;
}

.aquarium-empty p {
  margin: 0;
  color: #4d6973;
  font-size: 0.84rem;
  line-height: 1.5;
}

.aquarium-empty-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  margin-top: 14px;
  min-height: 46px;
  border-radius: 16px;
  background: linear-gradient(90deg, #10c39a 0%, #1aa9b4 100%);
  color: #ffffff;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.92rem;
  font-weight: 700;
  text-decoration: none;
  box-shadow: 0 12px 22px rgba(15, 177, 146, 0.22);
}

@media (max-width: 380px) {
  .aquarium-popup-wrap {
    inset: 68px 10px auto;
  }

  .aquarium-popup {
    padding: 12px 14px;
    border-radius: 16px;
  }

  .aquarium-popup p {
    font-size: 0.78rem;
  }

  .aquarium-card {
    border-radius: 20px;
  }

  .aquarium-card-body {
    padding: 12px 12px 14px;
    gap: 9px;
  }

  .aquarium-card-tag {
    min-height: 20px;
    padding: 0 8px;
    font-size: 0.58rem;
  }

  .aquarium-card-body h2 {
    font-size: 1.08rem;
  }

  .aquarium-card-level {
    min-height: 22px;
    font-size: 0.6rem;
  }

  .aquarium-card-stat {
    padding: 10px;
    border-radius: 14px;
  }

  .aquarium-card-stat span {
    font-size: 0.64rem;
  }

  .aquarium-card-stat strong {
    font-size: 0.82rem;
  }

  .aquarium-market-timer {
    font-size: 0.68rem;
  }

  .aquarium-market-button {
    min-height: 40px;
    font-size: 0.8rem;
  }

  .aquarium-empty {
    padding: 16px 14px;
    border-radius: 20px;
  }

  .aquarium-empty h2 {
    font-size: 1.1rem;
  }

  .aquarium-empty p {
    font-size: 0.76rem;
  }

  .aquarium-empty-button {
    min-height: 42px;
    font-size: 0.84rem;
  }
}
</style>
