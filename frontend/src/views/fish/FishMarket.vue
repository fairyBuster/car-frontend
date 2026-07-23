<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import AppShell from '../../components/layout/AppShell.vue'
import {
  backProfileHeaderActions,
  marketFooterItems,
  marketNoticeItems,
} from '../../data/mockData'
import { getInvestmentInterestTransactions, getInvestments } from '../../services/api'

const route = useRoute()
const state = ref({
  ownedFish: [],
  feed: [],
})
const selectedFishKey = ref(typeof route.query.fish === 'string' ? route.query.fish : '')
const isLoading = ref(false)
const loadError = ref('')
const isLoadingFeed = ref(false)
const feedError = ref('')
const now = ref(Date.now())
const imageObjectUrls = new Set()

function normalizeImageSource(value) {
  if (typeof value !== 'string') {
    return ''
  }

  const trimmed = value.trim()
  if (!trimmed) {
    return ''
  }

  const unwrapped = trimmed.replace(/^`+|`+$/g, '').trim()
  if (!unwrapped) {
    return ''
  }

  if (/^https?:\/\//i.test(unwrapped)) {
    const safeUrl = unwrapped.replace(/^http:\/\//i, 'https://')
    try {
      const parsed = new URL(safeUrl)
      if (parsed.pathname.startsWith('/media/')) {
        return `${parsed.pathname}${parsed.search}${parsed.hash}`
      }
    } catch {
      return safeUrl
    }
    return safeUrl
  }

  if (unwrapped.startsWith('/media/')) {
    return unwrapped
  }

  return unwrapped
}

function clearImageObjectUrls() {
  for (const url of imageObjectUrls) {
    URL.revokeObjectURL(url)
  }
  imageObjectUrls.clear()
}

async function loadImageAsBlobUrl(value) {
  const src = normalizeImageSource(value)
  if (!src) {
    return null
  }

  try {
    const response = await fetch(src)
    if (!response.ok) {
      return null
    }

    const blob = await response.blob()
    const objectUrl = URL.createObjectURL(blob)
    imageObjectUrls.add(objectUrl)
    return objectUrl
  } catch {
    return null
  }
}

const timer = window.setInterval(() => {
  now.value = Date.now()
}, 1000)

const selectedFish = computed(
  () => state.value.ownedFish.find((item) => item.key === selectedFishKey.value) ?? state.value.ownedFish[0] ?? null,
)

function remainingSeconds(item) {
  return Math.max(0, Math.ceil((new Date(item.marketAvailableAt).getTime() - now.value) / 1000))
}

function formatCountdown(seconds) {
  const safe = Math.max(0, seconds)
  const hours = String(Math.floor(safe / 3600)).padStart(2, '0')
  const minutes = String(Math.floor((safe % 3600) / 60)).padStart(2, '0')
  const secs = String(safe % 60).padStart(2, '0')
  return `${hours}:${minutes}:${secs}`
}

function formatCurrency(value) {
  const amount = Number(value)

  if (!Number.isFinite(amount)) {
    return '0'
  }

  return new Intl.NumberFormat('id-ID', {
    maximumFractionDigits: 0,
  }).format(amount)
}

function formatProfitRate(value) {
  const amount = Number(value)

  if (!Number.isFinite(amount)) {
    return '0.00%'
  }

  return `${amount.toFixed(2)}%`
}

function estimatedProfit(item) {
  return formatCurrency(item.totalPotentialProfit || 0)
}

function dailyProfitAmount(item) {
  return formatCurrency(item.dailyProfit || 0)
}

function resolveInvestmentStatusLabel(status) {
  const normalizedStatus = String(status || '').trim().toUpperCase()

  if (!normalizedStatus) {
    return '-'
  }

  if (normalizedStatus === 'ACTIVE') {
    return 'Active running'
  }

  return normalizedStatus
    .toLowerCase()
    .split('_')
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ')
}

function profitCountdownLabel(item) {
  const seconds = remainingSeconds(item)
  return seconds === 0 ? 'Profit siap diterima' : `Menuju profit ${formatCountdown(seconds)}`
}

function selectFish(key) {
  selectedFishKey.value = key
}

function sellFish(fish) {
  if (!fish || remainingSeconds(fish) > 0 || fish.isMarketSold) {
    return
  }

  fish.isMarketSold = true
}

function extractInvestments(payload) {
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

function parseBoolean(value) {
  if (typeof value === 'boolean') {
    return value
  }

  if (typeof value === 'string') {
    return value.toLowerCase() === 'true'
  }

  return false
}

function formatFeedDate(value) {
  if (!value) {
    return '-'
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return String(value)
  }

  const yyyy = date.getFullYear()
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const dd = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd} ${hours}:${minutes}`
}

function resolveMarketAvailableAt(investment) {
  if (parseBoolean(investment.can_claim_today)) {
    return new Date(now.value).toISOString()
  }

  return investment.next_claim_time_calculated || investment.next_claim_time || investment.created_at
}

function mapInvestmentToOwnedFish(investment) {
  const imageUrl = normalizeImageSource(investment.product_image)
  return {
    key: String(investment.id),
    name: investment.product_name || 'Unnamed Investment',
    imageUrl,
    image: '/logo.png',
    imageAlt: investment.product_name || 'Investment image',
    marketRate: Number(investment.profit_rate) || 0,
    marketBalance: Number(investment.total_amount) || 0,
    dailyProfit: Number(investment.daily_profit) || 0,
    totalPotentialProfit: Number(investment.total_potential_profit) || 0,
    status: String(investment.status || ''),
    isMarketSold: investment.status === 'COMPLETED',
    marketAvailableAt: resolveMarketAvailableAt(investment),
  }
}

function mapInvestmentToFeedItem(investment) {
  return {
    date: formatFeedDate(investment.created_at),
    amount: Number(investment.daily_profit || 0).toFixed(2),
    fish: investment.product_name || 'Investment',
    status: 'Receive',
    note: `Menerima keuntungan kargo hari ini (${Number(investment.profit_rate || 0).toFixed(2)}%).`,
    createdAt: investment.created_at,
  }
}

function extractInterestTransactions(payload) {
  if (Array.isArray(payload)) {
    return payload
  }

  if (!payload || typeof payload !== 'object') {
    return []
  }

  return Array.isArray(payload.results) ? payload.results : []
}

function mapInterestTransactionToFeedItem(transaction) {
  return {
    date: formatFeedDate(transaction.created_at),
    amount: Number(transaction.amount || 0).toFixed(2),
    fish: transaction.product_name || selectedFish.value?.name || 'Produk',
    status: 'Receive',
    note: `Menerima keuntungan kargo hari ini (${Number(selectedFish.value?.marketRate || 0).toFixed(2)}%).`,
    createdAt: transaction.created_at,
  }
}

async function loadInvestments() {
  isLoading.value = true
  loadError.value = ''

  try {
    clearImageObjectUrls()
    const items = []
    let page = 1
    let hasNext = true
    let safety = 0

    while (hasNext && safety < 50) {
      const { ok, data } = await getInvestments({ status: 'ACTIVE', page })

      if (!ok) {
        loadError.value = 'Data investasi gagal dimuat.'
        state.value.ownedFish = []
        state.value.feed = []
        return
      }

      const { results, next } = extractInvestments(data)
      items.push(...results)

      if (!next || !results.length) {
        hasNext = false
      } else {
        page += 1
      }

      safety += 1
    }

    const mappedFish = items.map(mapInvestmentToOwnedFish)
    state.value.ownedFish = await Promise.all(
      mappedFish.map(async (fish) => {
        const blobUrl = await loadImageAsBlobUrl(fish.imageUrl)
        return {
          ...fish,
          image: blobUrl || fish.imageUrl || '/logo.png',
        }
      }),
    )

    if (!selectedFishKey.value && state.value.ownedFish.length) {
      selectedFishKey.value = state.value.ownedFish[0].key
    }
  } catch (error) {
    loadError.value =
      error instanceof Error ? error.message : 'Tidak bisa terhubung ke server investasi.'
    state.value.ownedFish = []
    state.value.feed = []
  } finally {
    isLoading.value = false
  }
}

async function loadInterestTransactions() {
  if (!selectedFish.value?.key) {
    state.value.feed = []
    return
  }

  isLoadingFeed.value = true
  feedError.value = ''

  try {
    const { ok, data } = await getInvestmentInterestTransactions(selectedFish.value.key)

    if (!ok) {
      feedError.value = 'Aktivitas kargo gagal dimuat.'
      state.value.feed = []
      return
    }

    const interestTransactions = extractInterestTransactions(data)
    state.value.feed = interestTransactions.length
      ? interestTransactions.map(mapInterestTransactionToFeedItem)
      : [mapInvestmentToFeedItem(selectedFish.value)]
  } catch (error) {
    feedError.value =
      error instanceof Error ? error.message : 'Tidak bisa terhubung ke server aktivitas kargo.'
    state.value.feed = []
  } finally {
    isLoadingFeed.value = false
  }
}

onMounted(() => {
  loadInvestments()
})

watch(selectedFish, (value) => {
  if (value) {
    loadInterestTransactions()
  } else {
    state.value.feed = []
  }
})

onBeforeUnmount(() => {
  window.clearInterval(timer)
  clearImageObjectUrls()
})
</script>

<template>
  <AppShell
    body-class="fish-market-page-body"
    main-class="fish-market-main"
    :header-actions="backProfileHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="marketFooterItems"
    active-footer-key="market"
  >
    <section v-if="isLoading" class="fish-market-own-card">
      <div class="fish-market-own-body">
        <h1>Memuat market...</h1>
        <p class="fish-market-own-timer">Data investasi sedang diambil.</p>
      </div>
    </section>

    <section v-else-if="loadError" class="fish-market-own-card">
      <div class="fish-market-own-body">
        <h1>Gagal memuat market</h1>
        <p class="fish-market-own-timer">{{ loadError }}</p>
      </div>
    </section>

    <section v-else-if="state.ownedFish.length" class="fish-market-list">
      <article v-for="fish in state.ownedFish" :key="fish.key" class="fish-market-own-card">
        <div class="fish-market-own-media">
          <img :src="fish.image" :alt="fish.imageAlt" class="fish-market-own-image" />
        </div>

        <div class="fish-market-own-body">
          <div class="fish-market-own-tags" aria-hidden="true">
            <span class="fish-market-own-tag">Automatic</span>
            <span class="fish-market-own-tag">24hours</span>
          </div>

          <h1>{{ fish.name }}</h1>
          <span class="fish-market-own-status" :class="fish.status === 'ACTIVE' ? 'is-ready' : 'is-waiting'">
            {{ resolveInvestmentStatusLabel(fish.status) }}
          </span>

          <div class="fish-market-own-meta">
            <div class="fish-market-own-stat">
              <span>Persentase profit</span>
              <strong>{{ formatProfitRate(fish.marketRate) }}</strong>
            </div>
            <div class="fish-market-own-stat">
              <span>Keuntungan harian</span>
              <strong>Rp{{ dailyProfitAmount(fish) }}</strong>
            </div>
            <div class="fish-market-own-stat">
              <span>Estimasi keuntungan</span>
              <strong>Rp{{ estimatedProfit(fish) }}</strong>
            </div>
          </div>

          <p class="fish-market-own-timer">Kargo Anda sedang bekerja sesuai dengan jadwalnya untuk menghasilkan keuntungan.</p>

          <button
            type="button"
            class="fish-market-sell-button"
            :class="remainingSeconds(fish) > 0 ? 'is-locked is-timer' : 'is-listed is-timer'"
          >
            {{ profitCountdownLabel(fish) }}
          </button>

          <div class="fish-market-status-stream">
            <p class="fish-market-status-line is-muted">
              Kargo ini bersifat permanen dan seluruh pekerjaan dijalankan secara otomatis.
            </p>
          </div>
        </div>
      </article>
    </section>

   

  </AppShell>
</template>

<style>
.fish-market-page-body {
  min-height: 100vh;
}

.fish-market-main {
  align-items: stretch;
}

.fish-market-list {
  display: grid;
  gap: 12px;
}

.fish-market-own-card,
.fish-market-feed-shell,
.fish-market-owned-list {
  width: 100%;
  max-width: 430px;
}

.fish-market-own-card {
  overflow: hidden;
  border-radius: 26px;
  background:
    radial-gradient(circle at top right, rgba(255, 214, 125, 0.22), transparent 38%),
    linear-gradient(140deg, rgba(9, 76, 100, 0.98) 0%, rgba(11, 114, 133, 0.96) 52%, rgba(15, 156, 145, 0.94) 100%);
  box-shadow: 0 18px 34px rgba(9, 69, 87, 0.2);
  color: #f4feff;
}

.fish-market-own-media {
  position: relative;
}

.fish-market-own-image {
  display: block;
  width: 100%;
  aspect-ratio: 16 / 10;
  object-fit: cover;
}

.fish-market-own-body {
  padding: 8px 10px 9px;
  display: grid;
  gap: 6px;
}

.fish-market-own-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
}

.fish-market-own-tag {
  display: inline-flex;
  align-items: center;
  min-height: 18px;
  padding: 0 8px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  border: 1px solid rgba(255, 255, 255, 0.15);
  color: #f3fcff;
  font-size: 0.54rem;
  font-weight: 700;
}

.fish-market-own-body h1 {
  margin: 0;
  color: #ffffff;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.1rem;
  line-height: 1;
}

.fish-market-own-status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  min-height: 19px;
  padding: 0 8px;
  border-radius: 999px;
  font-size: 0.54rem;
  font-weight: 700;
}

.fish-market-own-status.is-ready {
  background: rgba(255, 255, 255, 0.18);
  color: #ffffff;
}

.fish-market-own-status.is-waiting {
  background: rgba(255, 209, 120, 0.18);
  color: #fff3d5;
}

.fish-market-own-meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(84px, 1fr));
  gap: 6px;
}

.fish-market-own-stat {
  padding: 8px 9px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.12);
}

.fish-market-own-stat span {
  display: block;
  color: rgba(240, 252, 255, 0.88);
  font-size: 0.56rem;
}

.fish-market-own-stat strong {
  display: block;
  margin-top: 3px;
  color: #ffffff;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.8rem;
}

.fish-market-own-timer {
  margin: 0;
  color: rgba(241, 252, 255, 0.92);
  font-size: 0.62rem;
  line-height: 1.3;
}

.fish-market-own-timer.is-active {
  color: #ffe3b6;
  font-weight: 700;
}

.fish-market-sell-button {
  width: 100%;
  height: 34px;
  border: 0;
  border-radius: 12px;
  background: linear-gradient(90deg, #ffb44a 0%, #f18842 100%);
  color: #ffffff;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.72rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 8px 16px rgba(241, 136, 66, 0.18);
}

.fish-market-sell-button.is-timer {
  cursor: default;
}

.fish-market-sell-button.is-locked,
.fish-market-sell-button:disabled {
  background: linear-gradient(90deg, rgba(176, 195, 201, 0.9) 0%, rgba(140, 164, 171, 0.94) 100%);
  box-shadow: none;
  cursor: not-allowed;
}

.fish-market-sell-button.is-listed {
  background: linear-gradient(90deg, #13b191 0%, #1891b8 100%);
  box-shadow: 0 12px 24px rgba(24, 145, 184, 0.22);
}

.fish-market-status-stream {
  display: grid;
  gap: 5px;
}

.fish-market-status-line {
  margin: 0;
  padding: 7px 9px;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.12);
  color: #f2fbff;
  font-size: 0.62rem;
  line-height: 1.3;
  border: 1px solid rgba(255, 255, 255, 0.12);
}

.fish-market-status-line.is-muted {
  color: rgba(243, 252, 255, 0.8);
}

.fish-market-status-line.is-info {
  background: rgba(255, 255, 255, 0.14);
}

.fish-market-status-line.is-success {
  background: rgba(17, 191, 155, 0.18);
  color: #f3fffb;
}

.fish-market-feed-shell,
.fish-market-owned-list {
  padding: 12px 12px;
  border-radius: 20px;
  background: rgba(255, 255, 255, 0.95);
  border: 1px solid rgba(20, 108, 130, 0.12);
  box-shadow: 0 16px 28px rgba(12, 86, 104, 0.12);
}

.fish-market-feed-head {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 9px;
}

.fish-market-feed-head h2 {
  margin: 0;
  color: #123045;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1rem;
}

.fish-market-feed-head span {
  color: #63808b;
  font-size: 0.62rem;
  text-align: right;
}

.fish-market-feed {
  display: grid;
  gap: 8px;
}

.fish-market-feed-item {
  padding: 10px 10px 11px;
  border-radius: 15px;
  background: linear-gradient(180deg, rgba(245, 252, 255, 0.98) 0%, rgba(236, 249, 252, 0.95) 100%);
  border: 1px solid rgba(17, 115, 142, 0.1);
}

.fish-market-feed-item.is-live {
  animation: fishMarketFeedReveal 0.55s ease;
}

.fish-market-feed-item.is-user {
  background: linear-gradient(180deg, rgba(230, 253, 247, 0.98) 0%, rgba(221, 248, 241, 0.95) 100%);
  border-color: rgba(20, 169, 132, 0.16);
}

.fish-market-feed-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

.fish-market-feed-top strong {
  color: #123045;
  font-size: 0.76rem;
}

.fish-market-feed-top span {
  color: #6b838b;
  font-size: 0.62rem;
}

.fish-market-feed-item h3 {
  margin: 6px 0 5px;
  color: #123045;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.9rem;
}

.fish-market-feed-status {
  display: inline-flex;
  align-items: center;
  min-height: 19px;
  padding: 0 8px;
  border-radius: 999px;
  background: rgba(18, 190, 146, 0.12);
  color: #187458;
  font-size: 0.56rem;
  font-weight: 700;
}

.fish-market-feed-item p {
  margin: 6px 0 0;
  color: #45616b;
  font-size: 0.68rem;
  line-height: 1.4;
}

@keyframes fishMarketFeedReveal {
  0% {
    opacity: 0;
    transform: translateY(-10px) scale(0.985);
  }
  100% {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
</style>
