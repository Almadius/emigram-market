<template>
  <Layout>
    <div class="container mx-auto px-4 py-8">
      <h1 class="text-3xl font-bold mb-6">Мои заказы</h1>

      <div v-if="loading" class="text-center py-12">
        <p class="text-gray-500">Загрузка заказов...</p>
      </div>

      <div v-else-if="orders.length === 0" class="text-center py-12">
        <p class="text-gray-500 text-lg mb-4">У вас пока нет заказов</p>
        <router-link to="/products" class="text-blue-600 hover:underline">
          Перейти к товарам
        </router-link>
      </div>

      <div v-else class="space-y-6">
        <div
          v-for="order in orders"
          :key="order.id"
          class="bg-white rounded-lg shadow p-6"
        >
          <div class="flex justify-between items-start mb-4">
            <div>
              <h2 class="text-xl font-semibold">Заказ #{{ order.id }}</h2>
              <p class="text-sm text-gray-500 mt-1">
                Магазин: {{ order.shop_domain }}
              </p>
              <p class="text-sm text-gray-500">
                Создан: {{ formatDate(order.created_at) }}
              </p>
            </div>
            <div class="text-right">
              <span
                :class="getStatusClass(order.status)"
                class="px-3 py-1 rounded-full text-sm font-semibold"
              >
                {{ getStatusText(order.status) }}
              </span>
              <p class="text-xl font-bold mt-2">
                {{ formatPrice(order.total) }} {{ order.currency }}
              </p>
            </div>
          </div>

          <div class="border-t pt-4">
            <h3 class="font-semibold mb-2">Товары:</h3>
            <ul class="space-y-2">
              <li
                v-for="item in order.items"
                :key="item.product_id"
                class="flex justify-between text-sm"
              >
                <span>{{ item.product_name }} x{{ item.quantity }}</span>
                <span>{{ formatPrice(item.price * item.quantity) }} {{ order.currency }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- WebSocket статус -->
      <div v-if="wsConnected" class="fixed bottom-4 left-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg flex items-center gap-2">
        <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
        <span class="text-sm">WebSocket подключен</span>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import Layout from '../components/Layout.vue';
import api from '../composables/useApi';
import { useWebSocket } from '../composables/useWebSocket';
import { useAuthStore } from '../stores/auth';
import toast from '../utils/toast';

const authStore = useAuthStore();
const { subscribeToUserChannel, unsubscribe, isConnected: wsConnected } = useWebSocket();

const orders = ref([]);
const loading = ref(true);
let orderChannel = null;

async function fetchOrders() {
  try {
    loading.value = true;
    // Note: This endpoint needs to be created in the backend
    // For now, we'll use a placeholder
    const response = await api.get('/api/v1/orders');
    orders.value = response.data.data || response.data || [];
  } catch (error) {
    console.error('Failed to fetch orders:', error);
    orders.value = [];
  } finally {
    loading.value = false;
  }
}

function formatPrice(price) {
  return new Intl.NumberFormat('ru-RU', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(price);
}

function formatDate(dateString) {
  return new Date(dateString).toLocaleString('ru-RU');
}

function getStatusText(status) {
  const statusMap = {
    pending: 'Ожидает обработки',
    processing: 'В обработке',
    shipped: 'Отправлен',
    delivered: 'Доставлен',
    cancelled: 'Отменен',
  };
  return statusMap[status] || status;
}

function getStatusClass(status) {
  const classMap = {
    pending: 'bg-yellow-100 text-yellow-800',
    processing: 'bg-blue-100 text-blue-800',
    shipped: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
  };
  return classMap[status] || 'bg-gray-100 text-gray-800';
}

function handleOrderStatusUpdate(data) {
  const orderIndex = orders.value.findIndex(o => o.id === data.order_id);
  if (orderIndex !== -1) {
    const oldStatus = orders.value[orderIndex].status;
    orders.value[orderIndex].status = data.status;
    
    // Показываем toast уведомление
    toast.info(
      `Статус заказа #${data.order_id} изменен: ${getStatusText(oldStatus)} → ${getStatusText(data.status)}`
    );
  }
}

onMounted(async () => {
  await fetchOrders();

  // Subscribe to WebSocket updates if user is authenticated
  if (authStore.user?.id) {
    orderChannel = subscribeToUserChannel(authStore.user.id, handleOrderStatusUpdate);
  }
});

onUnmounted(() => {
  if (orderChannel) {
    unsubscribe(orderChannel);
  }
});
</script>


