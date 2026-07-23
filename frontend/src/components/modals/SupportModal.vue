<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { getSupportLink } from '../../services/api'
import {
  cleanSupportLinkUrl,
  formatSupportPlatformLabel,
  normalizeSupportLink,
  SUPPORT_LINK_ID,
} from '../../utils/supportLink'

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:modelValue'])
const isLoading = ref(false)
const supportLink = ref(null)

const supportTitle = computed(() => supportLink.value?.title || 'Support')
const supportPlatform = computed(() =>
  formatSupportPlatformLabel(supportLink.value?.platform || supportLink.value?.title),
)
const supportText = computed(() => {
  if (supportLink.value?.description) {
    return supportLink.value.description
  }

  if (isLoading.value) {
    return 'Memuat link support...'
  }

  return `Anda bisa menghubungi tim support melalui ${supportPlatform.value}.`
})
const supportActionLabel = computed(() => `Go to ${supportPlatform.value}`)
const resolvedHelpUrl = computed(() => cleanSupportLinkUrl(supportLink.value?.url) || '#')

async function loadSupportLink() {
  isLoading.value = true

  try {
    const { ok, data } = await getSupportLink(SUPPORT_LINK_ID)

    if (!ok) {
      supportLink.value = null
      return
    }

    supportLink.value = normalizeSupportLink(data)
  } catch {
    supportLink.value = null
  } finally {
    isLoading.value = false
  }
}

function closeModal() {
  emit('update:modelValue', false)
}

watch(
  () => props.modelValue,
  (value) => {
    document.body.classList.toggle('is-modal-open', value)

    if (value) {
      loadSupportLink()
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
  <div class="help-modal" :hidden="!modelValue">
    <div class="help-modal-backdrop" @click="closeModal"></div>
    <div class="help-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="helpModalTitle">
      <button type="button" class="help-modal-close" aria-label="Close" @click="closeModal">×</button>

      <div class="help-modal-content">
        <span class="help-modal-pill">{{ supportPlatform }}</span>
        <h2 id="helpModalTitle" class="help-modal-title">{{ supportTitle }}</h2>
        <p class="help-modal-text">{{ supportText }}</p>
        <a :href="resolvedHelpUrl" target="_blank" rel="noopener noreferrer" class="help-modal-action">
          {{ supportActionLabel }}
        </a>
      </div>
    </div>
  </div>
</template>

<style>
.help-modal[hidden] {
  display: none;
}

.help-modal {
  position: fixed;
  inset: 0;
  z-index: 1300;
  display: grid;
  place-items: center;
  padding: 20px;
}

.help-modal-backdrop {
  position: absolute;
  inset: 0;
  background: rgba(6, 32, 44, 0.56);
  backdrop-filter: blur(8px);
}

.help-modal-dialog {
  position: relative;
  width: min(100%, 336px);
  border-radius: 24px;
  background:
    radial-gradient(circle at top right, rgba(128, 233, 213, 0.2), transparent 36%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(241, 250, 252, 0.98) 100%);
  border: 1px solid rgba(18, 112, 134, 0.12);
  box-shadow: 0 28px 54px rgba(8, 54, 69, 0.24);
  overflow: hidden;
}

.help-modal-close {
  position: absolute;
  top: 14px;
  right: 14px;
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 50%;
  background: rgba(239, 248, 251, 0.95);
  color: #31505d;
  font-size: 1.45rem;
  line-height: 1;
  cursor: pointer;
}

.help-modal-content {
  display: grid;
  gap: 14px;
  padding: 26px 18px 20px;
  text-align: center;
}

.help-modal-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  justify-self: center;
  min-height: 28px;
  padding: 0 14px;
  border-radius: 999px;
  background: linear-gradient(180deg, #e9f9fb 0%, #def4f8 100%);
  color: #176980;
  font-size: 0.74rem;
  font-weight: 800;
}

.help-modal-title {
  margin: 0;
  color: #123045;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.42rem;
  line-height: 1.1;
}

.help-modal-text {
  margin: 0;
  color: #57727d;
  font-size: 0.86rem;
  line-height: 1.5;
}

.help-modal-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 48px;
  padding: 0 18px;
  border-radius: 16px;
  background: linear-gradient(90deg, #10c39a 0%, #1aa9b4 100%);
  color: #ffffff;
  text-decoration: none;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 0.92rem;
  font-weight: 800;
  box-shadow: 0 14px 24px rgba(15, 177, 146, 0.2);
}
</style>
