// Sistema conecta com o backend via API real

document.addEventListener("DOMContentLoaded", () => {
  const isLoginPage = document.querySelector(".login-card");
  const isRegisterPage = document.querySelector(".cadastro-card");

  if (isLoginPage) {
    setupLoginForm();
  }

  if (isRegisterPage) {
    setupRegisterForm();
  }
});

/**
 * Configurar formulário de login
 */
function setupLoginForm() {
  const togglePassword = document.querySelector(".toggle-password");
  const passwordInput = document.querySelector("#password");
  const form = document.querySelector(".login-card");
  const feedback = document.createElement("div");
  feedback.id = "feedback";
  form.appendChild(feedback);

  // Toggle para mostrar/esconder senha
  if (togglePassword) {
    togglePassword.addEventListener("click", () => {
      const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
      togglePassword.textContent = type === "password" ? "👁️" : "🙈";
    });
  }

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = document.querySelector("#email").value.trim();
    const password = passwordInput.value.trim();

    if (!email || !password) {
      showFeedback(feedback, "Por favor, preencha todos os campos!", "error");
      return;
    }

    showFeedback(feedback, "Verificando credenciais...", "");

    // Fazer login via API real
    const result = await loginWrapper(email, password);
    
    if (result.success) {
      showFeedback(feedback, "Login realizado com sucesso!", "success");
      
      // Salvar token e usuário no localStorage
      localStorage.setItem(API_CONFIG.STORAGE_KEYS.TOKEN, result.data.token);
      localStorage.setItem(API_CONFIG.STORAGE_KEYS.USER, JSON.stringify(result.data.user));

      // Redirecionar para o dashboard
      setTimeout(() => {
        window.location.href = "dashboard.html";
      }, 1500);
    } else {
      showFeedback(feedback, result.error || "E-mail ou senha inválidos!", "error");
    }
  });
}

/**
 * Configurar formulário de cadastro
 */
function setupRegisterForm() {
  const form = document.querySelector(".cadastro-card");
  const feedback = form.querySelector("#feedback") || (() => {
    const f = document.createElement("div");
    f.id = "feedback";
    form.appendChild(f);
    return f;
  })();

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const name = document.querySelector("#nome").value.trim();
    const email = document.querySelector("#email").value.trim();
    const organization = document.querySelector("#organizacao").value.trim();
    const password = document.querySelector("#senha").value.trim();
    const passwordConfirm = document.querySelector("#confirmar-senha").value.trim();

    if (!name || !email || !organization || !password || !passwordConfirm) {
      showFeedback(feedback, "Por favor, preencha todos os campos!", "error");
      return;
    }

    if (password !== passwordConfirm) {
      showFeedback(feedback, "As senhas não conferem!", "error");
      return;
    }

    if (password.length < 6) {
      showFeedback(feedback, "A senha deve ter no mínimo 6 caracteres!", "error");
      return;
    }

    showFeedback(feedback, "Criando conta...", "");

    // Registrar via API real
    const result = await registerWrapper({ name, email, organization, password });
    
    if (result.success) {
      showFeedback(feedback, "Cadastro realizado com sucesso! Redirecionando para login...", "success");
      setTimeout(() => {
        window.location.href = "login.html";
      }, 2000);
    } else {
      showFeedback(feedback, result.error || "Erro ao criar conta. Tente novamente.", "error");
    }
  });
}

/**
 * Função de login - chama a API real
 */
async function loginWrapper(email, password) {
  const result = await apiRequest(API_CONFIG.ENDPOINTS.LOGIN, {
    method: 'POST',
    body: JSON.stringify({ email, password }),
  });
  
  if (result.success) {
    return {
      success: true,
      data: result.data
    };
  } else {
    return {
      success: false,
      error: result.error
    };
  }
}

/**
 * Função de registro - chama a API real
 */
async function registerWrapper(userData) {
  const result = await apiRequest(API_CONFIG.ENDPOINTS.REGISTER, {
    method: 'POST',
    body: JSON.stringify(userData),
  });
  
  if (result.success) {
    return {
      success: true,
      data: result.data
    };
  } else {
    return {
      success: false,
      error: result.error
    };
  }
}

/**
 * Função de logout - chama a API real
 */
async function logoutWrapper() {
  const result = await apiRequest(API_CONFIG.ENDPOINTS.LOGOUT, {
    method: 'POST',
  });
  
  // Limpar dados locais sempre (mesmo se API falhar)
  localStorage.removeItem(API_CONFIG.STORAGE_KEYS.TOKEN);
  localStorage.removeItem(API_CONFIG.STORAGE_KEYS.USER);
  
  return {
    success: result.success,
    error: result.error
  };
}

/**
 * Fazer logout e redirecionar
 */
async function logout() {
  await logoutWrapper();
  window.location.href = "login.html";
}

/**
 * Mostrar feedback ao usuário
 */
function showFeedback(element, message, type) {
  element.textContent = message;
  element.className = type;
  element.style.padding = "12px";
  element.style.borderRadius = "5px";
  element.style.marginTop = "15px";

  if (type === "success") {
    element.style.background = "#d4edda";
    element.style.color = "#155724";
    element.style.border = "1px solid #c3e6cb";
  } else if (type === "error") {
    element.style.background = "#f8d7da";
    element.style.color = "#721c24";
    element.style.border = "1px solid #f5c6cb";
  }
}
