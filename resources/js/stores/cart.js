import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '../composables/useApi';

export const useCartStore = defineStore('cart', () => {
  const items = ref([]);
  const total = ref(0);
  const loading = ref(false);

  const itemsCount = computed(() => items.value.length);

  async function fetchCart() {
    loading.value = true;
    try {
      const response = await api.get('/api/v1/cart');
      
      // CartResource использует ->response(), который оборачивает в { data: { items: [], total: 0 } }
      // Поэтому нужно проверить response.data.data сначала
      if (response.data?.data) {
        // Ответ обернут в data
        items.value = response.data.data.items || [];
        total.value = response.data.data.total || 0;
      } else {
        // Ответ не обернут
        items.value = response.data.items || [];
        total.value = response.data.total || 0;
      }
    } catch (error) {
      console.error('Failed to fetch cart:', error);
      // Если ошибка 401, возможно токен недействителен
      if (error.response?.status === 401) {
        console.warn('Unauthorized - please login again');
      }
    } finally {
      loading.value = false;
    }
  }

  async function addItem(productId, quantity = 1) {
    loading.value = true;
    try {
      await api.post('/api/v1/cart/items', {
        product_id: productId,
        quantity,
      });
      await fetchCart();
    } catch (error) {
      console.error('Failed to add item:', error);
      throw error;
    } finally {
      loading.value = false;
    }
  }

  async function removeItem(productId) {
    loading.value = true;
    try {
      await api.delete(`/api/v1/cart/items/${productId}`);
      await fetchCart();
    } catch (error) {
      console.error('Failed to remove item:', error);
      throw error;
    } finally {
      loading.value = false;
    }
  }

  async function updateItem(productId, quantity) {
    loading.value = true;
    try {
      await api.put(`/api/v1/cart/items/${productId}`, {
        quantity,
      });
      await fetchCart();
    } catch (error) {
      console.error('Failed to update item:', error);
      throw error;
    } finally {
      loading.value = false;
    }
  }

  async function clearCart() {
    loading.value = true;
    try {
      await api.delete('/api/v1/cart');
      items.value = [];
      total.value = 0;
    } catch (error) {
      console.error('Failed to clear cart:', error);
      throw error;
    } finally {
      loading.value = false;
    }
  }

  return {
    items,
    total,
    loading,
    itemsCount,
    fetchCart,
    addItem,
    removeItem,
    updateItem,
    clearCart,
  };
});




