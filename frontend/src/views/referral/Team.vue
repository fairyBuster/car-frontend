<script setup>
import { computed, onMounted, ref } from 'vue'
import AppShell from '../../components/layout/AppShell.vue'
import { backProfileHeaderActions, marketNoticeItems, standardFooterItems } from '../../data/mockData'
import { getDownlineOverview } from '../../services/api'

const activeLevel = ref(1)
const isLoading = ref(false)
const loadError = ref('')
const page = ref(1)
const pageSize = 10
const state = ref({
  memberCount: 0,
  activeCount: 0,
  totalCommission: '0.00',
  members: [],
})

function formatAmount(value) {
  const normalized = typeof value === 'string' ? value.replace(/,/g, '').trim() : value
  const amount = Number(normalized)

  if (!Number.isFinite(amount)) {
    return '0'
  }

  return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(amount)
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
  return `${yyyy}-${mm}-${dd}`
}

function findLastProductActivationDate(member) {
  if (!Array.isArray(member?.transaction_history)) {
    return '-'
  }

  const latestPurchaseTransaction = member.transaction_history
    .filter((item) => item?.type === 'PURCHASE_COMMISSION')
    .sort((left, right) => new Date(right.created_at).getTime() - new Date(left.created_at).getTime())[0]

  return formatDate(latestPurchaseTransaction?.created_at)
}

function resolveLevelSummary(payload, level) {
  if (!payload || typeof payload !== 'object' || !Array.isArray(payload.levels)) {
    return null
  }

  return payload.levels.find((item) => Number(item?.level) === Number(level)) ?? payload.levels[0] ?? null
}

function calculateMemberCommission(member) {
  const profitRaw =
    typeof member?.total_profit_commission === 'string'
      ? member.total_profit_commission.replace(/,/g, '').trim()
      : member?.total_profit_commission
  const purchaseRaw =
    typeof member?.total_purchase_commission === 'string'
      ? member.total_purchase_commission.replace(/,/g, '').trim()
      : member?.total_purchase_commission

  const amount = (Number(profitRaw) || 0) + (Number(purchaseRaw) || 0)
  return formatAmount(amount)
}

function mapMember(member) {
  return {
    id: member.phone || member.username || member.referral_code || '-',
    displayName: member.phone || '-',
    isActive: Boolean(member.is_active),
    statusLabel: 'User',
    joinedAt: formatDate(member.registration_date),
    activeInvestments: Number(member.active_investments) || 0,
    lastProductActivationAt: findLastProductActivationDate(member),
    commission: calculateMemberCommission(member),
  }
}

function onLevelChange(event) {
  page.value = 1
  loadTeam(Number(event.target.value) || 1)
}

const visibleMembers = computed(() => state.value.members.slice(0, page.value * pageSize))
const canLoadMore = computed(() => state.value.members.length > visibleMembers.value.length)

function loadMoreMembers() {
  if (!canLoadMore.value) {
    return
  }

  page.value += 1
}

async function loadTeam(level = activeLevel.value) {
  isLoading.value = true
  loadError.value = ''
  activeLevel.value = level
  page.value = 1

  try {
    const { ok, data } = await getDownlineOverview({ level })

    if (!ok) {
      loadError.value = 'Data team gagal dimuat.'
      state.value = {
        memberCount: 0,
        activeCount: 0,
        totalCommission: '0.00',
        members: [],
      }
      return
    }

    const levelSummary = resolveLevelSummary(data, level)

    if (!levelSummary) {
      state.value = {
        memberCount: 0,
        activeCount: 0,
        totalCommission: '0.00',
        members: [],
      }
      return
    }

    state.value = {
      memberCount: Number(levelSummary.member_count) || 0,
      activeCount: Number(levelSummary.active_member_count) || 0,
      totalCommission: formatAmount(
        (Number(data.total_profit_commission) || 0) +
          (Number(data.total_purchase_commission) || 0) +
          (Number(data.total_earned_commission) || 0),
      ),
      members: Array.isArray(levelSummary.members) ? levelSummary.members.map(mapMember) : [],
    }
  } catch (error) {
    loadError.value =
      error instanceof Error ? error.message : 'Tidak bisa terhubung ke server team.'
    state.value = {
      memberCount: 0,
      activeCount: 0,
      totalCommission: '0.00',
      members: [],
    }
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadTeam(1)
})
</script>

<template>
  <AppShell
    body-class="team-page-body"
    main-class="team-main"
    :header-actions="backProfileHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="standardFooterItems"
    active-footer-key="invite"
  >
    <section class="team-summary" aria-label="Team ozeti">
      <div class="team-summary-box">
        <span>Filter Level</span>
        <select class="deposit-input team-level-select" :value="activeLevel" @change="onLevelChange">
          <option :value="1">Tim 1</option>
          <option :value="2">Tim 2</option>
          <option :value="3">Tim 3</option>
        </select>
      </div>
      <div class="team-summary-box">
        <span>Total Team</span>
        <strong>{{ state.memberCount }}</strong>
      </div>
      <div class="team-summary-box">
        <span>Active Team</span>
        <strong>{{ state.activeCount }}</strong>
      </div>
      <div class="team-summary-box">
        <span>Komisi Saya</span>
        <strong>Rp {{ state.totalCommission }}</strong>
      </div>
    </section>

    <section v-if="isLoading" class="team-empty">
      <h1>Memuat Team</h1>
      <p>Data level {{ activeLevel }} sedang diambil.</p>
    </section>

    <section v-else-if="loadError" class="team-empty">
      <h1>Gagal Memuat Team</h1>
      <p>{{ loadError }}</p>
    </section> 

    <section v-else-if="state.members.length" class="team-list" aria-label="Team listesi">
      <article v-for="member in visibleMembers" :key="member.id" class="team-item">
        <div class="team-item-main">
          <div class="team-item-avatar">{{ member.displayName.slice(0, 2).toUpperCase() }}</div>

          <div class="team-item-copy">
            <div class="team-item-top">
              <strong>{{ member.displayName }}</strong>
              <span class="team-item-status" :class="member.isActive ? 'is-sold' : 'is-idle'">
                {{ member.statusLabel }}
              </span>
            </div>

            <div class="team-item-meta">
              <span>Joined {{ member.joinedAt }}</span>
              <span>{{ member.activeInvestments }} produk aktif</span>
            </div>

            <p class="team-item-note">
              Aktif produk terakhir: {{ member.lastProductActivationAt }}
            </p>
          </div>
        </div>
      </article>
      <button v-if="canLoadMore" type="button" class="team-load-more" @click="loadMoreMembers">Muat lainnya</button>
    </section>

    <section v-else class="team-empty">
      <h1>Team Level {{ activeLevel }} Masih Kosong</h1>
      <p>Anggota referral level ini akan tampil di sini.</p>
    </section>
  </AppShell>
</template>

<style>
.team-page-body {
  min-height: 100vh;
}

.team-main {
  align-items: stretch;
  gap: 12px;
}

.team-summary {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  align-items: stretch;
  justify-content: space-between;
  gap: 12px;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  overflow-x: hidden;
  padding-bottom: 2px;
}

.team-summary-box {
  display: grid;
  flex: 0 1 auto;
  width: fit-content;
  max-width: calc((100% - 36px) / 4);
  min-width: 0;
  gap: 5px;
  padding: 10px 8px;
  border-radius: 16px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.96) 0%, rgba(240, 250, 252, 0.96) 100%);
  border: 1px solid rgba(17, 111, 134, 0.1);
  box-shadow: 0 12px 22px rgba(10, 73, 92, 0.08);
}

.team-summary-box span {
  color: #6a8894;
  font-size: 0.66rem;
  font-weight: 700;
  line-height: 1.2;
}

.team-summary-box strong {
  display: block;
  color: #123045;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.92rem;
  line-height: 1.1;
  white-space: normal;
  overflow-wrap: anywhere;
}

.team-level-select {
  min-height: 36px;
  padding: 6px 10px;
  font-size: 0.78rem;
}

.team-list {
  display: grid;
  gap: 8px;
  width: calc(100% + 10px);
  margin-inline: -5px;
}

.team-item {
  padding: 9px 10px;
  border-radius: 16px;
  background:
    radial-gradient(circle at top right, rgba(160, 234, 223, 0.18), transparent 36%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(244, 251, 253, 0.98) 100%);
  border: 1px solid rgba(18, 112, 134, 0.1);
  box-shadow: 0 14px 28px rgba(12, 79, 98, 0.08);
}

.team-item-main {
  display: grid;
  grid-template-columns: 38px minmax(0, 1fr);
  gap: 8px;
  align-items: center;
}

.team-item-avatar {
  display: grid;
  place-items: center;
  width: 38px;
  aspect-ratio: 1;
  border-radius: 12px;
  background: linear-gradient(180deg, #1db8c0 0%, #0e8cab 100%);
  color: #ffffff;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.74rem;
  font-weight: 800;
  box-shadow: 0 12px 20px rgba(14, 140, 171, 0.22);
}

.team-item-copy {
  min-width: 0;
  display: grid;
  gap: 5px;
}

.team-item-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.team-item-top strong {
  min-width: 0;
  color: #123045;
  font-size: 0.88rem;
  line-height: 1.2;
}

.team-item-status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 22px;
  padding: 0 9px;
  border-radius: 999px;
  font-size: 0.6rem;
  font-weight: 800;
  white-space: nowrap;
}

.team-item-status.is-sold {
  background: rgba(18, 190, 146, 0.12);
  color: #187458;
}

.team-item-status.is-idle {
  background: rgba(255, 178, 69, 0.14);
  color: #9f620f;
}

.team-item-meta,
.team-item-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.team-item-meta span,
.team-item-stats span {
  display: inline-flex;
  align-items: center;
  min-height: 22px;
  padding: 0 8px;
  border-radius: 999px;
  background: linear-gradient(180deg, #f4fbfd 0%, #edf8fb 100%);
  color: #4f6c77;
  font-size: 0.62rem;
  font-weight: 700;
  line-height: 1.2;
}

.team-item-stats strong {
  margin-left: 4px;
  color: #0f6f8c;
}

.team-item-note {
  margin: 0;
  color: #607985;
  font-size: 0.66rem;
  line-height: 1.25;
  font-weight: 600;
}

.team-load-more {
  width: 100%;
  border: 0;
  border-radius: 16px;
  min-height: 44px;
  padding: 0 16px;
  background: linear-gradient(135deg, rgba(22, 185, 170, 0.14) 0%, rgba(45, 141, 213, 0.14) 100%);
  color: #0b6e66;
  font-weight: 800;
}

.team-empty {
  padding: 20px 18px;
  border-radius: 22px;
  background: linear-gradient(135deg, rgba(30, 134, 168, 0.9) 0%, rgba(58, 203, 181, 0.9) 100%);
  color: #ffffff;
  box-shadow: 0 18px 38px rgba(12, 83, 106, 0.16);
}

.team-empty h1 {
  margin: 0 0 8px;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.5rem;
  line-height: 1.1;
}

.team-empty p {
  margin: 0;
  font-size: 0.9rem;
  line-height: 1.5;
}

@media (max-width: 640px) {
  .team-summary {
    justify-content: flex-start;
    gap: 12px;
    flex-wrap: wrap;
  }

  .team-summary-box {
    flex: 0 0 auto;
    width: fit-content;
    min-width: 70px;
    padding: 9px 7px;
  }

  .team-summary-box strong {
    font-size: 0.82rem;
  }

  .team-level-select {
    min-height: 32px;
    padding: 5px 8px;
    font-size: 0.74rem;
  }

  .team-item {
    padding: 8px 9px;
  }

  .team-item-main {
    grid-template-columns: 34px minmax(0, 1fr);
    gap: 7px;
  }

  .team-item-avatar {
    width: 34px;
    border-radius: 11px;
    font-size: 0.7rem;
  }

  .team-item-top {
    align-items: center;
    flex-direction: row;
  }
}
</style>
