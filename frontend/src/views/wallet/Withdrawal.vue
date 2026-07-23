<script setup>
import { computed, onMounted, ref } from 'vue'
import AppShell from '../../components/layout/AppShell.vue'
import {
  backProfileHeaderActions,
  getWithdrawalData,
  marketNoticeItems,
  standardFooterItems,
} from '../../data/mockData'
import { useRouter } from 'vue-router'
import { getUserBanks, getWithdrawals, requestWithdrawal } from '../../services/api'

const SELECTED_BANK_STORAGE_KEY = 'selected_withdraw_bank_id'

const router = useRouter()

const state = ref(getWithdrawalData())
const amount = ref('')
const flash = ref(null)
const isLoadingRequests = ref(false)
const requestError = ref('')
const isSubmitting = ref(false)
const submitError = ref('')
const isLoadingBanks = ref(false)
const bankError = ref('')
const banks = ref([])

const selectedBankId = ref(null)

const selectedBank = computed(() => {
  if (!selectedBankId.value) {
    return null
  }

  return banks.value.find((item) => item?.id === selectedBankId.value) ?? null
})

const bankFieldValue = computed(() => {
  if (!selectedBank.value) {
    return ''
  }

  const name = selectedBank.value.bank_name || selectedBank.value.bank_code || 'Bank'
  const number = selectedBank.value.account_number || '-'
  return `${name} • ${number}`
})

function resolveStoredBalances() {
  try {
    const rawUser = localStorage.getItem('auth_user')
    const user = rawUser ? JSON.parse(rawUser) : null

    if (!user || typeof user !== 'object') {
      return
    }

    if (user.balance_deposit !== undefined) {
      state.value.displayBalance = Number(user.balance_deposit).toFixed(2)
    }

    if (user.balance !== undefined) {
      state.value.withdrawableBalance = Number(user.balance).toFixed(2)
    }
  } catch {
    // Keep mock fallback when auth_user is unavailable or malformed.
  }
}

resolveStoredBalances()

function resolveStoredSelectedBankId() {
  const stored = localStorage.getItem(SELECTED_BANK_STORAGE_KEY)
  const asNumber = stored ? Number(stored) : NaN
  if (Number.isFinite(asNumber)) {
    return asNumber
  }

  return null
}

function extractWithdrawals(payload) {
  if (Array.isArray(payload)) {
    return payload
  }

  if (!payload || typeof payload !== 'object') {
    return []
  }

  return Array.isArray(payload.results) ? payload.results : []
}

function formatRequestNo(value) {
  if (!value) {
    return ''
  }

  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return ''
  }

  const yyyy = date.getFullYear()
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const dd = String(date.getDate()).padStart(2, '0')
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${yyyy}${mm}${dd}${hours}${minutes}`
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
  const hours = String(date.getHours()).padStart(2, '0')
  const minutes = String(date.getMinutes()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd} ${hours}:${minutes}`
}

function mapWithdrawalRequest(item) {
  const normalizedStatus =
    item?.status === 'COMPLETED' ? 'approved' : item?.status === 'REJECTED' ? 'rejected' : 'pending'

  return {
    requestNo: formatRequestNo(item?.created_at),
    bankAccountNumber: item?.bank_account_number || '-',
    bankName: item?.bank_name || '-',
    status: normalizedStatus,
    amount: item?.amount || '0.00',
    createdAt: formatDateTime(item?.created_at),
  }
}

async function loadWithdrawals() {
  isLoadingRequests.value = true
  requestError.value = ''

  try {
    const { ok, data } = await getWithdrawals()

    if (!ok) {
      requestError.value = 'Riwayat withdrawal gagal dimuat.'
      state.value.requests = []
      flash.value = null
      return
    }

    const withdrawals = extractWithdrawals(data).sort(
      (left, right) => new Date(right.created_at).getTime() - new Date(left.created_at).getTime(),
    )

    state.value.requests = withdrawals.map(mapWithdrawalRequest)

    const latestPending = withdrawals.find((item) => item?.status === 'PENDING')

    if (!latestPending) {
      flash.value = null
      return
    }

    flash.value = {
      requestNo: formatRequestNo(latestPending.created_at),
      amount: latestPending.amount || '',
    }
  } catch {
    requestError.value = 'Tidak bisa terhubung ke server withdrawal.'
    state.value.requests = []
    flash.value = null
  } finally {
    isLoadingRequests.value = false
  }
}

async function loadBanks() {
  isLoadingBanks.value = true
  bankError.value = ''

  try {
    const { ok, data } = await getUserBanks()

    if (!ok) {
      bankError.value = 'Daftar bank gagal dimuat.'
      banks.value = []
      selectedBankId.value = null
      return
    }

    banks.value = Array.isArray(data) ? data : []

    const storedId = resolveStoredSelectedBankId()
    const hasStored = storedId && banks.value.some((item) => item?.id === storedId)
    if (hasStored) {
      selectedBankId.value = storedId
      return
    }

    const defaultBank = banks.value.find((item) => item?.is_default)
    selectedBankId.value = defaultBank?.id ?? null
  } catch {
    bankError.value = 'Tidak bisa terhubung ke server bank.'
    banks.value = []
    selectedBankId.value = null
  } finally {
    isLoadingBanks.value = false
  }
}

function openBankBinding() {
  router.push({
    name: 'banks',
    query: { redirect: router.resolve({ name: 'withdrawal' }).href },
  })
}

function resolveSubmitErrorMessage(payload) {
  if (payload && typeof payload === 'object') {
    const nonFieldErrors = payload.non_field_errors
    if (Array.isArray(nonFieldErrors) && nonFieldErrors.length) {
      return String(nonFieldErrors[0])
    }

    const amountErrors = payload.amount
    if (Array.isArray(amountErrors) && amountErrors.length) {
      return String(amountErrors[0])
    }

    if (payload.detail) {
      return String(payload.detail)
    }
  }

  if (typeof payload === 'string' && payload.trim()) {
    return payload
  }

  return 'Withdrawal gagal dikirim.'
}

async function submit() {
  submitError.value = ''

  const parsedAmount = Number(String(amount.value || '').replace(/,/g, '').trim())
  if (!Number.isFinite(parsedAmount) || parsedAmount <= 0) {
    submitError.value = 'Jumlah withdrawal tidak valid.'
    return
  }

  const bankAccountId = selectedBankId.value ? Number(selectedBankId.value) : null
  if (!bankAccountId) {
    submitError.value = 'Pilih bank dulu di menu Ikat bank.'
    return
  }

  isSubmitting.value = true

  try {
    const payload = {
      amount: String(parsedAmount),
      bank_account_id: bankAccountId,
    }

    const { ok, data } = await requestWithdrawal(payload)

    if (!ok) {
      submitError.value = resolveSubmitErrorMessage(data)
      return
    }

    amount.value = ''
    await loadWithdrawals()
  } catch {
    submitError.value = 'Tidak bisa terhubung ke server withdrawal.'
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  loadWithdrawals()
  loadBanks()
})
</script>

<template>
  <AppShell
    body-class="withdrawal-page-body"
    main-class="withdrawal-main"
    :header-actions="backProfileHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="standardFooterItems"
    active-footer-key="profile"
  >
    <section class="withdrawal-panel" aria-label="Withdrawal summary">
      <div class="withdrawal-summary-grid">
        <article class="withdrawal-summary-card">
          <span>Deposit Balance</span>
          <strong>Rp{{ state.displayBalance }}</strong>
        </article>
        <article class="withdrawal-summary-card is-strong">
          <span>Withdrawable Balance</span>
          <strong>Rp{{ state.withdrawableBalance }}</strong>
        </article>
      </div>

    </section>

    <section v-if="flash" class="deposit-alert is-success is-autohide" aria-label="Withdrawal info">
      <p>Your withdrawal request has been placed in pending review.</p>
      <p>Request No: <strong>{{ flash.requestNo }}</strong></p>
      <p>Amount: <strong>Rp{{ flash.amount }}</strong></p>
    </section>

    <section class="withdrawal-form-panel" aria-label="Withdrawal form">
      <form class="deposit-form" novalidate @submit.prevent="submit">
        <label class="deposit-field" for="withdrawal-amount">
          <span class="deposit-field-label">Withdrawal Amount (fee withdraw 10%)</span>
          <input
            id="withdrawal-amount"
            v-model="amount"
            class="deposit-input"
            type="number"
            name="amount"
            min="4"
            step="1"
            inputmode="numeric"
            placeholder="Example: 30000"
            required
          />
        </label>

        <label class="deposit-field" for="withdrawal-wallet-address">
          <span class="deposit-field-label">Wallet Address</span>
          <input
            id="withdrawal-wallet-address"
            class="deposit-input"
            type="text"
            name="wallet_address"
            :value="bankFieldValue"
            :placeholder="
              isLoadingBanks
                ? 'Memuat daftar bank...'
                : bankError
                  ? bankError
                  : 'Pilih bank untuk withdrawal'
            "
            readonly
            required
            @click.prevent="openBankBinding"
            @focus="openBankBinding"
          />
        </label>

        <div v-if="submitError" class="deposit-alert is-error">
          <p>{{ submitError }}</p>
        </div>

        <button type="submit" class="deposit-submit-btn" :disabled="isSubmitting">
          {{ isSubmitting ? 'Mengirim...' : 'Submit Withdrawal Request' }}
        </button>
      </form>
    </section>

    <section class="deposit-history" aria-label="Withdrawal requests">
      <div class="deposit-section-head">
        <div>
          <h2>Withdrawal History</h2>
          <p>Submitted requests and their statuses</p>
        </div>
      </div>

      <div class="deposit-history-list">
        <article v-if="isLoadingRequests" class="deposit-history-item">
          <div class="deposit-history-top">
            <div>
              <strong>Memuat</strong>
              <span>Riwayat withdrawal sedang diambil</span>
            </div>
          </div>
        </article>
        <article v-else-if="requestError" class="deposit-history-item">
          <div class="deposit-history-top">
            <div>
              <strong>Gagal</strong>
              <span>{{ requestError }}</span>
            </div>
          </div>
        </article>
        <article v-else-if="!state.requests.length" class="deposit-history-item">
          <div class="deposit-history-top">
            <div>
              <strong>Belum ada data</strong>
              <span>Belum ada riwayat withdrawal</span>
            </div>
          </div>
        </article>
        <article v-for="request in state.requests" :key="request.requestNo" class="deposit-history-item">
          <div class="deposit-history-top">
            <div>
              <strong>{{ request.bankAccountNumber }}</strong>
              <span>{{ request.bankName }}</span>
            </div>
            <span class="deposit-status" :class="`is-${request.status}`">
              {{ request.status === 'approved' ? 'Approved' : request.status === 'rejected' ? 'Rejected' : 'Pending' }}
            </span>
          </div>

          <div class="deposit-history-bottom">
            <span>Rp{{ request.amount }}</span>
            <span>{{ request.createdAt }}</span>
          </div>
        </article>
      </div>
    </section>
  </AppShell>
</template>

<style>
.withdrawal-page-body {
  background:
    radial-gradient(circle at top right, rgba(105, 204, 186, 0.18), transparent 30%),
    linear-gradient(180deg, #eef8fc 0%, #def3f6 48%, #d4eeea 100%);
}

.withdrawal-main {
  padding-bottom: 184px;
}

.withdrawal-panel,
.withdrawal-form-panel {
  width: 100%;
  box-sizing: border-box;
  padding: 16px 14px;
  border-radius: 22px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(242, 250, 252, 0.96) 100%);
  border: 1px solid rgba(19, 118, 146, 0.1);
  box-shadow: 0 18px 34px rgba(28, 89, 109, 0.09);
}

.withdrawal-summary-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
}

.withdrawal-summary-card {
  padding: 14px;
  border-radius: 18px;
  background: linear-gradient(180deg, rgba(245, 252, 253, 0.98) 0%, rgba(234, 246, 249, 0.96) 100%);
  border: 1px solid rgba(18, 117, 144, 0.1);
}

.withdrawal-summary-card span {
  display: block;
  color: #6c8390;
  font-size: 0.72rem;
}

.withdrawal-summary-card strong {
  display: block;
  margin-top: 6px;
  font-size: 0.95rem;
  color: #133446;
}

.withdrawal-summary-card.is-strong {
  background: linear-gradient(135deg, rgba(17, 186, 171, 0.11) 0%, rgba(49, 141, 213, 0.11) 100%);
}

@media (max-width: 640px) {
  .withdrawal-summary-grid {
    grid-template-columns: 1fr;
  }
}
</style>
