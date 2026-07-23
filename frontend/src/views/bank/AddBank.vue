<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppShell from '../../components/layout/AppShell.vue'
import { backProfileHeaderActions, marketNoticeItems, standardFooterItems } from '../../data/mockData'
import { addUserBank, getBanks } from '../../services/api'

const route = useRoute()
const router = useRouter()

const isLoadingBanks = ref(false)
const bankError = ref('')
const bankOptions = ref([])

const isSubmitting = ref(false)
const submitError = ref('')

const bankId = ref(null)
const accountName = ref('')
const accountNumber = ref('')
const phone = ref('')
const isDefault = ref(false)

const redirectPath = computed(() => {
  const value = route.query.redirect
  return typeof value === 'string' && value ? value : ''
})

function normalizeCurrencyBankList(value) {
  if (!Array.isArray(value)) {
    return []
  }

  return value.filter((item) => item?.is_active !== false)
}

async function loadBankOptions() {
  isLoadingBanks.value = true
  bankError.value = ''

  try {
    const { ok, data } = await getBanks({ currency_code: 'IDR' })

    if (!ok) {
      bankError.value = 'Daftar bank aktif gagal dimuat.'
      bankOptions.value = []
      return
    }

    bankOptions.value = normalizeCurrencyBankList(data)

    if (!bankId.value && bankOptions.value.length) {
      bankId.value = bankOptions.value[0].id
    }
  } catch {
    bankError.value = 'Tidak bisa terhubung ke server bank.'
    bankOptions.value = []
  } finally {
    isLoadingBanks.value = false
  }
}

function buildBackLink() {
  const query = {}
  if (redirectPath.value) {
    query.redirect = redirectPath.value
  }
  return { name: 'banks', query }
}

function resolveSubmitErrorMessage(payload) {
  if (payload && typeof payload === 'object') {
    const nonFieldErrors = payload.non_field_errors
    if (Array.isArray(nonFieldErrors) && nonFieldErrors.length) {
      const rawMessage = String(nonFieldErrors[0])

      if (/must make a unique set/i.test(rawMessage)) {
        return 'Bank ini sudah terdaftar.'
      }

      return rawMessage
    }

    if (payload.detail) {
      return String(payload.detail)
    }
  }

  if (typeof payload === 'string' && payload.trim()) {
    return payload
  }

  return 'Bank gagal disimpan.'
}

async function submit() {
  submitError.value = ''

  const resolvedBankId = Number(bankId.value)
  if (!Number.isFinite(resolvedBankId)) {
    submitError.value = 'Bank wajib dipilih.'
    return
  }

  if (!accountName.value.trim()) {
    submitError.value = 'Nama pemilik rekening wajib diisi.'
    return
  }

  if (!accountNumber.value.trim()) {
    submitError.value = 'Nomor rekening wajib diisi.'
    return
  }

  if (!phone.value.trim()) {
    submitError.value = 'Nomor telepon wajib diisi.'
    return
  }

  isSubmitting.value = true

  try {
    const payload = {
      bank: resolvedBankId,
      account_name: accountName.value.trim(),
      account_number: accountNumber.value.trim(),
      phone: phone.value.trim(),
      is_default: Boolean(isDefault.value),
    }

    const { ok, data } = await addUserBank(payload)

    if (!ok) {
      submitError.value = resolveSubmitErrorMessage(data)
      return
    }

    router.push(buildBackLink())
  } catch {
    submitError.value = 'Tidak bisa terhubung ke server bank.'
  } finally {
    isSubmitting.value = false
  }
}

onMounted(() => {
  loadBankOptions()
})
</script>

<template>
  <AppShell
    body-class="bank-page-body"
    main-class="bank-main"
    :header-actions="backProfileHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="standardFooterItems"
    active-footer-key="home"
  >
    <section class="bank-panel" aria-label="Add bank">
      <div class="bank-panel-head">
        <div>
          <h1 class="bank-title">Tambah bank</h1>
          <p class="bank-subtitle">Masukkan data bank untuk kebutuhan withdrawal.</p>
        </div>
        <RouterLink :to="buildBackLink()" class="bank-add-link">Kembali</RouterLink>
      </div>

      <div v-if="bankError" class="deposit-alert is-error">
        <p>{{ bankError }}</p>
      </div>

      <form class="bank-form" novalidate @submit.prevent="submit">
        <label class="deposit-field" for="bank-select">
          <span class="deposit-field-label">Bank</span>
          <select id="bank-select" v-model.number="bankId" class="deposit-input" :disabled="isLoadingBanks" required>
            <option v-for="bank in bankOptions" :key="bank.id" :value="bank.id">
              {{ bank.name }} ({{ bank.code }})
            </option>
          </select>
        </label>

        <label class="deposit-field" for="bank-account-name">
          <span class="deposit-field-label">Account Name</span>
          <input
            id="bank-account-name"
            v-model="accountName"
            class="deposit-input"
            type="text"
            name="account_name"
            autocomplete="off"
            required
          />
        </label>

        <label class="deposit-field" for="bank-account-number">
          <span class="deposit-field-label">Account Number</span>
          <input
            id="bank-account-number"
            v-model="accountNumber"
            class="deposit-input"
            type="text"
            inputmode="numeric"
            name="account_number"
            autocomplete="off"
            required
          />
        </label>

        <label class="deposit-field" for="bank-phone">
          <span class="deposit-field-label">Phone</span>
          <input
            id="bank-phone"
            v-model="phone"
            class="deposit-input"
            type="text"
            inputmode="tel"
            name="phone"
            autocomplete="off"
            required
          />
        </label>

        <label class="bank-checkbox" for="bank-default">
          <input id="bank-default" v-model="isDefault" type="checkbox" />
          <span>Jadikan default</span>
        </label>

        <div v-if="submitError" class="deposit-alert is-error">
          <p>{{ submitError }}</p>
        </div>

        <button type="submit" class="deposit-submit-btn" :disabled="isSubmitting">
          {{ isSubmitting ? 'Menyimpan...' : 'Simpan bank' }}
        </button>
      </form>
    </section>
  </AppShell>
</template>

<style>
.bank-page-body {
  background:
    radial-gradient(circle at top right, rgba(105, 204, 186, 0.18), transparent 30%),
    linear-gradient(180deg, #eef8fc 0%, #def3f6 48%, #d4eeea 100%);
}

.bank-main {
  padding-bottom: 184px;
}

.bank-panel {
  width: 100%;
  box-sizing: border-box;
  padding: 16px 14px;
  border-radius: 22px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.99) 0%, rgba(242, 250, 252, 0.96) 100%);
  border: 1px solid rgba(19, 118, 146, 0.1);
  box-shadow: 0 18px 34px rgba(28, 89, 109, 0.09);
}

.bank-panel-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}

.bank-title {
  margin: 0;
  font-size: 1.05rem;
  color: #133446;
}

.bank-subtitle {
  margin: 6px 0 0;
  color: #5b7788;
  font-size: 0.82rem;
}

.bank-add-link {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 9px 12px;
  border-radius: 999px;
  border: 1px solid rgba(19, 118, 146, 0.18);
  background: rgba(255, 255, 255, 0.9);
  color: #12506b;
  text-decoration: none;
  font-size: 0.78rem;
  font-weight: 600;
  white-space: nowrap;
}

.bank-form {
  display: grid;
  gap: 12px;
}

.bank-checkbox {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  color: #133446;
  font-weight: 600;
  font-size: 0.85rem;
}

.bank-checkbox input {
  width: 18px;
  height: 18px;
}

@media (max-width: 640px) {
  .bank-panel-head {
    flex-direction: column;
    align-items: stretch;
  }

  .bank-add-link {
    width: 100%;
  }
}
</style>
