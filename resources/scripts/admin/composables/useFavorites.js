import { ref, computed } from 'vue'
import { useGlobalStore } from '@/scripts/admin/stores/global'

const STORAGE_KEY = 'sidebar_favorites'
const MAX_FAVORITES = 8

// Shared reactive state (module-level singleton)
const favoriteLinks = ref(loadFavorites())

function loadFavorites() {
  try {
    const stored = localStorage.getItem(STORAGE_KEY)
    return stored ? JSON.parse(stored) : []
  } catch {
    return []
  }
}

function saveFavorites() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(favoriteLinks.value))
}

export function useFavorites() {
  const globalStore = useGlobalStore()

  function isFavorite(link) {
    return favoriteLinks.value.includes(link)
  }

  function addFavorite(link) {
    if (!isFavorite(link) && favoriteLinks.value.length < MAX_FAVORITES) {
      favoriteLinks.value = [...favoriteLinks.value, link]
      saveFavorites()
    }
  }

  function removeFavorite(link) {
    favoriteLinks.value = favoriteLinks.value.filter(l => l !== link)
    saveFavorites()
  }

  function toggleFavorite(link) {
    if (isFavorite(link)) {
      removeFavorite(link)
    } else {
      addFavorite(link)
    }
  }

  // Resolve favorite links to full menu item objects
  function getFavoriteItems() {
    const allItems = [
      ...(globalStore.mainMenu || []),
      ...(globalStore.settingMenu || []),
    ]
    return favoriteLinks.value
      .map(link => allItems.find(item => item.link === link))
      .filter(Boolean)
  }

  return {
    favorites: favoriteLinks,
    isFavorite,
    addFavorite,
    removeFavorite,
    toggleFavorite,
    getFavoriteItems,
  }
}
