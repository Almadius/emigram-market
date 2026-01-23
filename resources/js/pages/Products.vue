<template>
  <Layout>
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-6">Каталог товаров</h1>
      
      <div v-if="loading" class="text-center py-12">
        <p class="text-gray-500">Загрузка...</p>
      </div>

      <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ error }}
      </div>

      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <ProductCard
          v-for="product in products"
          :key="product.product.id"
          :product="product"
          @add-to-cart="handleAddToCart"
        />
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../components/Layout.vue';
import ProductCard from '../components/ProductCard.vue';
import api from '../composables/useApi';
import { useCartStore } from '../stores/cart';
import toast from '../utils/toast';

const products = ref([]);
const loading = ref(false);
const error = ref(null);
const cartStore = useCartStore();

async function fetchProducts() {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get('/api/v1/products');
    products.value = response.data.data || [];
  } catch (err) {
    error.value = 'Не удалось загрузить товары';
    console.error('Failed to fetch products:', err);
  } finally {
    loading.value = false;
  }
}

async function handleAddToCart(productId) {
  try {
    await cartStore.addItem(productId, 1);
    toast.success('Товар добавлен в корзину!');
  } catch (err) {
    const errorMessage = err.response?.data?.message || 'Не удалось добавить товар в корзину';
    toast.error(errorMessage);
    console.error('Failed to add to cart:', err);
  }
}

onMounted(() => {
  fetchProducts();
});
</script>




