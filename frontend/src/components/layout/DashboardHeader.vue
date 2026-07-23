<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { getSupportLink } from '../../services/api'
import {
  cleanSupportLinkUrl,
  normalizeSupportLink,
  SUPPORT_LINK_ID,
} from '../../utils/supportLink'

const props = defineProps({
  actions: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['action'])

function resolveUserPhone() {
  try {
    const rawUser = localStorage.getItem('auth_user')
    if (rawUser) {
      const parsed = JSON.parse(rawUser)
      if (parsed?.phone) {
        return String(parsed.phone)
      }
    }
  } catch {}

  const rawPhone = localStorage.getItem('auth_phone')
  return rawPhone ? String(rawPhone) : ''
}

const userPhone = ref(resolveUserPhone())
const brandLabel = computed(() => userPhone.value || '-')
const supportHref = ref('')

function syncUserPhone() {
  userPhone.value = resolveUserPhone()
}

async function loadSupportLink() {
  try {
    const { ok, data } = await getSupportLink(SUPPORT_LINK_ID)

    if (!ok) {
      supportHref.value = ''
      return
    }

    supportHref.value = cleanSupportLinkUrl(normalizeSupportLink(data)?.url)
  } catch {
    supportHref.value = ''
  }
}

onMounted(() => {
  window.addEventListener('storage', syncUserPhone)
  loadSupportLink()
})

onBeforeUnmount(() => {
  window.removeEventListener('storage', syncUserPhone)
})

const icons = {
  support:
    '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 13v-2a8 8 0 1 1 16 0v2"></path><rect x="2.5" y="12" width="4" height="7" rx="1.5"></rect><rect x="17.5" y="12" width="4" height="7" rx="1.5"></rect><path d="M18 19a3 3 0 0 1-3 3h-3"></path><circle cx="11.8" cy="22" r="1"></circle></svg>',
  language:
    '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M3 12h18"></path><path d="M12 3a14.5 14.5 0 0 1 0 18"></path><path d="M12 3a14.5 14.5 0 0 0 0 18"></path></svg>',
  back:
    '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.5 5.5L8 12l6.5 6.5"></path><path d="M9 12h10"></path></svg>',
  profile:
    '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3"></circle><path d="M6.5 18.5c0-3 2.4-5 5.5-5s5.5 2 5.5 5"></path></svg>',
  logout:
    '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 7V5.5A1.5 1.5 0 0 0 12.5 4h-5A1.5 1.5 0 0 0 6 5.5v13A1.5 1.5 0 0 0 7.5 20h5A1.5 1.5 0 0 0 14 18.5V17"></path><path d="M10 12h10"></path><path d="M17 8.5l3.5 3.5-3.5 3.5"></path></svg>',
}

function componentFor(action) {
  if (action.type === 'button') {
    return 'button'
  }

  if (action.to) {
    return RouterLink
  }

  return 'a'
}

function resolvedHref(action) {
  if (action.href) {
    return action.href
  }

  if (action.key === 'support' || action.iconName === 'support') {
    return supportHref.value || '#'
  }

  return undefined
}

function resolvedTarget(action) {
  if (action.target) {
    return action.target
  }

  if (action.key === 'support' || action.iconName === 'support') {
    return '_blank'
  }

  return undefined
}

function resolvedRel(action) {
  if (action.rel) {
    return action.rel
  }

  if (action.key === 'support' || action.iconName === 'support') {
    return 'noopener noreferrer'
  }

  return undefined
}

function onActionClick(action) {
  if (action.type === 'button') {
    emit('action', action.key)
  }
}
</script>

<template>
  <header class="dashboard-header">
    <div class="dashboard-brand" aria-label="User">
   
      <span class="dashboard-brand-name">{{ brandLabel }}</span>
    </div>

    <div class="dashboard-actions">
      <component
        :is="componentFor(action)"
        v-for="action in props.actions"
        :key="`${action.iconName}-${action.ariaLabel}`"
        :class="action.className || 'dashboard-icon-btn'"
        :to="action.to"
        :href="resolvedHref(action)"
        :target="resolvedTarget(action)"
        :rel="resolvedRel(action)"
        :type="action.type === 'button' ? 'button' : undefined"
        :aria-label="action.ariaLabel"
        @click="onActionClick(action)"
      >
        <template v-if="action.label">
          <span v-html="icons[action.iconName]"></span>
          <span>{{ action.label }}</span>
        </template>
        <span v-else v-html="icons[action.iconName]"></span>
      </component>
    </div>
  </header>
</template>

<style>
.dashboard-header {
  width: 100%;
  min-height: 43px;
  background: #f2f2f2;
  border-bottom: 1px solid #e2e2e2;
  padding: 6px 12px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dashboard-brand {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.dashboard-brand-logo {
  width: 26px;
  height: 26px;
  flex: 0 0 auto;
}

.dashboard-brand-name {
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1.02rem;
  line-height: 1;
  font-weight: 700;
  color: #152737;
  letter-spacing: 0.2px;
}

.dashboard-actions {
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.dashboard-icon-btn {
  width: 33px;
  height: 33px;
  border: 1px solid #e2e2e2;
  border-radius: 9px;
  background: #ececec;
  display: inline-flex;
  justify-content: center;
  align-items: center;
  padding: 0;
  cursor: pointer;
}

.dashboard-icon-btn svg {
  width: 17px;
  height: 17px;
  fill: none;
  stroke: #12273b;
  stroke-width: 1.8;
  stroke-linecap: round;
  stroke-linejoin: round;
}

@media (max-width: 380px) {
  .dashboard-brand-name {
    font-size: 0.92rem;
  }
}
</style>
