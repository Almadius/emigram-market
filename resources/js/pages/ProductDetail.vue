<template>
  <Layout>
    <div class="container mx-auto px-4 py-8">
      <div v-if="loading" class="text-center py-12">
        <p class="text-gray-500">Загрузка...</p>
      </div>

      <div v-else-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
        {{ error }}
      </div>

      <div v-else-if="product" class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
          <div class="md:flex">
            <!-- Изображение товара -->
            <div class="md:w-1/2">
              <div v-if="product.product.image_url" class="h-96 bg-gray-200">
                <img
                  :src="product.product.image_url"
                  :alt="product.product.name"
                  class="w-full h-full object-cover"
                />
              </div>
              <div v-else class="h-96 bg-gray-200 flex items-center justify-center">
                <span class="text-gray-400">Нет изображения</span>
              </div>
            </div>

            <!-- Информация о товаре -->
            <div class="md:w-1/2 p-8">
              <h1 class="text-3xl font-bold mb-4">{{ product.product.name }}</h1>
              
              <p v-if="product.product.description" class="text-gray-600 mb-6 text-lg">
                {{ product.product.description }}
              </p>

              <!-- Цены -->
              <div class="mb-6 border-b pb-6">
                <div v-if="product.emigram_price" class="mb-4">
                  <div class="flex items-center gap-3 mb-2">
                    <div class="text-4xl font-bold text-blue-600">
                      {{ formatPrice(product.emigram_price.price) }} {{ product.emigram_price.currency }}
                    </div>
                    <div
                      v-if="product.discount_breakdown"
                      class="relative group"
                    >
                      <button
                        class="text-blue-500 hover:text-blue-700 text-xl cursor-help"
                        title="Детали скидки"
                      >
                        ℹ️
                      </button>
                      <!-- Tooltip с breakdown -->
                      <div
                        class="absolute left-0 bottom-full mb-2 w-72 bg-gray-800 text-white text-sm rounded-lg shadow-xl p-4 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10"
                      >
                        <div class="font-semibold mb-3 text-base">Состав скидки:</div>
                        <div class="space-y-2">
                          <div v-if="product.discount_breakdown.base_discount > 0" class="flex justify-between">
                            <span>Базовая скидка:</span>
                            <span class="font-semibold">{{ product.discount_breakdown.base_discount.toFixed(1) }}%</span>
                          </div>
                          <div v-if="product.discount_breakdown.personal_discount > 0" class="flex justify-between">
                            <span>Персональная скидка:</span>
                            <span class="font-semibold">{{ product.discount_breakdown.personal_discount.toFixed(1) }}%</span>
                          </div>
                          <div class="border-t border-gray-600 pt-2 mt-2 flex justify-between font-bold text-base">
                            <span>Итого скидка:</span>
                            <span>{{ product.discount_breakdown.total_discount_percent.toFixed(1) }}%</span>
                          </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-600 text-xs text-gray-300">
                          Уровень: {{ getLevelName(product.discount_breakdown.user_level) }}
                        </div>
                      </div>
                    </div>
                  </div>
                  <div v-if="product.store_price" class="text-xl text-gray-500 line-through mb-2">
                    {{ formatPrice(product.store_price.price) }} {{ product.store_price.currency }}
                  </div>
                  <div v-if="product.emigram_price.savings_percent > 0" class="text-green-600 text-lg font-semibold">
                    Экономия: {{ product.emigram_price.savings_absolute.toFixed(2) }} {{ product.emigram_price.currency }}
                    ({{ product.emigram_price.savings_percent.toFixed(0) }}%)
                  </div>
                </div>
                <div v-else-if="product.store_price" class="text-4xl font-bold">
                  {{ formatPrice(product.store_price.price) }} {{ product.store_price.currency }}
                </div>
              </div>

              <!-- Кнопка добавления в корзину -->
              <div class="flex gap-4">
                <div class="flex items-center gap-2">
                  <label class="text-sm font-medium">Количество:</label>
                  <input
                    v-model.number="quantity"
                    type="number"
                    min="1"
                    class="w-20 px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                  />
                </div>
                <button
                  @click="handleAddToCart"
                  :disabled="addingToCart"
                  class="flex-1 bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 transition disabled:bg-gray-400 disabled:cursor-not-allowed font-semibold"
                >
                  <span v-if="addingToCart">Добавление...</span>
                  <span v-else>Добавить в корзину</span>
                </button>
              </div>

              <!-- Информация о магазине -->
              <div v-if="product.product.shop_domain" class="mt-6 pt-6 border-t text-sm text-gray-500">
                Магазин: <span class="font-medium">{{ product.product.shop_domain }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import Layout from '../components/Layout.vue';
import api from '../composables/useApi';
import { useCartStore } from '../stores/cart';
import toast from '../utils/toast';

const route = useRoute();
const product = ref(null);
const loading = ref(false);
const error = ref(null);
const cartStore = useCartStore();
const quantity = ref(1);
const addingToCart = ref(false);

async function fetchProduct() {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.get(`/api/v1/products/${route.params.id}`);
    product.value = response.data;
  } catch (err) {
    error.value = 'Товар не найден';
    console.error('Failed to fetch product:', err);
  } finally {
    loading.value = false;
  }
}

async function handleAddToCart() {
  if (quantity.value < 1) {
    toast.warning('Количество должно быть больше 0');
    return;
  }
  
  addingToCart.value = true;
  try {
    await cartStore.addItem(product.value.product.id, quantity.value);
    toast.success(`Товар добавлен в корзину! (${quantity.value} шт.)`);
    quantity.value = 1; // Сбрасываем количество
  } catch (err) {
    const errorMessage = err.response?.data?.message || 'Не удалось добавить товар в корзину';
    toast.error(errorMessage);
    console.error('Failed to add to cart:', err);
  } finally {
    addingToCart.value = false;
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('ru-RU', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(price);
}

function getLevelName(level) {
  const levels = {
    1: 'Bronze',
    2: 'Silver',
    3: 'Gold',
    4: 'Platinum',
    5: 'Diamond',
  };
  return levels[level] || 'Unknown';
}

onMounted(() => {
  fetchProduct();
});
</script>




