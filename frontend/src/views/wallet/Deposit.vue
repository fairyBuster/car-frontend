<script setup>
import { computed, onMounted, ref } from 'vue'
import AppShell from '../../components/layout/AppShell.vue'
import {
  backProfileHeaderActions,
  getDepositData,
  marketNoticeItems,
  standardFooterItems,
} from '../../data/mockData'
import { getDepositTransactions } from '../../services/api'

const state = ref(getDepositData())
const copied = ref(false)
const isLoadingOrders = ref(false)
const orderError = ref('')
const flash = ref({
  orderNo: 'AQD-20260721-001',
  amount: '50.00',
  paymentLabel: 'USDT',
  networkLabel: 'TRC20',
})

const selectedWallet = computed(
  () =>
    state.value.wallets.find((wallet) => wallet.networkKey === state.value.selectedNetwork) ??
    state.value.wallets[0],
)

function setActiveNetwork(key) {
  state.value.selectedNetwork = key
}

function extractTransactions(payload) {
  if (Array.isArray(payload)) {
    return payload
  }

  if (!payload || typeof payload !== 'object') {
    return []
  }

  if (Array.isArray(payload.results)) {
    return payload.results
  }

  return []
}

function formatDate(value) {
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

function sanitizeDepositOrderNo(value) {
  const raw = String(value || '').trim()
  if (!raw) {
    return '-'
  }

  const trimmed = raw.replace(/^DEP-/i, '')
  const lastDashIndex = trimmed.lastIndexOf('-')
  if (lastDashIndex > 0) {
    return trimmed.slice(0, lastDashIndex)
  }

  return trimmed
}

function mapDepositOrder(item) {
  const rawOrderNo = item.transaction_id || item.trx_id || `DEP-${item.id}`
  return {
    orderNo: rawOrderNo,
    displayOrderNo: sanitizeDepositOrderNo(rawOrderNo),
    paymentLabel: item.currency_code || item.original_currency_code || 'DEPOSIT',
    networkLabel: item.wallet_type || item.bank_name || '-',
    status: 'paid',
    amount: item.amount || '0.00',
    createdAt: formatDate(item.created_at),
  }
}

async function loadCompletedOrders() {
  isLoadingOrders.value = true
  orderError.value = ''

  try {
    const { ok, data } = await getDepositTransactions({ status: 'COMPLETED' })

    if (!ok) {
      orderError.value = 'Riwayat deposit gagal dimuat.'
      return
    }

    state.value.orders = extractTransactions(data).map(mapDepositOrder)
  } catch (error) {
    orderError.value =
      error instanceof Error ? error.message : 'Tidak bisa terhubung ke server deposit.'
  } finally {
    isLoadingOrders.value = false
  }
}

async function copyWallet() {
  try {
    await navigator.clipboard.writeText(selectedWallet.value.walletAddress)
    copied.value = true
    window.setTimeout(() => {
      copied.value = false
    }, 1500)
  } catch {
    copied.value = false
  }
}

onMounted(() => {
  loadCompletedOrders()
})
</script>

<template>
  <AppShell
    body-class="deposit-page-body"
    main-class="deposit-main"
    :header-actions="backProfileHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="standardFooterItems"
    active-footer-key="profile"
  >

    <section class="deposit-panel" aria-label="Deposit form">
      <form class="deposit-form" novalidate @submit.prevent>
        <input type="hidden" name="payment_method" value="usdt" />

        <label class="deposit-field" for="deposit-amount">
          <span class="deposit-field-label">Deposit Amount</span>
          <input
            id="deposit-amount"
            v-model="state.amount"
            class="deposit-input"
            type="number"
            name="amount"
            min="10"
            step="0.01"
            inputmode="decimal"
            placeholder="Example: 10000"
            required
          />
        </label>

      

        <div class="deposit-flow-note">
          <strong>Flow:</strong>
          <span>
            Enter the amount, choose the network, complete the transfer, and submit the request for review. Balance is not added until approval.
          </span>
        </div>

        <button type="submit" class="deposit-submit-btn">Submit Deposit Request</button>
      </form>
    </section>

    <section class="deposit-history" aria-label="Deposit orders">
      <div class="deposit-section-head">
        <div>
          <h2>Deposit History</h2>
          <p>Created orders and payment status</p>
        </div>
      </div>

      <div class="deposit-history-list">
        <article v-if="isLoadingOrders" class="deposit-history-item">
          <div class="deposit-history-top">
            <div>
              <strong>Memuat</strong>
              <span>Riwayat deposit sedang diambil</span>
            </div>
          </div>
        </article>
        <article v-else-if="orderError" class="deposit-history-item">
          <div class="deposit-history-top">
            <div>
              <strong>Gagal</strong>
              <span>{{ orderError }}</span>
            </div>
          </div>
        </article>
        <article v-else-if="!state.orders.length" class="deposit-history-item">
          <div class="deposit-history-top">
            <div>
              <strong>Belum ada data</strong>
              <span>Belum ada deposit completed</span>
            </div>
          </div>
        </article>
        <article v-for="order in state.orders" :key="order.orderNo" class="deposit-history-item">
          <div class="deposit-history-top">
            <div>
              <strong>{{ order.displayOrderNo }}</strong>
              <!-- <span>{{ order.paymentLabel }} · {{ order.networkLabel }}</span> -->
            </div>
            <span class="deposit-status" :class="`is-${order.status}`">
              {{ order.status === 'paid' ? 'Approved' : order.status === 'cancelled' ? 'Cancelled' : 'Pending' }}
            </span>
          </div>

          <div class="deposit-history-bottom">
            <span>Rp{{ order.amount }}</span>
            <span>{{ order.createdAt }}</span>
          </div>
        </article>
      </div>
    </section>
  </AppShell>
</template>

<style>
.deposit-page-body {
  background:
    radial-gradient(circle at top center, rgba(110, 209, 189, 0.18), transparent 30%),
    linear-gradient(180deg, #eef8fc 0%, #dff4f7 46%, #d4efea 100%);
}

.deposit-main {
  padding-bottom: 184px;
}

.deposit-panel {
  width: 100%;
  box-sizing: border-box;
  padding: 18px 16px;
  border-radius: 24px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(242, 250, 252, 0.96) 100%);
  border: 1px solid rgba(19, 118, 146, 0.1);
  box-shadow: 0 18px 34px rgba(28, 89, 109, 0.09);
}

@media (max-width: 640px) {
  .deposit-panel {
    padding: 14px 12px;
    border-radius: 22px;
  }
}
</style>
