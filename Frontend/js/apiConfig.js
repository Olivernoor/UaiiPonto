/**
 * Configuração central da API
 * Defina aqui a URL base do seu backend
 */

const API_CONFIG = {
  // URL base do backend - altere conforme necessário
  BASE_URL: 'http://localhost:8000',
  
  // Endpoints da API
  ENDPOINTS: {
    LOGIN: '/login',
    REGISTER: '/register',
    LOGOUT: '/logout',
    ME: '/me',
    UPDATE_USER: (userId) => `/users/${userId}`,
  },

  // Chaves de armazenamento local
  STORAGE_KEYS: {
    TOKEN: 'token',
    USER: 'user',
  },

  // Timeouts
  TIMEOUT: 5000, // em ms
};

/**
 * Função auxiliar para fazer requisições à API
 * @param {string} endpoint - O endpoint da API (ex: '/login')
 * @param {object} options - Opções da requisição (method, body, etc)
 * @returns {Promise}
 */
async function apiRequest(endpoint, options = {}) {
  const url = `${API_CONFIG.BASE_URL}${endpoint}`;
  const defaultOptions = {
    method: 'GET',
    headers: {
      'Content-Type': 'application/json',
    },
  };

  // Adicionar token de autenticação se existir
  const token = localStorage.getItem(API_CONFIG.STORAGE_KEYS.TOKEN);
  if (token) {
    defaultOptions.headers['Authorization'] = `Bearer ${token}`;
  }

  const finalOptions = { ...defaultOptions, ...options };

  try {
    const response = await fetch(url, finalOptions);
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Erro na requisição');
    }

    return { success: true, data, status: response.status };
  } catch (error) {
    return { success: false, error: error.message, status: null };
  }
}

/**
 * Fazer login
 */
async function login(email, password) {
  return apiRequest(API_CONFIG.ENDPOINTS.LOGIN, {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });
}

/**
 * Fazer logout
 */
async function logout() {
  return apiRequest(API_CONFIG.ENDPOINTS.LOGOUT, {
    method: 'POST',
  });
}

/**
 * Registrar novo usuário
 */
async function register(userData) {
  return apiRequest(API_CONFIG.ENDPOINTS.REGISTER, {
    method: 'POST',
    body: JSON.stringify(userData),
  });
}

/**
 * Obter dados do usuário logado
 */
async function getCurrentUser() {
  return apiRequest(API_CONFIG.ENDPOINTS.ME, {
    method: 'GET',
  });
}

/**
 * Atualizar dados do usuário
 */
async function updateUser(userId, userData) {
  return apiRequest(API_CONFIG.ENDPOINTS.UPDATE_USER(userId), {
    method: 'PUT',
    body: JSON.stringify(userData),
  });
}
