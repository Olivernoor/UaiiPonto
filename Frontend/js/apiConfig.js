/**
 * Configuração central da API
 * Defina aqui a URL base do seu backend
 */

const API_CONFIG = {
  // URL base do backend - altere conforme necessário
  BASE_URL: 'http://localhost:8000',
  
  // Endpoints da API (sem prefixo /api/ pois será adicionado automaticamente)
  ENDPOINTS: {
    LOGIN: '/login',
    REGISTER: '/register',
    LOGOUT: '/logout',
    ME: '/me',
    UPDATE_USER: (userId) => `/users/${userId}`,
    // Endpoints de Batida de Ponto
    CHECK_IN: '/time-entries/check-in',
    CHECK_OUT: '/time-entries/check-out',
    TODAY_ENTRY: '/time-entries/today',
    HISTORY: '/time-entries/history',
    RANGE_BY_USER: (userId) => `/time-entries/user/${userId}/range`,
    DELETE_ENTRY: (entryId) => `/time-entries/${entryId}`,
    ALL_ENTRIES: '/time-entries',
  },

  // Chaves de armazenamento local
  STORAGE_KEYS: {
    TOKEN: 'token',
    USER: 'user',
  },
    // Usuário padrão para teste
  DEFAULT_USER: {
    email: 'admin@uaiiponto.com',
    password: '123456',
    name: 'Administrador',
    organization: 'UaiiPonto Sistemas'
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
  const url = `${API_CONFIG.BASE_URL}/api${endpoint}`;
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
    
    // Verificar se a resposta é JSON
    const contentType = response.headers.get('content-type');
    let data;
    
    if (contentType && contentType.includes('application/json')) {
      data = await response.json();
    } else {
      // Se não for JSON, trata como texto (pode ser erro HTML do servidor)
      const text = await response.text();
      data = { message: text.substring(0, 200) };
    }

    if (!response.ok) {
      throw new Error(data.message || `Erro ${response.status} do servidor`);
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

/**
 * Fazer check-in (registrar entrada)
 */
async function checkIn(notes = null) {
  return apiRequest(API_CONFIG.ENDPOINTS.CHECK_IN, {
    method: 'POST',
    body: JSON.stringify({ notes }),
  });
}

/**
 * Fazer check-out (registrar saída)
 */
async function checkOut(notes = null) {
  return apiRequest(API_CONFIG.ENDPOINTS.CHECK_OUT, {
    method: 'POST',
    body: JSON.stringify({ notes }),
  });
}

/**
 * Obter batida de hoje
 */
async function getTodayEntry() {
  return apiRequest(API_CONFIG.ENDPOINTS.TODAY_ENTRY, {
    method: 'GET',
  });
}

/**
 * Obter histórico de batidas
 */
async function getHistory(days = 30, page = 1, perPage = 10) {
  const query = `?days=${days}&page=${page}&per_page=${perPage}`;
  return apiRequest(API_CONFIG.ENDPOINTS.HISTORY + query, {
    method: 'GET',
  });
}

/**
 * Obter batidas de um período para um usuário
 */
async function getRangeByUser(userId, startDate, endDate) {
  return apiRequest(API_CONFIG.ENDPOINTS.RANGE_BY_USER(userId), {
    method: 'GET',
    body: JSON.stringify({ start_date: startDate, end_date: endDate }),
  });
}

/**
 * Deletar uma batida
 */
async function deleteEntry(entryId) {
  return apiRequest(API_CONFIG.ENDPOINTS.DELETE_ENTRY(entryId), {
    method: 'DELETE',
  });
}

/**
 * Obter todas as batidas (apenas admin/manager)
 */
async function getAllEntries(page = 1, perPage = 20, userId = null) {
  let query = `?page=${page}&per_page=${perPage}`;
  if (userId) query += `&user_id=${userId}`;
  return apiRequest(API_CONFIG.ENDPOINTS.ALL_ENTRIES + query, {
    method: 'GET',
  });
}

/**
 * Obter dados do usuário logado (alias para getCurrentUser)
 */
async function getMe() {
  return apiRequest(API_CONFIG.ENDPOINTS.ME, {
    method: 'GET',
  });
}

