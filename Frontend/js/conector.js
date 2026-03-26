document.addEventListener("DOMContentLoaded", () => {
  const togglePassword = document.querySelector(".toggle-password");
  const passwordInput = document.querySelector("#password");
  const form = document.querySelector(".login-card");
  const feedback = document.createElement("div");
  feedback.id = "feedback";
  form.appendChild(feedback);

  
  if (togglePassword) {
    togglePassword.addEventListener("click", () => {
      const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
      passwordInput.setAttribute("type", type);
    });
  }

  
  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const email = document.querySelector("#email").value.trim();
    const password = passwordInput.value.trim();

    
    if (!email || !password) {
      feedback.textContent = "Por favor, preencha todos os campos!";
      feedback.className = "error";
      return;
    }

    
    feedback.textContent = "Carregando...";
    feedback.className = "";

    try {
      
      const response = await fetch("http://localhost:8000/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, senha: password })
      });

      const result = await response.json();

      if (response.ok) {
        feedback.textContent = "Login realizado com sucesso!";
        feedback.className = "success";

        
        if (result.token) {
          localStorage.setItem("token", result.token);
        }

        
        setTimeout(() => {
          window.location.href = "dashboard.html";
        }, 1500);
      } else {
        feedback.textContent = result.message || "Credenciais inválidas.";
        feedback.className = "error";
      }
    } catch (error) {
      feedback.textContent = "Erro de conexão com servidor.";
      feedback.className = "error";
    }
  });
});