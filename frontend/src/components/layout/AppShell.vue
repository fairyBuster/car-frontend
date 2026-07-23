<script setup>
import { computed, onBeforeUnmount, watchEffect } from 'vue'
import BottomNav from './BottomNav.vue'
import DashboardHeader from './DashboardHeader.vue'
import MarketNotice from '../shared/MarketNotice.vue'

const emit = defineEmits(['action'])

const props = defineProps({
  bodyClass: {
    type: String,
    default: '',
  },
  mainClass: {
    type: String,
    default: '',
  },
  headerActions: {
    type: Array,
    default: () => [],
  },
  noticeItems: {
    type: Array,
    default: () => [],
  },
  footerItems: {
    type: Array,
    default: () => [],
  },
  activeFooterKey: {
    type: String,
    default: '',
  },
  footerClass: {
    type: String,
    default: '',
  },
  footerMeta: {
    type: Object,
    default: null,
  },
})

const shellMainClass = computed(() => ['dashboard-main', props.mainClass].filter(Boolean).join(' '))

watchEffect(() => {
  document.body.className = ['dashboard-body', props.bodyClass].filter(Boolean).join(' ')
})

onBeforeUnmount(() => {
  document.body.className = ''
})
</script>

<template>
  <DashboardHeader :actions="headerActions" @action="emit('action', $event)" />
  <MarketNotice :items="noticeItems" />
  <main :class="shellMainClass">
    <slot />
  </main>
  <BottomNav
    v-if="footerItems.length"
    :items="footerItems"
    :active-key="activeFooterKey"
    :footer-class="footerClass"
    :meta="footerMeta"
  />
</template>

<style>
.dashboard-body {
  display: block;
  padding: 0;
  background: linear-gradient(180deg, #eaf9ff 0%, #dcf4e6 100%);
}

.dashboard-main {
  padding: 11px 14px 86px;
  display: grid;
  gap: 11px;
  justify-items: center;
}
</style>
