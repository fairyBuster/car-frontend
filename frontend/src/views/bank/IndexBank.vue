<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppShell from '../../components/layout/AppShell.vue'
import { backProfileHeaderActions, marketNoticeItems, standardFooterItems } from '../../data/mockData'
import { getUserBanks } from '../../services/api'

const SELECTED_BANK_STORAGE_KEY = 'selected_withdraw_bank_id'

const route = useRoute()
const router = useRouter()

const isLoading = ref(false)
const loadError = ref('')
const banks = ref([])

const redirectPath = computed(() => {
  const value = route.query.redirect
  return typeof value === 'string' && value ? value : ''
})

const selectedBankId = computed(() => {
  const stored = localStorage.getItem(SELECTED_BANK_STORAGE_KEY)
  const asNumber = stored ? Number(stored) : NaN
  return Number.isFinite(asNumber) ? asNumber : null
})

const resolvedDefaultBankId = computed(() => {
  const defaultBank = banks.value.find((item) => item?.is_default)
  return defaultBank?.id ?? null
})

const activeBankId = computed(() => selectedBankId.value ?? resolvedDefaultBankId.value)

function formatAccount(value) {
  const raw = String(value || '').trim()
  if (!raw) {
    return '-'
  }

  const last4 = raw.slice(-4)
  const masked = raw.length > 4 ? '*'.repeat(Math.min(8, raw.length - 4)) : ''
  return masked ? `${masked}${last4}` : last4
}

function buildAddLink() {
  const query = {}
  if (redirectPath.value) {
    query.redirect = redirectPath.value
  }
  return { name: 'banks-add', query }
}

function selectBank(bank) {
  if (!bank?.id) {
    return
  }

  localStorage.setItem(SELECTED_BANK_STORAGE_KEY, String(bank.id))

  if (redirectPath.value) {
    router.push(redirectPath.value)
  }
}

async function loadBanks() {
  isLoading.value = true
  loadError.value = ''

  try {
    const { ok, data } = await getUserBanks()

    if (!ok) {
      loadError.value = 'Daftar bank gagal dimuat.'
      banks.value = []
      return
    }

    banks.value = Array.isArray(data) ? data : []
  } catch {
    loadError.value = 'Tidak bisa terhubung ke server bank.'
    banks.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadBanks()
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
    <section class="bank-panel" aria-label="Bank accounts">
      <div class="bank-panel-head">
        <div>
          <h1 class="bank-title">Ikat bank</h1>
          <p class="bank-subtitle">Pilih rekening bank untuk withdrawal.</p>
        </div>
        <RouterLink :to="buildAddLink()" class="bank-add-link">Tambah bank</RouterLink>
      </div>

      <div class="bank-list">
        <article v-if="isLoading" class="bank-item">
          <div class="bank-item-copy">
            <strong>Memuat</strong>
            <span>Daftar bank sedang diambil</span>
          </div>
        </article>
        <article v-else-if="loadError" class="bank-item">
          <div class="bank-item-copy">
            <strong>Gagal</strong>
            <span>{{ loadError }}</span>
          </div>
        </article>
        <article v-else-if="!banks.length" class="bank-item">
          <div class="bank-item-copy">
            <strong>Belum ada bank</strong>
            <span>Tambahkan bank dulu untuk withdrawal.</span>
          </div>
        </article>
        <button
          v-for="bank in banks"
          v-else
          :key="bank.id"
          type="button"
          class="bank-item"
          :class="{ 'is-active': bank.id === activeBankId }"
          @click="selectBank(bank)"
        >
          <div class="bank-item-copy">
            <div class="bank-item-row">
              <strong class="bank-item-name">{{ bank.bank_name || '-' }}</strong>
              <span v-if="bank.is_default" class="bank-badge">Default</span>
              <span v-else-if="bank.id === selectedBankId" class="bank-badge is-selected">Selected</span>
            </div>
            <span class="bank-item-meta">
              {{ formatAccount(bank.account_number) }} · {{ bank.account_name || '-' }}
            </span>
          </div>
          <span class="bank-item-action">Pilih</span>
        </button>
      </div>
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

.bank-list {
  margin-top: 14px;
  display: grid;
  gap: 10px;
}

.bank-item {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 14px;
  border-radius: 18px;
  background: #ffffff;
  border: 1px solid rgba(18, 117, 144, 0.12);
  box-shadow: 0 12px 22px rgba(9, 64, 86, 0.07);
  text-align: left;
}

button.bank-item {
  cursor: pointer;
}

.bank-item.is-active {
  border-color: rgba(17, 186, 171, 0.35);
  box-shadow: 0 12px 26px rgba(17, 186, 171, 0.14);
}

.bank-item-copy strong {
  display: block;
  color: #133446;
  font-size: 0.93rem;
}

.bank-item-copy span {
  display: block;
  margin-top: 5px;
  color: #6c8390;
  font-size: 0.78rem;
}

.bank-item-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.bank-badge {
  padding: 4px 8px;
  border-radius: 999px;
  background: rgba(49, 141, 213, 0.12);
  color: #1a5c8a;
  font-size: 0.68rem;
  font-weight: 700;
}

.bank-badge.is-selected {
  background: rgba(17, 186, 171, 0.14);
  color: #0b6e66;
}

.bank-item-action {
  font-size: 0.78rem;
  font-weight: 700;
  color: #0b6e66;
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
