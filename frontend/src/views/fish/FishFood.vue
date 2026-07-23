<script setup>
import { computed, onMounted, ref } from 'vue'
import AppShell from '../../components/layout/AppShell.vue'
import FishFoodPurchaseModal from '../../components/modals/FishFoodPurchaseModal.vue'
import {
  defaultHeaderActions,
  marketNoticeItems,
  standardFooterItems,
} from '../../data/mockData'
import { getProducts } from '../../services/api'

const isModalOpen = ref(false)
const alertState = ref(null)
const selectedFishKey = ref('')
const isLoadingProducts = ref(false)
const productError = ref('')
const state = ref({
  balance: resolveUserBalance(),
  cards: [],
})

const selectedItem = computed(
  () => state.value.cards.find((card) => card.key === selectedFishKey.value) ?? null,
)

function resolveUserBalance() {
  try {
    const rawUser = localStorage.getItem('auth_user')
    const user = rawUser ? JSON.parse(rawUser) : null
    const balance = Number(user?.balance)

    return Number.isFinite(balance) ? balance : 0
  } catch {
    return 0
  }
}

function extractProducts(payload) {
  if (Array.isArray(payload)) {
    return payload
  }

  if (!payload || typeof payload !== 'object') {
    return []
  }

  if (Array.isArray(payload.results)) {
    const nestedResults = payload.results.flatMap((item) => extractProducts(item))
    return nestedResults.length ? nestedResults : payload.results
  }

  return []
}

function normalizeImage(value) {
  return value || '/logo.png'
}

function resolveLevel(rank) {
  if (rank >= 5) {
    return { key: 'premium', label: `Level ${rank}` }
  }

  if (rank >= 3) {
    return { key: 'mid', label: `Level ${rank}` }
  }

  return { key: 'starter', label: `Level ${Math.max(1, rank)}` }
}

function buildUnlockMessage(isUnlocked, minRequiredRank) {
  if (isUnlocked) {
    return 'Syarat rank sudah terpenuhi.'
  }

  if (minRequiredRank > 0) {
    return `Buka setelah mencapai rank ${minRequiredRank}.`
  }

  return 'Kartu akan terbuka saat syarat produk terpenuhi.'
}

function mapProductToFishFoodCard(product) {
  const userBalance = state.value.balance
  const productPrice = Number(product.price) || 0
  const minRequiredRank = Number(product.min_required_rank) || 1
  const level = resolveLevel(minRequiredRank)
  const isUnlocked = !product.require_min_rank_enabled || minRequiredRank <= 1

  return {
    key: String(product.id),
    name: product.name || 'Unnamed Product',
    image: normalizeImage(product.image),
    imageAlt: product.name || 'Product image',
    tags: [product.golongan || 'Product', `${product.duration || 0} Days`],
    levelKey: level.key,
    levelLabel: level.label,
    isUnlocked,
    referralRequirement: 0,
    rewardDays: Number(product.duration) || 5,
    meetsBalance: userBalance >= productPrice,
    meetsReferrals: true,
    unlockBalance: productPrice.toFixed(2),
    bonusDays: 0,
    unlockMessage: buildUnlockMessage(isUnlocked, minRequiredRank),
    purchasePrice: productPrice,
  }
}

async function loadProducts() {
  isLoadingProducts.value = true
  productError.value = ''

  try {
    const { ok, data } = await getProducts()

    if (!ok) {
      productError.value = 'Produk fish food gagal dimuat.'
      return
    }

    state.value.cards = extractProducts(data)
      .sort((left, right) => (Number(left.price) || 0) - (Number(right.price) || 0))
      .map(mapProductToFishFoodCard)
  } catch (error) {
    productError.value =
      error instanceof Error ? error.message : 'Tidak bisa terhubung ke server produk.'
  } finally {
    isLoadingProducts.value = false
  }
}

function openModal(key) {
  selectedFishKey.value = key
  isModalOpen.value = true
}

function confirmPurchase() {
  const item = selectedItem.value
  if (!item) {
    return
  }

  state.value.balance -= Number(item.purchasePrice)
  item.bonusDays += 5
  item.isUnlocked = true
  alertState.value = {
    type: 'success',
    message: `${item.name} received 5 days of fish food.`,
    amount: Number(item.purchasePrice).toFixed(2),
    balance: state.value.balance.toFixed(2),
    bonusDays: item.bonusDays,
  }

  window.setTimeout(() => {
    if (alertState.value?.message.includes(item.name)) {
      alertState.value = null
    }
  }, 3200)
}

onMounted(() => {
  loadProducts()
})
</script>
<!-- 
<template>
  <AppShell
    body-class="fish-food-page-body"
    main-class="fish-food-main"
    :header-actions="defaultHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="standardFooterItems"
    active-footer-key="home"
  >
    <section v-if="alertState" class="deposit-alert is-success is-autohide" aria-label="Food info">
      <p>{{ alertState.message }}</p>
      <p>
        Amount: <strong>Rp{{ alertState.amount }}</strong> · Remaining balance:
        <strong>Rp{{ alertState.balance }}</strong>
      </p>
      <p>Total food entitlement: <strong>{{ alertState.bonusDays }} days</strong></p>
    </section>

    <section class="fish-food-grid" aria-label="Fish food cards">
      <p v-if="isLoadingProducts" class="dashboard-fish-empty">Memuat produk...</p>
      <p v-else-if="productError" class="dashboard-fish-empty">{{ productError }}</p>
      <p v-else-if="!state.cards.length" class="dashboard-fish-empty">Belum ada produk fish food.</p>
      <article
        v-for="card in state.cards"
        :key="card.key"
        class="fish-food-card"
        :class="card.isUnlocked ? 'is-unlocked' : 'is-locked'"
      >
        <div class="fish-food-card-top">
          <img :src="card.image" :alt="card.imageAlt" class="fish-food-image" />

          <div class="fish-food-card-head">
            <div class="fish-food-tags" aria-hidden="true">
              <span v-for="tag in card.tags" :key="tag" class="fish-food-tag">{{ tag }}</span>
            </div>

            <h2>{{ card.name }}</h2>
            <span class="fish-food-level" :class="`is-${card.levelKey}`">{{ card.levelLabel }}</span>
            <span class="fish-food-status" :class="card.isUnlocked ? 'is-open' : 'is-locked'">
              {{ card.isUnlocked ? 'Kart Acik' : 'Kart Locked' }}
            </span>
          </div>
        </div>

        <p class="fish-food-offer">
          {{ card.referralRequirement }} With reference
          <strong>{{ card.rewardDays }} days of food</strong> earned.
        </p>

        <div class="fish-food-meta">
          <span :class="card.meetsBalance ? 'is-ready' : 'is-pending'">
            Deposit requied : Rp{{ card.unlockBalance }}+
          </span>
          <span :class="card.meetsReferrals ? 'is-ready' : 'is-pending'">
            Referral requirement: {{ card.referralRequirement }} users
          </span>
        </div>

        <div class="fish-food-bonus-box">
          <strong v-if="card.bonusDays > 0">{{ card.bonusDays }} days of foodn var.</strong>
          <strong v-else>Gives 5 free days of food on first activation.</strong>
          <span>{{ card.unlockMessage }}</span>
        </div>

        <div class="fish-food-actions">
          <span class="fish-food-note">
            {{
              card.isUnlocked
                ? 'Card is active. You can add 5 more days of food using your balance.'
                : 'Meet the requirements or buy to unlock the card and add 5 days of food.'
            }}
          </span>
          <div class="fish-food-purchase">
            <span class="fish-food-price">Rp{{ Number(card.purchasePrice).toFixed(2) }} / 5 day</span>
            <button type="button" class="fish-food-buy-button" @click="openModal(card.key)">
              Buy Fish Food
            </button>
          </div>
        </div>
      </article>
    </section>

    <FishFoodPurchaseModal
      v-model="isModalOpen"
      :item="selectedItem"
      :balance="state.balance"
      @confirm="confirmPurchase"
    />
  </AppShell>
</template> -->

<style>
.fish-food-page-body {
  min-height: 100vh;
}

.fish-food-main {
  align-items: stretch;
}

.fish-food-grid {
  width: 100%;
  max-width: 430px;
  display: grid;
  gap: 12px;
}

.fish-food-card {
  padding: 12px;
  border-radius: 22px;
  background:
    radial-gradient(circle at top right, rgba(184, 233, 243, 0.14), transparent 38%),
    rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(19, 110, 132, 0.12);
  box-shadow: 0 14px 28px rgba(13, 88, 109, 0.1);
}

.fish-food-card.is-locked {
  background:
    radial-gradient(circle at top right, rgba(210, 224, 228, 0.14), transparent 38%),
    rgba(255, 255, 255, 0.88);
}

.fish-food-card.is-locked .fish-food-image {
  filter: saturate(0.72);
}

.fish-food-card-top {
  display: grid;
  grid-template-columns: 112px minmax(0, 1fr);
  gap: 12px;
  align-items: center;
}

.fish-food-image {
  display: block;
  width: 100%;
  aspect-ratio: 3 / 2;
  border-radius: 16px;
  object-fit: cover;
  object-position: center;
  box-shadow: 0 10px 18px rgba(14, 88, 107, 0.14);
}

.fish-food-card-head {
  min-width: 0;
}

.fish-food-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  margin-bottom: 7px;
}

.fish-food-tag {
  display: inline-flex;
  align-items: center;
  min-height: 19px;
  padding: 0 7px;
  border-radius: 999px;
  background: linear-gradient(180deg, #f5fbfd 0%, #e8f5fb 100%);
  border: 1px solid rgba(19, 124, 150, 0.12);
  color: #225468;
  font-size: 0.58rem;
  font-weight: 700;
}

.fish-food-card-head h2 {
  margin: 0;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.06rem;
  line-height: 1.1;
  color: #123045;
}

.fish-food-level {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-top: 8px;
  min-height: 24px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 700;
}

.fish-food-level.is-easy {
  background: rgba(59, 181, 122, 0.12);
  color: #23734d;
}

.fish-food-level.is-medium {
  background: rgba(255, 174, 63, 0.16);
  color: #9a5b08;
}

.fish-food-level.is-hard {
  background: rgba(228, 87, 87, 0.14);
  color: #983539;
}

.fish-food-status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  margin-top: 7px;
  min-height: 24px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 0.66rem;
  font-weight: 700;
}

.fish-food-status.is-open {
  background: rgba(18, 190, 146, 0.12);
  color: #187458;
}

.fish-food-status.is-locked {
  background: rgba(129, 151, 160, 0.15);
  color: #566d76;
}

.fish-food-offer {
  margin: 12px 0 8px;
  color: #264652;
  font-size: 0.82rem;
  line-height: 1.5;
}

.fish-food-offer strong {
  color: #0f6f8c;
}

.fish-food-meta {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 7px;
}

.fish-food-meta span {
  display: block;
  padding: 9px 10px;
  border-radius: 14px;
  background: linear-gradient(180deg, #f5fbfd 0%, #edf8fb 100%);
  color: #41606c;
  font-size: 0.67rem;
  font-weight: 700;
  line-height: 1.3;
  text-align: center;
}

.fish-food-meta span.is-ready {
  color: #1d6f56;
  background: linear-gradient(180deg, #eefbf5 0%, #e7f8ef 100%);
}

.fish-food-meta span.is-pending {
  color: #7b5a20;
  background: linear-gradient(180deg, #fff8eb 0%, #fff2d8 100%);
}

.fish-food-bonus-box {
  margin-top: 10px;
  padding: 11px 12px;
  border-radius: 16px;
  background: linear-gradient(180deg, #f8fcff 0%, #eef8fb 100%);
  border: 1px solid rgba(20, 110, 132, 0.1);
  display: grid;
  gap: 5px;
}

.fish-food-bonus-box strong {
  color: #153b4b;
  font-size: 0.74rem;
  line-height: 1.3;
}

.fish-food-bonus-box span {
  color: #58727d;
  font-size: 0.68rem;
  line-height: 1.4;
}

.fish-food-actions {
  margin-top: 10px;
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 10px;
  align-items: center;
}

.fish-food-note {
  color: #55737d;
  font-size: 0.7rem;
  line-height: 1.4;
  font-weight: 600;
}

.fish-food-buy-button {
  min-width: 112px;
  height: 42px;
  padding: 0 18px;
  border: 0;
  border-radius: 14px;
  background: linear-gradient(90deg, #10c39a 0%, #1aa9b4 100%);
  color: #ffffff;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.84rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 12px 22px rgba(15, 177, 146, 0.2);
}

.fish-food-buy-button:hover {
  transform: translateY(-1px);
}

.fish-food-purchase {
  display: grid;
  gap: 6px;
  justify-items: end;
}

.fish-food-price {
  color: #0f6f8c;
  font-size: 0.72rem;
  font-weight: 800;
  line-height: 1.2;
}
</style>
