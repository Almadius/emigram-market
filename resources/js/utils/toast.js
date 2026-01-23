/**
 * Простая система toast уведомлений
 */
class ToastManager {
  constructor() {
    this.toasts = [];
    this.container = null;
    this.init();
  }

  init() {
    // Создаем контейнер для toast'ов
    if (typeof document !== 'undefined') {
      this.container = document.createElement('div');
      this.container.id = 'toast-container';
      this.container.className = 'fixed top-4 right-4 z-50 space-y-2';
      document.body.appendChild(this.container);
    }
  }

  show(message, type = 'info', duration = 3000) {
    const toast = document.createElement('div');
    const id = Date.now();
    
    const bgColors = {
      success: 'bg-green-500',
      error: 'bg-red-500',
      warning: 'bg-yellow-500',
      info: 'bg-blue-500',
    };

    toast.className = `${bgColors[type] || bgColors.info} text-white px-6 py-3 rounded-lg shadow-lg flex items-center justify-between min-w-[300px] max-w-md animate-slide-in`;
    toast.innerHTML = `
      <span>${message}</span>
      <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.remove()">×</button>
    `;
    
    toast.dataset.toastId = id;

    if (this.container) {
      this.container.appendChild(toast);

      // Автоматическое удаление
      setTimeout(() => {
        if (toast.parentElement) {
          toast.style.opacity = '0';
          toast.style.transform = 'translateX(100%)';
          toast.style.transition = 'all 0.3s ease';
          setTimeout(() => toast.remove(), 300);
        }
      }, duration);
    }

    return id;
  }

  success(message, duration) {
    return this.show(message, 'success', duration);
  }

  error(message, duration) {
    return this.show(message, 'error', duration || 5000);
  }

  warning(message, duration) {
    return this.show(message, 'warning', duration);
  }

  info(message, duration) {
    return this.show(message, 'info', duration);
  }
}

// Создаем singleton
const toast = new ToastManager();

export default toast;

