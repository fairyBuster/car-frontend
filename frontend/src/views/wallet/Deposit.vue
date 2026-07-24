<script setup>
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import AppShell from '../../components/layout/AppShell.vue'
import {
  backProfileHeaderActions,
  getDepositData,
  marketNoticeItems,
  standardFooterItems,
} from '../../data/mockData'
import {
  getDepositTransactions,
  initiatePpayProsDeposit,
  initiateSiTransferHubDeposit,
} from '../../services/api'

const state = ref(getDepositData())
const router = useRouter()
const isLoadingOrders = ref(false)
const orderError = ref('')
const submitError = ref('')
const isSubmitting = ref(false)
const QRIS_OVERVIEW_STORAGE_KEY = 'deposit_qris_overview'
const MIN_DEPOSIT_AMOUNT = 20000

const depositMethods = [
  {
    key: 'sitransferhub',
    title: 'Payment 01',
  },
  {
    key: 'ppaypros',
    title: 'Payment 02',
  },
]

function setActiveMethod(key) {
  state.value.selectedMethod = key
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

function formatAmountInput(value) {
  const raw = String(value || '').replace(/,/g, '').trim()
  const amount = Number(raw)

  if (!Number.isFinite(amount)) {
    return ''
  }

  return String(Math.trunc(amount))
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

  const normalizedStatus =
    item?.status === 'COMPLETED'
      ? 'paid'
      : item?.status === 'REJECTED' || item?.status === 'FAILED'
        ? 'cancelled'
        : 'pending'

  return {
    orderNo: rawOrderNo,
    displayOrderNo: sanitizeDepositOrderNo(rawOrderNo),
    paymentLabel: item.currency_code || item.original_currency_code || 'DEPOSIT',
    networkLabel: item.wallet_type || item.bank_name || '-',
    status: normalizedStatus,
    amount: item.amount || '0.00',
    createdAt: formatDate(item.created_at),
  }
}

function resolveProviderMessage(payload) {
  const providerData = payload?.provider?.data

  if (providerData?.errMsg) {
    return String(providerData.errMsg)
  }

  if (payload?.detail) {
    return String(payload.detail)
  }

  if (payload?.message) {
    return String(payload.message)
  }

  if (payload && typeof payload === 'object') {
    const firstError = Object.values(payload).find(
      (value) => Array.isArray(value) && value.length && typeof value[0] === 'string',
    )

    if (firstError) {
      return String(firstError[0])
    }
  }

  return ''
}

function persistQrisOverview(payload) {
  const normalizedPayload = {
    orderNo: payload?.order_num || '-',
    amount: String(payload?.amount || state.value.amount || ''),
    transactionId: payload?.transaction_id || '-',
    channel: payload?.channel || 'QRIS',
    expiredAt: payload?.expired_at || '-',
    instruction: payload?.instruction || 'Silakan selesaikan pembayaran sebelum waktu kedaluwarsa.',
    qrisImage: String(payload?.qris_image || '').replace(/[`'"]/g, '').trim(),
    qrisData: payload?.qris_data || '',
  }

  sessionStorage.setItem(QRIS_OVERVIEW_STORAGE_KEY, JSON.stringify(normalizedPayload))
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

async function submitDeposit() {
  submitError.value = ''

  const normalizedAmount = formatAmountInput(state.value.amount)
  const amount = Number(normalizedAmount)

  if (!Number.isFinite(amount) || amount < MIN_DEPOSIT_AMOUNT) {
    submitError.value = 'Minimal deposit Rp 20.000.'
    return
  }

  isSubmitting.value = true

  try {
    let result

    if (state.value.selectedMethod === 'ppaypros') {
      result = await initiatePpayProsDeposit({
        amount,
        wallet_type: 'BALANCE_DEPOSIT',
        wayCode: '',
        extParam: '',
      })
    } else {
      result = await initiateSiTransferHubDeposit({
        amount: normalizedAmount,
        wallet_type: 'BALANCE_DEPOSIT',
        channel: 'QRIS',
      })
    }

    if (!result.ok) {
      submitError.value = resolveProviderMessage(result.data) || 'Deposit gagal dibuat.'
      return
    }

    if (state.value.selectedMethod === 'ppaypros') {
      const paymentUrl = String(result.data?.payment_url || '').trim()

      if (!paymentUrl) {
        submitError.value = resolveProviderMessage(result.data) || 'Payment 02 gagal diproses.'
        return
      }

      window.location.assign(paymentUrl.replace(/[`'"]/g, '').trim())
      return
    }

    persistQrisOverview(result.data || {})
    await loadCompletedOrders()
    await router.push({ name: 'deposit-overview' })
  } catch (error) {
    submitError.value =
      error instanceof Error ? error.message : 'Tidak bisa terhubung ke server deposit.'
  }
  finally {
    isSubmitting.value = false
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
      <form class="deposit-form" novalidate @submit.prevent="submitDeposit">
        <div class="deposit-methods" aria-label="Metode deposit">
          <button
            v-for="method in depositMethods"
            :key="method.key"
            type="button"
            class="deposit-method-card"
            :class="{ 'is-active': state.selectedMethod === method.key }"
            @click="setActiveMethod(method.key)"
          >
            <strong>{{ method.title }}</strong>
            <span>{{ method.subtitle }}</span>
            <p>{{ method.description }}</p>
          </button>
        </div>

        <label class="deposit-field" for="deposit-amount">
          <span class="deposit-field-label">Deposit Amount</span>
          <input
            id="deposit-amount"
            v-model="state.amount"
            class="deposit-input"
            type="number"
            name="amount"
            :min="MIN_DEPOSIT_AMOUNT"
            step="1"
            inputmode="decimal"
            placeholder="Minimal 20000"
            required
          />
        </label>

        <!-- <div class="deposit-flow-note">
          <strong>Metode aktif:</strong>
          <span>
            {{
              state.selectedMethod === 'ppaypros'
                ? 'PPay Pros akan mengirim order deposit menggunakan wallet BALANCE_DEPOSIT.'
                : 'SiTransfer Hub akan membuat deposit QRIS ke wallet BALANCE_DEPOSIT.'
            }}
          </span>
        </div> -->

        <div v-if="submitError" class="deposit-alert is-error">
          <p>{{ submitError }}</p>
        </div>

        <div class="deposit-flow-note">
          <strong>Flow:</strong>
          <span>
            Masukkan jumlah, pilih metode deposit, buat order, lalu selesaikan pembayaran sesuai instruksi provider.
          </span>
        </div>

        <button type="submit" class="deposit-submit-btn" :disabled="isSubmitting">
          {{ isSubmitting ? 'Memproses deposit...' : 'Submit Deposit Request' }}
        </button>
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

.deposit-methods {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.deposit-method-card {
  width: 100%;
  text-align: left;
  border: 1px solid rgba(19, 118, 146, 0.12);
  border-radius: 18px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(239, 249, 252, 0.98) 100%);
  padding: 14px 12px;
  color: #123045;
}

.deposit-method-card.is-active {
  border-color: rgba(15, 140, 130, 0.42);
  box-shadow: 0 12px 22px rgba(11, 112, 104, 0.12);
}

.deposit-method-card strong {
  display: block;
  font-size: 0.92rem;
}

.deposit-method-card span {
  display: block;
  margin-top: 4px;
  font-size: 0.72rem;
  color: #0f7b87;
  font-weight: 700;
}

.deposit-method-card p {
  margin: 8px 0 0;
  font-size: 0.74rem;
  line-height: 1.4;
  color: #607985;
}

@media (max-width: 640px) {
  .deposit-panel {
    padding: 14px 12px;
    border-radius: 22px;
  }

  .deposit-methods {
    grid-template-columns: 1fr;
  }
}
</style>
