<script setup>
import { computed, onMounted, ref } from 'vue'
import AppShell from '../../components/layout/AppShell.vue'
import {
  backProfileHeaderActions,
  marketNoticeItems,
  standardFooterItems,
} from '../../data/mockData'
import { getTransactions } from '../../services/api'

const isLoading = ref(false)
const loadError = ref('')
const page = ref(1)
const pageSize = 10
const state = ref({
  totalProfit: '0.00',
  totalTransactions: 0,
  latestProfit: '0.00',
  transactions: [],
})

function formatAmount(value) {
  const amount = Number(value)
  return Number.isFinite(amount) ? amount.toFixed(2) : '0.00'
}

function formatDateTime(value) {
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
  const hh = String(date.getHours()).padStart(2, '0')
  const min = String(date.getMinutes()).padStart(2, '0')

  return `${yyyy}-${mm}-${dd} ${hh}:${min}`
}

function extractTransactionsPage(payload) {
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

function sumTransactionAmounts(transactions) {
  return formatAmount(
    transactions.reduce((total, item) => total + (Number(item?.amount) || 0), 0),
  )
}

function resolveStatusLabel(status) {
  if (status === 'COMPLETED') {
    return 'Selesai'
  }

  if (status === 'PENDING') {
    return 'Menunggu'
  }

  if (status === 'REJECTED') {
    return 'Ditolak'
  }

  return status || '-'
}

function resolveStatusClass(status) {
  if (status === 'COMPLETED') {
    return 'is-paid'
  }

  if (status === 'PENDING') {
    return 'is-pending'
  }

  return 'is-cancelled'
}

function mapInterestTransaction(item) {
  return {
    id: item?.id || item?.trx_id || item?.transaction_id,
    productName: item?.product_name || '-',
    transactionCode: item?.trx_id || '-',
    purchaseCode: item?.transaction_id || '-',
    description: item?.description || '-',
    amount: formatAmount(item?.amount),
    createdAt: formatDateTime(item?.created_at),
    statusLabel: resolveStatusLabel(item?.status),
    statusClass: resolveStatusClass(item?.status),
  }
}

const visibleTransactions = computed(() => state.value.transactions.slice(0, page.value * pageSize))
const canLoadMore = computed(() => state.value.transactions.length > visibleTransactions.value.length)

function loadMoreTransactions() {
  if (!canLoadMore.value) {
    return
  }

  page.value += 1
}

async function loadInterestHistory() {
  isLoading.value = true
  loadError.value = ''
  page.value = 1

  try {
    const items = []
    let page = 1
    let hasNext = true
    let safety = 0

    while (hasNext && safety < 50) {
      const { ok, data } = await getTransactions({ type: 'INTEREST', page })

      if (!ok) {
        loadError.value = 'Riwayat keuntungan gagal dimuat.'
        state.value = {
          totalProfit: '0.00',
          totalTransactions: 0,
          latestProfit: '0.00',
          transactions: [],
        }
        return
      }

      const { results, next } = extractTransactionsPage(data)
      items.push(...results)

      if (!next || !results.length) {
        hasNext = false
      } else {
        page += 1
      }

      safety += 1
    }

    const sortedTransactions = items
      .slice()
      .sort((left, right) => new Date(right.created_at).getTime() - new Date(left.created_at).getTime())

    state.value = {
      totalProfit: sumTransactionAmounts(sortedTransactions),
      totalTransactions: sortedTransactions.length,
      latestProfit: formatAmount(sortedTransactions[0]?.amount),
      transactions: sortedTransactions.map(mapInterestTransaction),
    }
  } catch (error) {
    loadError.value =
      error instanceof Error ? error.message : 'Tidak bisa terhubung ke server riwayat keuntungan.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadInterestHistory()
})
</script>

<template>
  <AppShell
    body-class="profile-page-body"
    main-class="profile-main"
    :header-actions="backProfileHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="standardFooterItems"
    active-footer-key="profile"
  >
    <section class="interest-history-shell">
      <section class="interest-history-module">
        <div class="interest-history-title"><span>Riwayat keuntungan</span></div>
        <div class="interest-history-summary-grid">
          <div class="interest-history-summary-card">
            <strong>Rp{{ state.totalProfit }}</strong>
            <span>Total keuntungan</span>
          </div>
          <!-- <div class="interest-history-summary-card">
            <strong>{{ state.totalTransactions }}</strong>
            <span>Total transaksi</span>
          </div>
          <div class="interest-history-summary-card">
            <strong>Rp{{ state.latestProfit }}</strong>
            <span>Profit terbaru</span>
          </div> -->
        </div>
      </section>

      <section v-if="loadError" class="interest-history-alert is-error">
        <p>{{ loadError }}</p>
      </section>

      <section v-else class="interest-history-panel" aria-label="Riwayat keuntungan">
        <div class="interest-history-head">
          <div>
            <h2>Riwayat keuntungan</h2>
            <p>Semua profit investasi dari transaksi INTEREST</p>
          </div>
        </div>

        <div v-if="isLoading" class="interest-history-list">
          <article class="interest-history-empty">
            <p>Memuat riwayat keuntungan...</p>
          </article>
        </div>

        <div v-else-if="!state.transactions.length" class="interest-history-list">
          <article class="interest-history-empty">
            <p>Belum ada riwayat keuntungan.</p>
          </article>
        </div>

        <div v-else class="interest-history-list">
          <article
            v-for="transaction in visibleTransactions"
            :key="transaction.id"
            class="interest-history-item"
          >
            <div class="interest-history-top">
              <div>
                <strong>{{ transaction.productName }}</strong>
                <span>{{ transaction.purchaseCode }}</span>
              </div>
              <span class="interest-history-status" :class="transaction.statusClass">
                {{ transaction.statusLabel }}
              </span>
            </div>
            <div class="interest-history-bottom">
              <span>Rp{{ transaction.amount }}</span>
              <span>{{ transaction.createdAt }}</span>
            </div>
            <!-- <p>{{ transaction.description }}</p> -->
            <!-- <p>Kode transaksi: {{ transaction.transactionCode }}</p> -->
          </article>
          <button
            v-if="canLoadMore"
            type="button"
            class="interest-history-load-more"
            @click="loadMoreTransactions"
          >
            Muat lainnya
          </button>
        </div>
      </section>
    </section>
  </AppShell>
</template>

<style scoped>
.interest-history-shell {
  width: 100%;
  max-width: 430px;
  display: grid;
  gap: 12px;
}

.interest-history-module,
.interest-history-alert,
.interest-history-panel {
  width: 100%;
  box-sizing: border-box;
  padding: 18px 16px;
  border-radius: 24px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(242, 250, 252, 0.96) 100%);
  border: 1px solid rgba(19, 118, 146, 0.1);
  box-shadow: 0 18px 34px rgba(28, 89, 109, 0.09);
}

.interest-history-title {
  padding: 0 2px 12px;
}

.interest-history-title span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 12px;
  border-left: 4px solid #0fc09f;
  color: #113246;
  font-size: 0.8rem;
  font-weight: 800;
}

.interest-history-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}

.interest-history-summary-card {
  padding: 12px 11px;
  border-radius: 18px;
  background: linear-gradient(180deg, #f5fbfd 0%, #eef8fb 100%);
  border: 1px solid rgba(18, 112, 134, 0.08);
}

.interest-history-summary-card strong {
  display: block;
  color: #123045;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.98rem;
  line-height: 1.1;
}

.interest-history-summary-card span {
  display: block;
  margin-top: 6px;
  color: #6b8590;
  font-size: 0.66rem;
}

.interest-history-alert p {
  margin: 0;
  font-size: 0.88rem;
  line-height: 1.55;
}

.interest-history-alert.is-error {
  border-color: rgba(190, 87, 87, 0.16);
  color: #8a3535;
  background: linear-gradient(180deg, rgba(255, 249, 249, 0.98) 0%, rgba(253, 239, 239, 0.96) 100%);
}

.interest-history-head,
.interest-history-top,
.interest-history-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.interest-history-head h2 {
  margin: 8px 0 0;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.18rem;
  color: #143548;
}

.interest-history-head p {
  margin: 6px 0 0;
  font-size: 0.76rem;
  color: #738a96;
}

.interest-history-list {
  display: grid;
  gap: 10px;
  margin-top: 16px;
}

.interest-history-item,
.interest-history-empty {
  padding: 14px;
  border-radius: 18px;
  background: linear-gradient(180deg, rgba(245, 252, 253, 0.98) 0%, rgba(234, 246, 249, 0.96) 100%);
  border: 1px solid rgba(18, 117, 144, 0.1);
}

.interest-history-item {
  display: grid;
  gap: 12px;
}

.interest-history-top span,
.interest-history-bottom span,
.interest-history-empty p {
  font-size: 0.74rem;
  color: #6c8390;
}

.interest-history-top strong {
  display: block;
  margin-top: 6px;
  font-size: 0.88rem;
  color: #133446;
}

.interest-history-item p {
  margin: 0;
  font-size: 0.74rem;
  line-height: 1.5;
  color: #56707c;
}

.interest-history-status {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 12px;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 800;
}

.interest-history-status.is-pending {
  background: rgba(255, 188, 67, 0.16);
  color: #b7780b;
}

.interest-history-status.is-paid {
  background: rgba(61, 185, 133, 0.16);
  color: #167251;
}

.interest-history-status.is-cancelled {
  background: rgba(222, 103, 103, 0.14);
  color: #984141;
}

.interest-history-load-more {
  width: 100%;
  border: 0;
  border-radius: 16px;
  min-height: 44px;
  padding: 0 16px;
  background: linear-gradient(135deg, rgba(22, 185, 170, 0.14) 0%, rgba(45, 141, 213, 0.14) 100%);
  color: #0b6e66;
  font-weight: 800;
}

@media (max-width: 640px) {
  .interest-history-module,
  .interest-history-alert,
  .interest-history-panel {
    padding: 14px 12px;
    border-radius: 22px;
  }
}
</style>
