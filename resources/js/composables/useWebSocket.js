import { ref, onMounted, onUnmounted } from 'vue';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

let echoInstance = null;

/**
 * Initialize Laravel Echo for WebSocket connections
 */
function initEcho() {
  if (echoInstance) {
    return echoInstance;
  }

  window.Pusher = Pusher;

  echoInstance = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY || 'your-pusher-key',
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER || 'mt1',
    forceTLS: true,
    authEndpoint: `${import.meta.env.VITE_APP_URL || 'http://localhost:8000'}/broadcasting/auth`,
    auth: {
      headers: {
        Authorization: `Bearer ${localStorage.getItem('token')}`,
      },
    },
  });

  return echoInstance;
}

/**
 * Composable for WebSocket subscriptions
 */
export function useWebSocket() {
  const echo = ref(null);
  const isConnected = ref(false);

  onMounted(() => {
    try {
      echo.value = initEcho();
      isConnected.value = true;
    } catch (error) {
      console.error('Failed to initialize WebSocket:', error);
      isConnected.value = false;
    }
  });

  onUnmounted(() => {
    if (echo.value) {
      echo.value.disconnect();
      echo.value = null;
      isConnected.value = false;
    }
  });

  /**
   * Subscribe to user-specific channel
   */
  function subscribeToUserChannel(userId, callback) {
    if (!echo.value) {
      console.warn('Echo not initialized');
      return null;
    }

    const channel = echo.value.private(`user.${userId}`);
    
    channel.listen('.order.status.updated', (data) => {
      callback(data);
    });

    return channel;
  }

  /**
   * Unsubscribe from channel
   */
  function unsubscribe(channel) {
    if (channel && echo.value) {
      echo.value.leave(channel.name);
    }
  }

  return {
    echo,
    isConnected,
    subscribeToUserChannel,
    unsubscribe,
  };
}


