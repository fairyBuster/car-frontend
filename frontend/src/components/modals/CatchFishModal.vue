<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  fish: {
    type: Object,
    default: null,
  },
})

const emit = defineEmits(['update:modelValue', 'caught'])

const router = useRouter()
const stage = ref('intro')
const isCatching = ref(false)
const introVideo = '/map/ilkvideo.mp4'
const catchVideo = '/map/baliktutmavideosu.mp4'
const videoSrc = ref(introVideo)

const canCatch = computed(() => {
  if (!props.fish?.isUnlocked) {
    return false
  }

  if (Number(props.fish?.foodCount ?? 0) <= 0) {
    return false
  }

  if (!props.fish?.nextAvailableAt) {
    return true
  }

  return new Date(props.fish.nextAvailableAt).getTime() <= Date.now()
})

const introMessage = computed(() => {
  if (!props.fish?.isUnlocked) {
    return 'This fish is not unlocked yet.'
  }

  if (Number(props.fish?.foodCount ?? 0) <= 0) {
    return 'You have no fish food left. You need to earn or buy more fish food.'
  }

  if (!canCatch.value) {
    return 'You need to wait before fishing again.'
  }

  return 'Start the fishing attempt when you are ready.'
})

function closeModal() {
  emit('update:modelValue', false)
}

function resetState() {
  stage.value = 'intro'
  isCatching.value = false
  videoSrc.value = introVideo
}

function startCatch() {
  if (!canCatch.value || !props.fish) {
    return
  }

  isCatching.value = true
  videoSrc.value = catchVideo

  window.setTimeout(() => {
    emit('caught', props.fish.key)
    stage.value = 'result'
    isCatching.value = false
  }, 2400)
}

function goToAquarium() {
  closeModal()
  router.push({ name: 'aquarium' })
}

watch(
  () => props.modelValue,
  (value) => {
    document.body.classList.toggle('is-modal-open', value)
    if (value) {
      resetState()
    }
  },
  { immediate: true },
)

function onKeydown(event) {
  if (event.key === 'Escape' && props.modelValue) {
    closeModal()
  }
}

window.addEventListener('keydown', onKeydown)

onBeforeUnmount(() => {
  document.body.classList.remove('is-modal-open')
  window.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div class="map-modal" :hidden="!modelValue">
    <div class="map-modal-backdrop" @click="closeModal"></div>
    <div class="map-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="catchModalTitle">
      <button type="button" class="map-modal-close" aria-label="Close" @click="closeModal">×</button>

      <div class="map-modal-video-shell">
        <video class="map-modal-video" autoplay muted playsinline preload="auto" :src="videoSrc"></video>
      </div>

      <div class="map-modal-content">
        <section v-if="stage === 'intro'" class="map-modal-stage is-active">
          <div class="map-modal-tags">
            <span v-for="tag in fish?.tags || []" :key="tag" class="map-modal-tag">{{ tag }}</span>
          </div>
          <h2 id="catchModalTitle" class="map-modal-title">{{ fish?.name || 'Fishing' }}</h2>

          <div class="map-modal-food-panel">
            <span>Remaining Food</span>
            <strong>{{ fish?.foodCount ?? 0 }}</strong>
          </div>

          <div class="map-modal-info">
            <p><strong>Country:</strong> <span>{{ fish?.country || '' }}</span></p>
            <p><strong>Habitat:</strong> <span>{{ fish?.habitat || '' }}</span></p>
            <p><strong>Info:</strong> <span>{{ fish?.info || '' }}</span></p>
          </div>

          <p class="map-modal-message">{{ isCatching ? 'Searching for the fish. The result will appear when the video ends.' : introMessage }}</p>

          <button type="button" class="map-modal-action" :disabled="!canCatch || isCatching" @click="startCatch">
            {{ isCatching ? 'Fishing in Progress' : canCatch ? 'Catch Fish' : 'Locked' }}
          </button>
        </section>

        <section v-else class="map-modal-stage is-active">
          <img :src="fish?.catchImage" :alt="fish?.catchImageAlt" class="map-modal-result-image" />
          <h3 class="map-modal-result-title">Success, you caught {{ fish?.name }}</h3>
          <p class="map-modal-result-text">1 food was used. This card will be active again in 24 hours.</p>
          <button type="button" class="map-modal-action is-secondary" @click="goToAquarium">Add to Aquarium</button>
        </section>
      </div>
    </div>
  </div>
</template>

<style>
.map-modal[hidden] {
  display: none;
}

.map-modal {
  position: fixed;
  inset: 0;
  z-index: 60;
  display: grid;
  place-items: center;
  padding: 18px;
}

.map-modal-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(7, 35, 47, 0.68);
  backdrop-filter: blur(8px);
}

.map-modal-dialog {
  position: relative;
  z-index: 1;
  width: min(100%, 430px);
  overflow: hidden;
  border-radius: 26px;
  background: linear-gradient(180deg, rgba(247, 253, 255, 0.98) 0%, rgba(236, 249, 252, 0.96) 100%);
  border: 1px solid rgba(17, 115, 142, 0.14);
  box-shadow: 0 24px 44px rgba(5, 40, 54, 0.28);
}

.map-modal-close {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 2;
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 50%;
  background: rgba(7, 46, 58, 0.6);
  color: #ffffff;
  font-size: 1.35rem;
  line-height: 1;
  cursor: pointer;
}

.map-modal-video-shell {
  background: #061f28;
}

.map-modal-video {
  display: block;
  width: 100%;
  aspect-ratio: 16 / 10;
  object-fit: cover;
}

.map-modal-content {
  padding: 16px 16px 18px;
}

.map-modal-stage {
  display: none;
}

.map-modal-stage.is-active {
  display: grid;
  gap: 12px;
}

.map-modal-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.map-modal-tag {
  display: inline-flex;
  align-items: center;
  min-height: 24px;
  padding: 0 10px;
  border-radius: 999px;
  background: linear-gradient(180deg, #edf9ff 0%, #e3f4fb 100%);
  border: 1px solid rgba(18, 125, 152, 0.12);
  color: #1d5668;
  font-size: 0.66rem;
  font-weight: 700;
}

.map-modal-title,
.map-modal-result-title {
  margin: 0;
  color: #123045;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
}

.map-modal-food-panel {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  min-height: 48px;
  padding: 0 14px;
  border-radius: 16px;
  background: linear-gradient(135deg, rgba(12, 157, 177, 0.12) 0%, rgba(16, 195, 154, 0.15) 100%);
  color: #21505f;
  font-size: 0.82rem;
  font-weight: 700;
}

.map-modal-food-panel strong {
  color: #0f3142;
  font-size: 1.12rem;
}

.map-modal-info {
  display: grid;
  gap: 7px;
}

.map-modal-info p,
.map-modal-message,
.map-modal-result-text {
  margin: 0;
  color: #305260;
  font-size: 0.86rem;
  line-height: 1.5;
}

.map-modal-info strong {
  color: #123045;
}

.map-modal-action {
  width: 100%;
  height: 48px;
  border: 0;
  border-radius: 16px;
  background: linear-gradient(90deg, #10c39a 0%, #1aa9b4 100%);
  color: #ffffff;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.94rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 12px 22px rgba(15, 177, 146, 0.22);
}

.map-modal-action:disabled {
  cursor: not-allowed;
  opacity: 1;
  background: linear-gradient(90deg, #9fb4bc 0%, #8fa5ae 100%);
  box-shadow: none;
}

.map-modal-action.is-secondary {
  background: linear-gradient(90deg, #147dc3 0%, #14a6c2 100%);
  box-shadow: 0 12px 22px rgba(20, 125, 195, 0.22);
}

.map-modal-result-image {
  width: 100%;
  aspect-ratio: 3 / 2;
  object-fit: cover;
  border-radius: 18px;
  border: 1px solid rgba(17, 115, 142, 0.14);
}

@media (max-width: 380px) {
  .map-modal-info p,
  .map-modal-message,
  .map-modal-result-text {
    font-size: 0.78rem;
  }

  .map-modal-action {
    height: 44px;
    font-size: 0.84rem;
  }
}
</style>
