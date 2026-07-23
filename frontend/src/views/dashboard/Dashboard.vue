<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import AppShell from '../../components/layout/AppShell.vue'
import SupportModal from '../../components/modals/SupportModal.vue'
import {
  defaultHeaderActions,
  getDashboardData,
  marketNoticeItems,
  standardFooterItems,
} from '../../data/mockData'
import { getProducts } from '../../services/api'

const isSupportOpen = ref(false)
const { menuItems } = getDashboardData()
const fishList = ref([])
const isLoadingProducts = ref(false)
const productError = ref('')

function extractProducts(payload) {
  if (Array.isArray(payload)) {
    return payload
  }

  if (!payload || typeof payload !== 'object') {
    return []
  }

  if (Array.isArray(payload.results)) {
    const nestedResults = payload.results.flatMap((item) => extractProducts(item))
    return nestedResults.length ? nestedResults : payload.results
  }

  return []
}

function slugifyProductName(value) {
  return String(value || '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
}

function formatPrice(value) {
  const amount = Number(value)

  if (!Number.isFinite(amount)) {
    return '-'
  }

  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(amount)
}

function formatRate(value, type) {
  if (value === undefined || value === null || value === '') {
    return '-'
  }

  return type === 'percentage' ? `${value}%` : String(value)
}

function formatContractPrice(product) {
  const price = Number(product.price)

  if (!Number.isFinite(price)) {
    return '-'
  }

  return formatPrice(price)
}

function buildUnlockRule(product) {
  if (product.require_min_rank_enabled && product.min_required_rank) {
    return `Min rank ${product.min_required_rank}`
  }

  if (product.purchase_limit) {
    return `Limit ${product.purchase_limit}x purchase`
  }

  return 'Available now.'
}

function mapProductToCard(product) {
  return {
    key: product.id,
    name: product.name || 'Unnamed Product',
    image: product.image || '/logo.png',
    imageAlt: product.name || 'Product image',
    labels: [product.golongan || 'Product', product.status === 1 ? 'Active' : 'Ready'],
    unlockRule: buildUnlockRule(product),
    rate: formatRate(product.profit_rate, product.profit_type),
    contractPrice: formatContractPrice(product),
    care: `${product.duration || 0} days`,
    to: { name: 'aquarium' },
  }
}

async function loadProducts() {
  isLoadingProducts.value = true
  productError.value = ''

  try {
    const { ok, data } = await getProducts()

    if (!ok) {
      productError.value = 'Produk gagal dimuat.'
      return
    }

    const products = extractProducts(data)
      .slice()
      .sort((left, right) => Number(left?.price || 0) - Number(right?.price || 0))

    fishList.value = products.slice(0, 4).map(mapProductToCard)
  } catch (error) {
    productError.value =
      error instanceof Error ? error.message : 'Tidak bisa terhubung ke server produk.'
  } finally {
    isLoadingProducts.value = false
  }
}

function onHeaderAction(actionKey) {
  if (actionKey === 'support') {
    isSupportOpen.value = true
  }
}

function onMenuButtonClick(item) {
  if (item.type === 'button') {
    isSupportOpen.value = true
  }
}

onMounted(() => {
  loadProducts()
})
</script>

<template>
  <AppShell
    body-class=""
    main-class=""
    :header-actions="defaultHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="standardFooterItems"
    active-footer-key="home"
    @action="onHeaderAction"
  >
    <section class="dashboard-card">
      <video class="dashboard-video" autoplay muted loop playsinline preload="metadata">
        <source src="/map/car-gowisevid.mp4" type="video/mp4" />
        Your browser does not support the video tag.
      </video>
    </section>

    <section class="dashboard-menu-grid" aria-label="Dashboard menu">
      <component
        :is="item.to ? RouterLink : item.type === 'button' ? 'button' : 'a'"
        v-for="item in menuItems"
        :key="item.label"
        class="dashboard-menu-item"
        :class="{ 'dashboard-menu-button': item.type === 'button' }"
        :to="item.to"
        :href="item.href"
        :type="item.type === 'button' ? 'button' : undefined"
        @click="onMenuButtonClick(item)"
      >
        <span class="dashboard-menu-icon" :class="item.tone" aria-hidden="true" v-html="item.icon"></span>
        <span class="dashboard-menu-text">{{ item.label }}</span>
      </component>
    </section>

    <section class="dashboard-invite-card" aria-label="friend invitation banner">
      <div class="dashboard-invite-copy">Invite Your Friends, Earn Together Fast earning!</div>
      <img src="/invite.png" alt="friend invitation" class="dashboard-invite-image" />
    </section>

    <section class="dashboard-fish-list" aria-label="fish list">
      <p v-if="isLoadingProducts" class="dashboard-fish-empty">Memuat produk...</p>
      <p v-else-if="productError" class="dashboard-fish-empty">{{ productError }}</p>
      <p v-else-if="!fishList.length" class="dashboard-fish-empty">Belum ada produk aktif.</p>
      <article v-for="fish in fishList" :key="fish.key || slugifyProductName(fish.name)" class="dashboard-fish-item">
        <RouterLink :to="fish.to" class="dashboard-fish-link">
          <div class="dashboard-fish-media">
            <img :src="fish.image" :alt="fish.imageAlt" class="dashboard-fish-image" />
          </div>

          <div class="dashboard-fish-content">
            <div class="dashboard-fish-labels" aria-hidden="true">
 
              <span class="dashboard-fish-label is-accent">{{ fish.labels[1] }}</span>
            </div>

            <h3 class="dashboard-fish-title">{{ fish.name }}</h3>

            <p class="dashboard-fish-unlock-rule">{{ fish.unlockRule }}</p>
            <p class="dashboard-fish-rate">Return {{ fish.rate }}</p>

            <div class="dashboard-fish-stats">
              <div class="dashboard-fish-stat">
                <div class="dashboard-fish-value">{{ fish.contractPrice }}</div>
                <div class="dashboard-fish-note">Harga kontrak</div>
              </div>

      
            </div>

            <span class="dashboard-fish-button">Mulai Kontrak</span>
          </div>
        </RouterLink>
      </article>
    </section>

    <SupportModal v-model="isSupportOpen" />
  </AppShell>
</template>

<style>
.dashboard-card {
  width: 100%;
  max-width: 430px;
  border-radius: 16px;
  padding: 3px;
  background: #ffffff;
  border: 1px solid rgba(27, 126, 145, 0.18);
  box-shadow: var(--card-shadow);
  overflow: hidden;
}

.dashboard-video {
  display: block;
  width: 100%;
  height: 146px;
  object-fit: cover;
  border-radius: 14px;
  background: #ffffff;
}

.dashboard-menu-grid {
  width: 100%;
  max-width: 430px;
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 10px 8px;
}

.dashboard-menu-item {
  text-decoration: none;
  color: #123045;
  display: grid;
  justify-items: center;
  gap: 5px;
}

.dashboard-menu-button {
  width: 100%;
  padding: 0;
  border: 0;
  background: transparent;
  font: inherit;
  cursor: pointer;
}

.dashboard-menu-icon {
  width: 46px;
  height: 34px;
  border-radius: 10px;
  background: #f1f4f6;
  border: 1px solid #e4eaed;
  display: grid;
  place-items: center;
}

.dashboard-menu-icon svg {
  width: 20px;
  height: 20px;
  fill: none;
  stroke-width: 1.9;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.dashboard-menu-icon.tone-blue svg {
  stroke: #1b89b0;
}

.dashboard-menu-icon.tone-green svg {
  stroke: #2f9b74;
}

.dashboard-menu-text {
  font-size: 0.8rem;
  font-weight: 700;
  line-height: 1.1;
  text-align: center;
}

.dashboard-invite-card {
  width: 100%;
  max-width: 430px;
  border-radius: 16px;
  padding: 3px;
  background: #ffffff;
  border: 1px solid rgba(27, 126, 145, 0.18);
  box-shadow: var(--card-shadow);
  overflow: hidden;
  position: relative;
}

.dashboard-invite-image {
  display: block;
  width: 100%;
  height: 105px;
  object-fit: cover;
  border-radius: 14px;
}

.dashboard-invite-copy {
  position: absolute;
  left: 20px;
  top: 12px;
  right: 90px;
  z-index: 2;
  color: #ffffff;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.02rem;
  line-height: 1.1;
  font-weight: 700;
  text-shadow: 0 2px 10px rgba(6, 29, 43, 0.5);
}

.dashboard-fish-list {
  width: 100%;
  max-width: 430px;
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  align-items: start;
}

.dashboard-fish-item {
  border-radius: 18px;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.44) 0%, rgba(243, 252, 255, 0.52) 100%),
    url('/arkaplan.jpg') right center / auto 100% no-repeat,
    url('/arkaplan.jpg') center / cover no-repeat;
  border: 1px solid rgba(20, 108, 130, 0.12);
  box-shadow: 0 12px 26px rgba(18, 92, 113, 0.12);
  overflow: hidden;
}

.dashboard-fish-link {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 8px;
  padding: 8px;
  text-decoration: none;
  color: inherit;
  background: linear-gradient(90deg, rgba(255, 255, 255, 0.18) 0%, rgba(255, 255, 255, 0.06) 100%);
}

.dashboard-fish-media {
  position: relative;
  border-radius: 14px;
  overflow: hidden;
  aspect-ratio: 3 / 2;
  width: 100%;
  min-height: 0;
  background: linear-gradient(180deg, rgba(241, 251, 245, 0.92) 0%, rgba(225, 245, 235, 0.9) 100%);
  align-self: stretch;
}

.dashboard-fish-image {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
}

.dashboard-fish-content {
  min-width: 0;
  padding: 10px 10px 11px;
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(248, 253, 255, 0.88) 0%, rgba(238, 249, 252, 0.92) 100%);
  border: 1px solid rgba(18, 107, 129, 0.12);
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.55);
  backdrop-filter: blur(3px);
}

.dashboard-fish-labels {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.dashboard-fish-label {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 18px;
  padding: 0 6px;
  border-radius: 999px;
  font-size: 0.56rem;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.dashboard-fish-label.is-primary {
  background: rgba(16, 124, 144, 0.14);
  color: #10637d;
}

.dashboard-fish-label.is-accent {
  background: rgba(0, 0, 0, 0.12);
  color: #123045;
}

.dashboard-fish-title {
  margin: 8px 0 4px;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.92rem;
  color: #123045;
  line-height: 1.1;
  letter-spacing: 0.2px;
}

.dashboard-fish-unlock-rule {
  margin: 0 0 7px;
  color: #3e5963;
  font-size: 0.62rem;
  font-weight: 700;
  line-height: 1.4;
}

.dashboard-fish-rate {
  margin: 0 0 9px;
  color: #3e5963;
  font-size: 0.62rem;
  font-weight: 700;
  line-height: 1.4;
}

.dashboard-fish-stats {
  display: grid;
  gap: 8px;
}

.dashboard-fish-stat {
  border-radius: 12px;
  padding: 8px;
  background: rgba(255, 255, 255, 0.72);
  border: 1px solid rgba(18, 107, 129, 0.1);
}

.dashboard-fish-value {
  color: #dd3e37;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.86rem;
  font-weight: 700;
  line-height: 1;
}

.dashboard-fish-note {
  margin-top: 2px;
  color: #4b646d;
  font-size: 0.58rem;
  line-height: 1.2;
  font-weight: 600;
}

.dashboard-fish-button {
  display: block;
  border-radius: 10px;
  background: linear-gradient(90deg, #0fcb9e 0%, #1db8a2 100%);
  color: #ffffff;
  text-align: center;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.78rem;
  font-weight: 700;
  line-height: 2.5;
  box-shadow: 0 10px 18px rgba(22, 178, 145, 0.18);
}

@media (max-width: 380px) {
  .dashboard-menu-grid {
    gap: 7px 6px;
  }

  .dashboard-menu-icon {
    width: 42px;
    height: 31px;
  }

  .dashboard-menu-text {
    font-size: 0.74rem;
  }

  .dashboard-invite-copy {
    left: 16px;
    right: 70px;
    top: 10px;
    font-size: 0.88rem;
  }

  .dashboard-fish-link {
    gap: 7px;
    padding: 7px;
  }

  .dashboard-fish-media {
    border-radius: 12px;
  }

  .dashboard-fish-label {
    font-size: 0.52rem;
    min-height: 17px;
    padding: 0 5px;
  }

  .dashboard-fish-content {
    padding: 9px 9px 10px;
    border-radius: 14px;
  }

  .dashboard-fish-title {
    font-size: 0.84rem;
  }

  .dashboard-fish-unlock-rule {
    font-size: 0.58rem;
    margin-bottom: 7px;
  }

  .dashboard-fish-rate {
    font-size: 0.58rem;
    margin-bottom: 7px;
  }

  .dashboard-fish-value {
    font-size: 0.8rem;
  }

  .dashboard-fish-note {
    font-size: 0.54rem;
  }

  .dashboard-fish-button {
    font-size: 0.72rem;
    line-height: 2.25;
  }
}
</style>
