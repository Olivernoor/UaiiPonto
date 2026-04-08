document.addEventListener("DOMContentLoaded", () => {
  // Verificar qual página estamos
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
  const form = document.querySelector(".login-card");
  const passwordInput = document.querySelector("#password");
  const feedback = document.createElement("div");
  feedback.id = "feedback";
  feedback.style.marginTop = "15px";
  form.appendChild(feedback);

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = document.querySelector("#email").value.trim();
    const password = passwordInput.value.trim();

    if (!email || !password) {
      showFeedback(feedback, "Por favor, preencha todos os campos!", "error");
      return;
    }

    showFeedback(feedback, "Carregando...", "");

    try {
      const result = await login(email, password);

      if (result.success) {
        showFeedback(feedback, "Login realizado com sucesso!", "success");

        // Armazenar token
        if (result.data.token) {
          localStorage.setItem("token", result.data.token);
        }

        // Armazenar dados do usuário
        if (result.data.user) {
          localStorage.setItem("user", JSON.stringify(result.data.user));
        }

        setTimeout(() => {
          window.location.href = "dashboard.html";
        }, 1500);
      } else {
        showFeedback(feedback, result.error || "Credenciais inválidas.", "error");
      }
    } catch (error) {
      showFeedback(feedback, "Erro de conexão com servidor.", "error");
    }
  });
}

/**
 * Configurar formulário de cadastro
 */
function setupRegisterForm() {
  const form = document.querySelector(".cadastro-card");
  const feedback = form.querySelector("#feedback");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const name = document.querySelector("#nome").value.trim();
    const email = document.querySelector("#email").value.trim();
    const organization = document.querySelector("#organizacao").value.trim();
    const password = document.querySelector("#senha").value.trim();
    const passwordConfirm = document.querySelector("#confirmar-senha").value.trim();

    // Validações
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

    try {
      const result = await register({
        name,
        email,
        organization,
        password,
        password_confirmation: passwordConfirm,
      });

      if (result.success) {
        showFeedback(feedback, "Cadastro realizado com sucesso! Redirecionando para login...", "success");
        
        setTimeout(() => {
          window.location.href = "login.html";
        }, 2000);
      } else {
        showFeedback(feedback, result.error || "Erro ao criar conta.", "error");
      }
    } catch (error) {
      showFeedback(feedback, "Erro de conexão com servidor.", "error");
    }
  });
}

/**
 * Mostrar feedback ao usuário
 */
function showFeedback(element, message, type) {
  element.textContent = message;
  element.className = type;
  if (type) {
    element.style.padding = "12px";
    element.style.borderRadius = "5px";
    element.style.marginTop = "15px";
  }

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