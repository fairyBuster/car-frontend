<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

const props = defineProps({
  items: {
    type: Array,
    default: () => [],
  },
  activeKey: {
    type: String,
    default: '',
  },
  footerClass: {
    type: String,
    default: '',
  },
  meta: {
    type: Object,
    default: null,
  },
})

const icons = {
  home: '<svg viewBox="0 0 24 24"><path d="M4 10.5L12 4l8 6.5"></path><path d="M6.5 9.5v9h11v-9"></path><path d="M10 18.5v-4h4v4"></path></svg>',
  aquarium: '<svg viewBox="0 0 24 24"><path d="M4.5 8.5h15l-1.5 10h-12z"></path><path d="M9 8.5a3 3 0 0 1 6 0"></path><path d="M9.5 12.5h5"></path></svg>',
  aquariumActive: '<svg viewBox="0 0 24 24"><path d="M4 15.5c0-3.9 3.6-7 8-7s8 3.1 8 7"></path><path d="M4 15.5v1.5A2 2 0 0 0 6 19h12a2 2 0 0 0 2-2v-1.5"></path><path d="M8 12.5h8"></path><circle cx="9" cy="10" r="1"></circle></svg>',
  invite: '<svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="2.5"></circle><path d="M4.5 18.5c0-2.5 2-4.5 4.5-4.5"></path><path d="M14 10.5h5"></path><path d="M16.5 8v5"></path><path d="M12 18.5c1.3-1.8 3.2-2.8 5.5-2.8"></path></svg>',
  profile: '<svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"></circle><path d="M6.5 18.5c0-3 2.4-5 5.5-5s5.5 2 5.5 5"></path></svg>',
  market: '<svg viewBox="0 0 24 24"><path d="M4 8h16l-1.5 3H5.5z"></path><path d="M6 11v6h12v-6"></path><path d="M9 14c1.1-1 2.1-1 3.2 0 1.1 1 2.1 1 3.1 0"></path></svg>',
  shop: '<svg viewBox="0 0 24 24"><path d="M4.5 8.5h15l-1.5 10h-12z"></path><path d="M9 8.5a3 3 0 0 1 6 0"></path><path d="M9.5 12.5h5"></path></svg>',
  my: '<svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"></circle><path d="M6.5 18.5c0-3 2.4-5 5.5-5s5.5 2 5.5 5"></path></svg>',
}

const baseFooterItems = [
  { key: 'home', label: 'Rumah', to: '/m/pages/dashboard', iconName: 'home' },
  { key: 'aquarium', label: 'Kargo', to: '/m/pages/cargo', iconName: 'aquarium' },
  { key: 'invite', label: 'Undang teman', to: '/m/pages/invite', iconName: 'invite' },
  { key: 'profile', label: 'Profil', to: '/m/pages/profile', iconName: 'profile' },
]

const navItems = computed(() => {
  const overrides = new Map((props.items || []).map((item) => [item.key, item]))

  return baseFooterItems.map((item) => ({
    ...overrides.get(item.key),
    ...item,
  }))
})

function componentFor(item) {
  if (item.to) {
    return RouterLink
  }

  return 'a'
}

function iconFor(item, isActive) {
  if (item.iconName === 'aquarium' && isActive) {
    return icons.aquariumActive
  }

  return icons[item.iconName]
}
</script>

<template>
  <footer class="dashboard-footer" :class="footerClass" aria-label="Primary footer navigation">
    <div v-if="meta" class="profile-footer-meta">
      <span>{{ meta.label }}</span>
      <strong>{{ meta.value }}</strong>
    </div>

    <nav class="dashboard-footer-bar" aria-label="Primary">
      <component
        :is="componentFor(item)"
        v-for="item in navItems"
        :key="item.key"
        class="dashboard-footer-item"
        :class="{ 'is-active': item.key === activeKey }"
        :to="item.to"
        :href="item.href"
        :aria-current="item.key === activeKey ? 'page' : undefined"
      >
        <span class="dashboard-footer-icon" aria-hidden="true" v-html="iconFor(item, item.key === activeKey)"></span>
        <span class="dashboard-footer-label">{{ item.label }}</span>
      </component>
    </nav>
  </footer>
</template>

<style>
.dashboard-footer {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 4px 12px calc(4px + env(safe-area-inset-bottom));
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0) 0%,
    rgba(255, 255, 255, 0.92) 48%,
    rgba(255, 255, 255, 0.98) 100%
  );
}

.dashboard-footer-bar {
  width: 100%;
  max-width: 430px;
  margin: 0 auto;
  padding: 5px 8px;
  border-radius: 18px;
  border: 1px solid rgba(12, 72, 91, 0.12);
  background: #ffffff;
  box-shadow: 0 12px 24px rgba(11, 76, 95, 0.12);
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 4px;
}

.dashboard-footer-item {
  text-decoration: none;
  color: #5a7680;
  display: grid;
  justify-items: center;
  gap: 2px;
  padding: 4px 4px 3px;
  border-radius: 13px;
  transition: transform 0.16s ease, background-color 0.16s ease, color 0.16s ease;
}

.dashboard-footer-item:hover {
  transform: translateY(-1px);
  color: #123045;
}

.dashboard-footer-item.is-active {
  background: rgba(18, 126, 153, 0.08);
  color: #123045;
}

.dashboard-footer-icon {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  color: #1182a3;
  background: linear-gradient(180deg, #f5fbfd 0%, #e8f6fb 100%);
  border: 1px solid rgba(17, 130, 163, 0.18);
  box-shadow: 0 6px 14px rgba(17, 130, 163, 0.16);
}

.dashboard-footer-item.is-active .dashboard-footer-icon {
  color: #0d6f8c;
  background: linear-gradient(180deg, #dff5fb 0%, #d9f3e7 100%);
  border-color: rgba(17, 130, 163, 0.28);
  box-shadow: 0 8px 18px rgba(17, 130, 163, 0.22);
}

.dashboard-footer-icon svg {
  width: 20px;
  height: 20px;
  fill: none;
  stroke: currentColor;
  stroke-width: 2.1;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.dashboard-footer-label {
  font-size: 0.63rem;
  font-weight: 700;
  line-height: 1.05;
  text-align: center;
}

.profile-footer-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  width: calc(100% - 12px);
  margin: 0 auto;
  padding: 10px 14px;
  border-radius: 18px;
  background: linear-gradient(180deg, rgba(244, 250, 252, 0.98) 0%, rgba(232, 244, 247, 0.96) 100%);
  border: 1px solid rgba(16, 102, 132, 0.1);
  color: #163245;
  box-shadow: 0 10px 20px rgba(33, 88, 110, 0.08);
}

.profile-footer-meta span {
  font-size: 0.72rem;
  color: #617985;
}

.profile-footer-meta strong {
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  font-size: 1rem;
  color: #0f3042;
}

@media (max-width: 380px) {
  .dashboard-footer {
    padding-left: 8px;
    padding-right: 8px;
  }

  .dashboard-footer-bar {
    padding: 4px 6px;
    border-radius: 16px;
    gap: 3px;
  }

  .dashboard-footer-icon {
    width: 34px;
    height: 34px;
    border-radius: 11px;
  }

  .dashboard-footer-label {
    font-size: 0.58rem;
  }
}
</style>
