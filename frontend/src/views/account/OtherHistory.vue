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
  attendanceTotal: '0.00',
  bonusTotal: '0.00',
  totalTransactions: 0,
  creditTotal: '0.00',
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

function sumTransactionAmounts(transactions) {
  return formatAmount(
    transactions.reduce((total, item) => total + (Number(item?.amount) || 0), 0),
  )
}

function resolveTypeLabel(type) {
  if (type === 'VOUCHER') {
    return 'Voucher'
  }

  if (type === 'ATTENDANCE') {
    return 'Attendance'
  }

  if (type === 'MISSIONS') {
    return 'Mission'
  }

  if (type === 'CREDIT') {
    return 'Add balance'
  }

  return type || '-'
}

function mapOtherTransaction(item) {
  return {
    id: item?.id || item?.trx_id || item?.transaction_id,
    title: resolveTypeLabel(item?.type),
    description: item?.description || '-',
    amount: formatAmount(item?.amount),
    createdAt: formatDateTime(item?.created_at),
    transactionCode: item?.trx_id || item?.transaction_id || '-',
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

async function loadOtherHistorySummary() {
  isLoading.value = true
  loadError.value = ''
  page.value = 1

  try {
    const [voucherResult, attendanceResult, missionResult, creditResult] = await Promise.all([
      fetchAllTransactionsByType('VOUCHER'),
      fetchAllTransactionsByType('ATTENDANCE'),
      fetchAllTransactionsByType('MISSIONS'),
      fetchAllTransactionsByType('CREDIT'),
    ])

    if (!voucherResult.ok || !attendanceResult.ok || !missionResult.ok || !creditResult.ok) {
      loadError.value = 'Riwayat transaksi lainnya gagal dimuat.'
      return
    }

    const voucherTransactions = voucherResult.items
    const attendanceTransactions = attendanceResult.items
    const missionTransactions = missionResult.items
    const creditTransactions = creditResult.items
    const transactions = [
      ...voucherTransactions,
      ...attendanceTransactions,
      ...missionTransactions,
      ...creditTransactions,
    ]
      .sort((left, right) => new Date(right.created_at).getTime() - new Date(left.created_at).getTime())
      .map(mapOtherTransaction)

    state.value = {
      attendanceTotal: sumTransactionAmounts(attendanceTransactions),
      bonusTotal: sumTransactionAmounts(missionTransactions),
      totalTransactions: transactions.length,
      creditTotal: sumTransactionAmounts(creditTransactions),
      transactions,
    }
  } catch (error) {
    loadError.value =
      error instanceof Error ? error.message : 'Tidak bisa terhubung ke server riwayat lainnya.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadOtherHistorySummary()
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
    <section class="profile-shell">
      <section class="profile-module">
        <div class="profile-module-title"><span>Riwayat lainnya</span></div>
        <div class="profile-summary-grid">
          <div class="profile-summary-card">
            <strong>Rp{{ state.attendanceTotal }}</strong>
            <span>Total attendance</span>
          </div>
          <div class="profile-summary-card">
            <strong>Rp{{ state.bonusTotal }}</strong>
            <span>Total mission</span>
          </div>
          <div class="profile-summary-card">
            <strong>{{ state.totalTransactions }}</strong>
            <span>Total transaksi</span>
          </div>
          <div class="profile-summary-card">
            <strong>Rp{{ state.creditTotal }}</strong>
            <span>Total add balance</span>
          </div>
        </div>
      </section>

      <section v-if="loadError" class="deposit-alert is-error">
        <p>{{ loadError }}</p>
      </section>

      <section v-else class="deposit-history" aria-label="Riwayat lainnya">
        <div class="deposit-section-head">
          <div>
            <h2>Mutasi Lainnya</h2>
            <p>Transaksi voucher, add balance, attendance, dan mission terbaru</p>
          </div>
        </div>

        <div v-if="isLoading" class="deposit-history-list">
          <article class="deposit-history-empty">
            <p>Memuat ringkasan riwayat lainnya...</p>
          </article>
        </div>

        <div v-else-if="!state.transactions.length" class="deposit-history-list">
          <article class="deposit-history-empty">
            <p>Belum ada transaksi lainnya.</p>
          </article>
        </div>

        <div v-else class="deposit-history-list">
          <article
            v-for="transaction in visibleTransactions"
            :key="transaction.id"
            class="deposit-history-item"
          >
            <div class="deposit-history-top">
              <div>
                <strong>{{ transaction.title }}</strong>
                
              </div>
              <span class="deposit-status is-paid">Selesai</span>
            </div>
            <div class="deposit-history-bottom">
              <span>Rp{{ transaction.amount }}</span>
              <span>{{ transaction.createdAt }}</span>
            </div>
            <!-- <p>{{ transaction.description }}</p> -->
          </article>
          <button v-if="canLoadMore" type="button" class="deposit-submit-btn" @click="loadMoreTransactions">
            Muat lainnya
          </button>
        </div>
      </section>
    </section>
  </AppShell>
</template>
