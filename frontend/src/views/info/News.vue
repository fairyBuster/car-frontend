<script setup>
import { computed, onMounted, ref } from 'vue'
import AppShell from '../../components/layout/AppShell.vue'
import {
  backProfileHeaderActions,
  infoFooterItems,
  marketNoticeItems,
} from '../../data/mockData'
import { getNews } from '../../services/api'

const isLoading = ref(false)
const loadError = ref('')
const items = ref([])

function getNewsIdentity(item) {
  return [item?.id, item?.slug, item?.title, item?.published_at, item?.updated_at]
    .filter(Boolean)
    .join('::')
}

function formatDateTime(value) {
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

function extractNewsItems(payload) {
  if (Array.isArray(payload)) {
    return payload
  }

  if (payload && typeof payload === 'object' && Array.isArray(payload.results)) {
    return payload.results
  }

  return []
}

function mapNewsItem(item) {
  return {
    id: item?.id || item?.slug || item?.title,
    tag: 'New update',
    title: item?.title || '-',
    summary: item?.body || '-',
    time: formatDateTime(item?.published_at || item?.updated_at),
  }
}

function dedupeNewsItems(newsItems) {
  const seen = new Set()

  return newsItems.filter((item) => {
    const identity = getNewsIdentity(item)

    if (!identity || seen.has(identity)) {
      return false
    }

    seen.add(identity)
    return true
  })
}

const featured = computed(() => {
  return (
    items.value[0] || {
      tag: 'New update',
      title: 'Belum ada berita',
      summary: 'Berita terbaru akan tampil di sini.',
      time: '-',
    }
  )
})

const listItems = computed(() => {
  if (!items.value.length) {
    return []
  }

  const featuredId = items.value[0]?.id
  return items.value.filter((item) => item.id !== featuredId)
})

async function loadNews() {
  isLoading.value = true
  loadError.value = ''

  try {
    const { ok, data } = await getNews()

    if (!ok) {
      loadError.value = 'Berita gagal dimuat.'
      return
    }

    items.value = dedupeNewsItems(extractNewsItems(data)).map(mapNewsItem)
  } catch (error) {
    loadError.value = error instanceof Error ? error.message : 'Tidak bisa terhubung ke server berita.'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadNews()
})
</script>

<template>
  <AppShell
    body-class="news-page-body"
    main-class="news-main"
    :header-actions="backProfileHeaderActions"
    :notice-items="marketNoticeItems"
    :footer-items="infoFooterItems"
    active-footer-key=""
  >
    <section class="news-hero" aria-label="One cikan haber">
      <span class="news-hero-pill">New update</span>
      <h1>{{ featured.title }}</h1>
      <p>{{ featured.summary }}</p>
      <div class="news-hero-meta">
   
        <span>{{ featured.time }}</span>
      </div>
    </section>

    <section v-if="loadError" class="deposit-alert is-error">
      <p>{{ loadError }}</p>
    </section>

    <section v-else-if="isLoading" class="deposit-history" aria-label="Daftar berita">
      <div class="deposit-history-list">
        <article class="deposit-history-empty">
          <p>Memuat berita...</p>
        </article>
      </div>
    </section>

    <section v-else-if="listItems.length" class="news-stream" aria-label="Deniz haberleri listesi">
      <article v-for="news in listItems" :key="news.id" class="news-card">
        <div class="news-card-head">
          <span class="news-card-pill">New update</span>
          <span class="news-card-time">{{ news.time }}</span>
        </div>
        <h2>{{ news.title }}</h2>
        <p>{{ news.summary }}</p>
      </article>
    </section>
  </AppShell>
</template>

<style>
.news-page-body {
  min-height: 100vh;
}

.news-main {
  align-items: stretch;
  gap: 12px;
}

.news-hero,
.news-card {
  width: 100%;
  box-sizing: border-box;
  border-radius: 24px;
  border: 1px solid rgba(18, 112, 134, 0.12);
  background:
    radial-gradient(circle at top right, rgba(131, 231, 216, 0.16), transparent 36%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(241, 250, 252, 0.98) 100%);
  box-shadow: 0 18px 34px rgba(11, 79, 98, 0.1);
}

.news-hero {
  padding: 18px 16px 16px;
}

.news-hero-pill,
.news-card-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 28px;
  padding: 0 14px;
  border-radius: 999px;
  background: linear-gradient(180deg, #e9f9fb 0%, #def4f8 100%);
  color: #176980;
  font-size: 0.72rem;
  font-weight: 800;
}

.news-hero h1,
.news-card h2 {
  margin: 0;
  color: #123045;
  font-family: 'Outfit', 'Segoe UI', sans-serif;
  line-height: 1.12;
}

.news-hero h1 {
  margin-top: 12px;
  font-size: 1.56rem;
}

.news-hero p,
.news-card p {
  margin: 0;
  color: #58727d;
  line-height: 1.55;
}

.news-hero p {
  margin-top: 12px;
  font-size: 0.9rem;
}

.news-hero-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-top: 14px;
  color: #6e8792;
  font-size: 0.72rem;
  font-weight: 700;
}

.news-stream {
  display: grid;
  gap: 10px;
}

.news-card {
  padding: 14px 14px 13px;
}

.news-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  margin-bottom: 10px;
}

.news-card-time {
  color: #7a929d;
  font-size: 0.68rem;
  font-weight: 700;
  white-space: nowrap;
}

.news-card h2 {
  font-size: 1rem;
}

.news-card p {
  margin-top: 8px;
  font-size: 0.82rem;
}
</style>
