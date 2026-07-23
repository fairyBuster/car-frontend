<script setup>
import { computed, onMounted, ref } from 'vue'
import AppShell from '../../components/layout/AppShell.vue'
import {
  backProfileHeaderActions,
  marketNoticeItems,
  standardFooterItems,
} from '../../data/mockData'
import { getBalanceStatistics, getTransactions } from '../../services/api'

const isLoading = ref(false)
const loadError = ref('')
const page = ref(1)
const pageSize = 10
const state = ref({
  totalCommission: '0.00',
  profitCommission: '0.00',
  purchaseCommission: '0.00',
  totalIncome: '0.00',
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

function mapCommissionTransaction(item) {
  return {
    id: item?.id || item?.trx_id || item?.transaction_id,
    buyerPhone: item?.upline_phone || item?.bank_account_number || '-',
    levelLabel: `Level ${Number(item?.commission_level) || 0}`,
    description: item?.description || item?.product_name || '-',
    amount: formatAmount(item?.amount),
    createdAt: formatDateTime(item?.created_at),
  }
}

function extractTransactions(payload) {
  if (Array.isArray(payload)) {
    return payload
  }

  if (payload && typeof payload === 'object' && Array.isArray(payload.results)) {
    return payload.results
  }

  return []
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

const visibleTransactions = computed(() => state.value.transactions.slice(0, page.value * pageSize))
const canLoadMore = computed(() => state.value.transactions.length > visibleTransactions.value.length)

function loadMoreTransactions() {
  if (!canLoadMore.value) {
    return
  }

  page.value += 1
}

async function fetchAllTransactionsByType(type) {
  const items = []
  let currentPage = 1
  let hasNext = true
  let safety = 0

  while (hasNext && safety < 50) {
    const { ok, data } = await getTransactions({ type, page: currentPage })

    if (!ok) {
      return { ok: false, items: [] }
    }

    const { results, next } = extractTransactionsPage(data)
    items.push(...results)

    if (!next || !results.length) {
      hasNext = false
    } else {
      currentPage += 1
    }

    safety += 1
  }

  return { ok: true, items }
}

async function loadCommissionData() {
  isLoading.value = true
  loadError.value = ''
  page.value = 1

  try {
    const [balanceResult, profitResult, purchaseResult] = await Promise.all([
      getBalanceStatistics('all-time'),
      fetchAllTransactionsByType('PROFIT_COMMISSION'),
      fetchAllTransactionsByType('PURCHASE_COMMISSION'),
    ])

    if (!balanceResult.ok || !balanceResult.data || typeof balanceResult.data !== 'object') {
      loadError.value = 'Ringkasan komisi gagal dimuat.'
      return
    }

    if (!profitResult.ok || !purchaseResult.ok) {
      loadError.value = 'Riwayat transaksi komisi gagal dimuat.'
      return
    }

    const profitTransactions = profitResult.items
    const purchaseTransactions = purchaseResult.items
    const transactions = [...profitTransactions, ...purchaseTransactions]
      .sort((left, right) => new Date(right.created_at).getTime() - new Date(left.created_at).getTime())
      .map(mapCommissionTransaction)

    state.value = {
      totalCommission: formatAmount(balanceResult.data.total_commission),
      profitCommission: formatAmount(balanceResult.data.profit_commission),
      purchaseCommission: formatAmount(balanceResult.data.purchase_commission),
      totalIncome: formatAmount(balanceResult.data.total_income),
      transactions,
    }
  } catch (error) {
    loadError.value =
      error instanceof Error ? error.message : 'Tidak bisa terhubung ke server riwayat komisi.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadCommissionData()
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
    <section class="commission-history-shell">
      <section class="commission-history-module">
        <div class="commission-history-title"><span>Riwayat komisi</span></div>
        <div class="commission-history-summary-grid">
          <div class="commission-history-summary-card">
            <strong>Rp{{ state.totalCommission }}</strong>
            <span>Total komisi</span>
          </div>
          <!-- <div class="commission-history-summary-card">
            <strong>Rp{{ state.profitCommission }}</strong>
            <span>Komisi profit</span>
          </div>
          <div class="commission-history-summary-card">
            <strong>Rp{{ state.purchaseCommission }}</strong>
            <span>Komisi pembelian</span>
          </div>
          <div class="commission-history-summary-card">
            <strong>Rp{{ state.totalIncome }}</strong>
            <span>Total pendapatan</span>
          </div> -->
        </div>
      </section>

      <section v-if="loadError" class="commission-history-alert is-error">
        <p>{{ loadError }}</p>
      </section>

      <section v-else class="commission-history-panel" aria-label="Riwayat komisi">
        <div class="commission-history-head">
          <div>
            <h2>Commision History</h2>
            <p>All commision team on here</p>
          </div>
        </div>

        <div v-if="isLoading" class="commission-history-list">
          <article class="commission-history-empty">
            <p>Memuat data komisi...</p>
          </article>
        </div>

        <div v-else-if="!state.transactions.length" class="commission-history-list">
          <article class="commission-history-empty">
            <p>Belum ada transaksi komisi.</p>
          </article>
        </div>

        <div v-else class="commission-history-list">
          <article
            v-for="transaction in visibleTransactions"
            :key="transaction.id"
            class="commission-history-item"
          >
            <div class="commission-history-top">
              <div>
                <strong>{{ transaction.buyerPhone }}</strong>
                <span>{{ transaction.levelLabel }}</span>
              </div>
            </div>
            <div class="commission-history-bottom">
              <span>Rp{{ transaction.amount }}</span>
              <span>{{ transaction.createdAt }}</span>
            </div>
            <!-- <p>{{ transaction.description }}</p> -->
          </article>
          <button
            v-if="canLoadMore"
            type="button"
            class="commission-history-load-more"
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
.commission-history-shell {
  width: 100%;
  max-width: 430px;
  display: grid;
  gap: 12px;
}

.commission-history-module,
.commission-history-alert,
.commission-history-panel {
  width: 100%;
  box-sizing: border-box;
  padding: 18px 16px;
  border-radius: 24px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(242, 250, 252, 0.96) 100%);
  border: 1px solid rgba(19, 118, 146, 0.1);
  box-shadow: 0 18px 34px rgba(28, 89, 109, 0.09);
}

.commission-history-title {
  padding: 0 2px 12px;
}

.commission-history-title span {
  display: inline-flex;
  align-items: center;
  min-height: 28px;
  padding: 0 12px;
  border-left: 4px solid #0fc09f;
  color: #113246;
  font-size: 0.8rem;
  font-weight: 800;
}

.commission-history-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 8px;
}

.commission-history-summary-card {
  padding: 12px 11px;
  border-radius: 18px;
  background: linear-gradient(180deg, #f5fbfd 0%, #eef8fb 100%);
  border: 1px solid rgba(18, 112, 134, 0.08);
}

.commission-history-summary-card strong {
  display: block;
  color: #123045;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.98rem;
  line-height: 1.1;
}

.commission-history-summary-card span {
  display: block;
  margin-top: 6px;
  color: #6b8590;
  font-size: 0.66rem;
}

.commission-history-alert p {
  margin: 0;
  font-size: 0.88rem;
  line-height: 1.55;
}

.commission-history-alert.is-error {
  border-color: rgba(190, 87, 87, 0.16);
  color: #8a3535;
  background: linear-gradient(180deg, rgba(255, 249, 249, 0.98) 0%, rgba(253, 239, 239, 0.96) 100%);
}

.commission-history-head,
.commission-history-top,
.commission-history-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.commission-history-head h2 {
  margin: 8px 0 0;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.18rem;
  color: #143548;
}

.commission-history-head p {
  margin: 6px 0 0;
  font-size: 0.76rem;
  color: #738a96;
}

.commission-history-list {
  display: grid;
  gap: 10px;
  margin-top: 16px;
}

.commission-history-item,
.commission-history-empty {
  padding: 14px;
  border-radius: 18px;
  background: linear-gradient(180deg, rgba(245, 252, 253, 0.98) 0%, rgba(234, 246, 249, 0.96) 100%);
  border: 1px solid rgba(18, 117, 144, 0.1);
}

.commission-history-item {
  display: grid;
  gap: 12px;
}

.commission-history-top span,
.commission-history-bottom span,
.commission-history-empty p {
  font-size: 0.74rem;
  color: #6c8390;
}

.commission-history-top strong {
  display: block;
  margin-top: 6px;
  font-size: 0.88rem;
  color: #133446;
}

.commission-history-item p {
  margin: 0;
  font-size: 0.74rem;
  line-height: 1.5;
  color: #56707c;
}

.commission-history-load-more {
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
  .commission-history-module,
  .commission-history-alert,
  .commission-history-panel {
    padding: 14px 12px;
    border-radius: 22px;
  }
}
</style>
