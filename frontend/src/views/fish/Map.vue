<script setup>
import { computed, onBeforeUnmount, ref } from 'vue'
import AppShell from '../../components/layout/AppShell.vue'
import CatchFishModal from '../../components/modals/CatchFishModal.vue'
import {
  defaultHeaderActions,
  getMapData,
  marketNoticeItems,
  standardFooterItems,
} from '../../data/mockData'

const state = ref(getMapData())
const isModalOpen = ref(false)
const selectedFishKey = ref('')
const now = ref(Date.now())

const timer = window.setInterval(() => {
  now.value = Date.now()
}, 1000)

const selectedFish = computed(
  () => state.value.cards.find((card) => card.key === selectedFishKey.value) ?? null,
)

function remainingSeconds(item) {
  if (!item.nextAvailableAt) {
    return 0
  }

  return Math.max(0, Math.ceil((new Date(item.nextAvailableAt).getTime() - now.value) / 1000))
}

function formatCountdown(seconds) {
  const safe = Math.max(0, seconds)
  const hours = String(Math.floor(safe / 3600)).padStart(2, '0')
  const minutes = String(Math.floor((safe % 3600) / 60)).padStart(2, '0')
  const secondsText = String(safe % 60).padStart(2, '0')
  return `${hours}:${minutes}:${secondsText}`
}

function isCatchReady(item) {
  return item.isUnlocked && item.foodCount > 0 && remainingSeconds(item) === 0
}

function buttonLabel(item) {
  if (!item.isUnlocked) {
    return 'Locked'
  }
  if (item.foodCount <= 0) {
    return 'No Food'
  }
  if (remainingSeconds(item) > 0) {
    return 'Wait 24 Hours'
  }
  return 'Catch Fish'
}

function timerText(item) {
  if (!item.isUnlocked) {
    return 'This fish is not unlocked yet.'
  }
  if (item.foodCount <= 0) {
    return 'You have no fish food left.'
  }
  if (remainingSeconds(item) > 0) {
    return `Wait ${formatCountdown(remainingSeconds(item))} to fish again.`
  }
  return 'Ready to fish today.'
}

function openModal(key) {
  selectedFishKey.value = key
  isModalOpen.value = true
}

function onCaught(key) {
  const card = state.value.cards.find((item) => item.key === key)
  if (!card || card.foodCount <= 0) {
    return
  }

  card.foodCount -= 1
  card.nextAvailableAt = new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString()
}

onBeforeUnmount(() => {
  window.clearInterval(timer)
})
</script>
<!-- 
<template>
  <AppShell
    body-class="map-page-body"
    main-class="map-main"
    :header-actions="defaultHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="standardFooterItems"
    active-footer-key="home"
  >
    <section class="map-grid" aria-label="Fish habitats">
      <article
        v-for="card in state.cards"
        :id="`map-${card.key}`"
        :key="card.key"
        class="map-card"
      >
        <img :src="card.habitatImage" :alt="`${card.name} habitat`" class="map-card-image" />

        <div class="map-card-body">
          <div class="map-card-tags" aria-hidden="true">
            <span class="map-card-tag">{{ card.name }}</span>
            <span class="map-card-tag is-soft">{{ card.tags[1] }}</span>
          </div>

          <h2 class="map-card-title">{{ card.name }}</h2>

          <span class="map-card-status" :class="card.isUnlocked ? 'is-open' : 'is-locked'">
            {{ card.isUnlocked ? 'Unlocked' : 'Locked' }}
          </span>

          <p class="map-card-unlock-rule">
            {{
              card.isUnlocked
                ? 'Rp 5-30 Balance'
                : `Unlock: Rp150+ balance • 1 Free Refferal`
            }}
          </p>

          <div class="map-card-info">
            <p><strong>Country:</strong> {{ card.country }}</p>
            <p><strong>Habitat:</strong> {{ card.habitat }}</p>
            <p><strong>Info:</strong> {{ card.info }}</p>
          </div>

          <div class="map-card-meta">
            <div class="map-card-food">
              <span>Food Count</span>
              <strong>{{ card.foodCount }}</strong>
            </div>
            <p class="map-card-timer" :class="{ 'is-active': remainingSeconds(card) > 0 }">
              {{ timerText(card) }}
            </p>
          </div>

          <button
            type="button"
            class="map-card-button"
            :class="!card.isUnlocked ? 'is-locked' : isCatchReady(card) ? 'is-open' : 'is-waiting'"
            :disabled="!isCatchReady(card)"
            @click="openModal(card.key)"
          >
            <span>{{ buttonLabel(card) }}</span>
          </button>
        </div>
      </article>
    </section>

    <CatchFishModal v-model="isModalOpen" :fish="selectedFish" @caught="onCaught" />
  </AppShell>
</template> -->

<style>
.map-page-body {
  min-height: 100vh;
}

.map-main {
  align-items: stretch;
}

.map-grid {
  width: 100%;
  max-width: 430px;
  display: grid;
  gap: 12px;
}

.map-card {
  border-radius: 24px;
  overflow: hidden;
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid rgba(20, 108, 130, 0.12);
  box-shadow: 0 16px 28px rgba(12, 86, 104, 0.12);
}

.map-card-image {
  display: block;
  width: 100%;
  aspect-ratio: 16 / 10;
  object-fit: cover;
  object-position: center;
}

.map-card-body {
  padding: 14px 14px 16px;
  display: grid;
  gap: 10px;
}

.map-card-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.map-card-tag {
  display: inline-flex;
  align-items: center;
  min-height: 22px;
  padding: 0 9px;
  border-radius: 999px;
  background: linear-gradient(180deg, #edf9ff 0%, #e3f4fb 100%);
  border: 1px solid rgba(18, 125, 152, 0.12);
  color: #1d5668;
  font-size: 0.62rem;
  font-weight: 700;
}

.map-card-tag.is-soft {
  background: linear-gradient(180deg, #eefbf4 0%, #e4f8ea 100%);
  color: #2d7752;
}

.map-card-title {
  margin: 0;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.28rem;
  line-height: 1.05;
  color: #123045;
}

.map-card-status {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: fit-content;
  min-height: 24px;
  padding: 0 10px;
  border-radius: 999px;
  font-size: 0.66rem;
  font-weight: 700;
}

.map-card-status.is-open {
  background: rgba(18, 190, 146, 0.12);
  color: #187458;
}

.map-card-status.is-locked {
  background: rgba(129, 151, 160, 0.15);
  color: #566d76;
}

.map-card-unlock-rule {
  margin: -2px 0 0;
  color: #2b5a66;
  font-size: 0.72rem;
  line-height: 1.35;
  font-weight: 600;
}

.map-card-info {
  display: grid;
  gap: 8px;
}

.map-card-info p {
  margin: 0;
  color: #31525d;
  font-size: 0.84rem;
  line-height: 1.5;
}

.map-card-info strong {
  color: #11394b;
}

.map-card-button {
  width: 100%;
  height: 46px;
  border: 0;
  border-radius: 14px;
  background: linear-gradient(90deg, #10c39a 0%, #1aa9b4 100%);
  color: #ffffff;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.92rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 12px 22px rgba(15, 177, 146, 0.22);
}

.map-card-meta {
  display: grid;
  gap: 8px;
}

.map-card-food {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  min-height: 42px;
  padding: 0 12px;
  border-radius: 14px;
  background: linear-gradient(180deg, rgba(238, 249, 255, 0.98) 0%, rgba(227, 245, 251, 0.95) 100%);
  border: 1px solid rgba(17, 130, 160, 0.12);
  color: #255262;
  font-size: 0.78rem;
  font-weight: 700;
}

.map-card-food strong {
  color: #123045;
  font-size: 1rem;
}

.map-card-timer {
  margin: 0;
  color: #57717b;
  font-size: 0.75rem;
  line-height: 1.4;
}

.map-card-timer.is-active {
  color: #b85d1d;
  font-weight: 700;
}

.map-card-button.is-locked {
  background: linear-gradient(90deg, #9fb4bc 0%, #8fa5ae 100%);
  box-shadow: none;
  cursor: not-allowed;
}

.map-card-button.is-waiting {
  background: linear-gradient(90deg, #f3aa44 0%, #ea7f42 100%);
  box-shadow: 0 12px 22px rgba(234, 127, 66, 0.2);
  cursor: not-allowed;
}

.map-card-button:disabled {
  opacity: 1;
}
</style>
