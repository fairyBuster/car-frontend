<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { getSupportLink } from '../../services/api'
import {
  cleanSupportLinkUrl,
  normalizeSupportLink,
  SUPPORT_LINK_ID,
} from '../../utils/supportLink'

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
})

const isVisible = ref(false)
const title = ref('Market is active')
const text = ref('Car-gowise is ready to running right now in the market.')
const queue = ref([])
const supportLinkUrl = ref('')

let activeTimer = null
let hideTimer = null

function clearTimers() {
  if (activeTimer) {
    window.clearTimeout(activeTimer)
  }

  if (hideTimer) {
    window.clearTimeout(hideTimer)
  }
}

function notificationKey(item) {
  return `${item.key}:${item.market_available_at}`
}

const resolvedSupportUrl = computed(() => supportLinkUrl.value || '#')

async function loadSupportLink() {
  try {
    const { ok, data } = await getSupportLink(SUPPORT_LINK_ID)

    if (!ok) {
      supportLinkUrl.value = ''
      return
    }

    const supportLink = normalizeSupportLink(data)
    supportLinkUrl.value = cleanSupportLinkUrl(supportLink?.url)
  } catch {
    supportLinkUrl.value = ''
  }
}

function showNotice(messageTitle, messageText) {
  title.value = messageTitle
  text.value = messageText
  isVisible.value = true

  if (hideTimer) {
    window.clearTimeout(hideTimer)
  }

  hideTimer = window.setTimeout(() => {
    isVisible.value = false
  }, 9000)
}

function scheduleNext() {
  clearTimers()

  const items = queue.value
  if (!items.length) {
    return
  }

  const next = items
    .map((item) => ({
      ...item,
      remaining: Math.max(0, new Date(item.market_available_at).getTime() - Date.now()),
    }))
    .sort((left, right) => left.remaining - right.remaining)[0]

  if (!next) {
    return
  }

  activeTimer = window.setTimeout(() => {
    sessionStorage.setItem(`market_notice:${notificationKey(next)}`, '1')
    showNotice('Market is active', 'Car-gowise is ready to running right now in the market.')
    queue.value = queue.value.filter((item) => notificationKey(item) !== notificationKey(next))
    activeTimer = window.setTimeout(scheduleNext, 9600)
  }, next.remaining)
}

watch(
  () => props.items,
  (items) => {
    queue.value = items.filter(
      (item) => sessionStorage.getItem(`market_notice:${notificationKey(item)}`) !== '1',
    )
    activeTimer = window.setTimeout(scheduleNext, 600)
  },
  { immediate: true, deep: true },
)

onBeforeUnmount(() => {
  clearTimers()
})

onMounted(() => {
  loadSupportLink()
})
</script>

<template>
  <div class="market-notice" :class="{ 'is-visible': isVisible }" :hidden="!isVisible">
    <div class="market-notice-card">
      <div class="market-notice-copy">
        <span class="market-notice-pill">Market Notice</span>
        <strong>{{ title }}</strong>
        <p>{{ text }}</p>
      </div>
      <div class="market-notice-actions">
        <a :href="resolvedSupportUrl" target="_blank" rel="noopener noreferrer" class="market-notice-button">
          Go to telegram
        </a>
        <button type="button" class="market-notice-close" aria-label="Close" @click="isVisible = false">
          ×
        </button>
      </div>
    </div>
  </div>
</template>

<style>
.market-notice[hidden] {
  display: none;
}

.market-notice {
  position: fixed;
  top: 14px;
  left: 50%;
  z-index: 70;
  width: min(calc(100% - 24px), 430px);
  transform: translate(-50%, -120%);
  opacity: 0;
  transition: transform 0.38s ease, opacity 0.38s ease;
  pointer-events: none;
}

.market-notice.is-visible {
  transform: translate(-50%, 0);
  opacity: 1;
  pointer-events: auto;
}

.market-notice-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 14px 14px 14px 16px;
  border-radius: 22px;
  background:
    radial-gradient(circle at top right, rgba(139, 218, 239, 0.3), transparent 42%),
    linear-gradient(140deg, rgba(7, 86, 108, 0.97) 0%, rgba(11, 122, 145, 0.95) 52%, rgba(18, 176, 151, 0.93) 100%);
  box-shadow: 0 18px 36px rgba(7, 54, 67, 0.24);
  border: 1px solid rgba(255, 255, 255, 0.16);
}

.market-notice-copy {
  min-width: 0;
}

.market-notice-pill {
  display: inline-flex;
  align-items: center;
  min-height: 22px;
  padding: 0 10px;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.16);
  color: #effcff;
  font-size: 0.63rem;
  font-weight: 700;
}

.market-notice-copy strong {
  display: block;
  margin-top: 7px;
  color: #ffffff;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.08rem;
  line-height: 1.05;
}

.market-notice-copy p {
  margin: 5px 0 0;
  color: rgba(239, 252, 255, 0.92);
  font-size: 0.79rem;
  line-height: 1.42;
}

.market-notice-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.market-notice-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 116px;
  min-height: 40px;
  padding: 0 14px;
  border-radius: 14px;
  background: rgba(255, 255, 255, 0.16);
  border: 1px solid rgba(255, 255, 255, 0.18);
  color: #ffffff;
  font-size: 0.78rem;
  font-weight: 700;
  text-decoration: none;
}

.market-notice-close {
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
  font-size: 1.18rem;
  line-height: 1;
  cursor: pointer;
}
</style>
