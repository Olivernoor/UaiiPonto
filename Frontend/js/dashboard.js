// dashboard.js - Integrado com API Backend

let currentMap = null;
let currentLocation = null;
let areaCircle = null;
let areaMarker = null;
let todayEntry = null; // Dados da batida de hoje do servidor
let entryHistory = []; // Histórico de batidas

// Configuração da área permitida - FAEX Faculdade de Extrema (MATRIZ)
const AREA_PERMITIDA = {
  nome: "FAEX - Faculdade de Extrema (MATRIZ)",
  endereco: "Estrada Mun. Pedro Rosa d' Alivia, 303 – Extrema, MG, Brasil",
  latitude: -22.8515,
  longitude: -46.3178,
  raioMetros: 200  // 200 metros de raio
};

// ============================================
// Funções de Integração com Servidor
// ============================================

// Carregar dados de batida de hoje
async function carregarBatidaHoje() {
  const resultado = await getTodayEntry();
  
  if (resultado.success && resultado.data) {
    todayEntry = resultado.data;
    atualizarStatusHoje();
  } else {
    todayEntry = null;
    atualizarStatusHoje();
  }
}

// Carregar histórico
async function carregarHistorico() {
  const resultado = await getHistory(30, 1, 50);
  
  if (resultado.success && resultado.data) {
    // Se vem paginado, extrair os dados
    entryHistory = resultado.data.data ? resultado.data.data : resultado.data;
    if (!Array.isArray(entryHistory)) {
      entryHistory = [];
    }
  } else {
    entryHistory = [];
  }
}

// Calcular distância entre duas coordenadas (Haversine formula)
function calcularDistancia(lat1, lon1, lat2, lon2) {
  const R = 6371000; // Raio da Terra em metros
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon/2) * Math.sin(dLon/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c;
}

// Verificar se está dentro da área permitida
function estaDentroAreaPermitida(lat, lng) {
  const distancia = calcularDistancia(lat, lng, AREA_PERMITIDA.latitude, AREA_PERMITIDA.longitude);
  return distancia <= AREA_PERMITIDA.raioMetros;
}

// Obter data atual formatada
function getCurrentDateTime() {
  const now = new Date();
  const dataStr = now.toLocaleDateString('pt-BR');
  const horaStr = now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  return { data: dataStr, hora: horaStr, timestamp: now.getTime(), dataCompleta: now };
}

// Obter batidas do dia atual
function getBatidasHoje() {
  const hoje = new Date().toLocaleDateString('pt-BR');
  return pontoRegistros.filter(r => r.data === hoje);
}

// Determinar próximo tipo de batida
function getProximoTipo(batidasHoje) {
  if (!todayEntry || todayEntry.check_out) {
    return 'Entrada';
  }
  return 'Saída';
}

// Registrar batida (Check-In ou Check-Out) - INTEGRADO COM API
async function registrarBatida() {
  // Determinar se é check-in ou check-out
  let resultado;
  let tipoOperacao;
  
  if (!todayEntry || todayEntry.check_out) {
    // Fazer check-in
    resultado = await checkIn(`Entrada registrada - ${new Date().toLocaleTimeString()}`);
    tipoOperacao = 'Check-in';
  } else {
    // Fazer check-out
    resultado = await checkOut(`Saída registrada - ${new Date().toLocaleTimeString()}`);
    tipoOperacao = 'Check-out';
  }

  if (resultado.success) {
    todayEntry = resultado.data;
    exibirFeedback('success', `✅ ${tipoOperacao} registrado com sucesso às ${new Date().toLocaleTimeString()}`);
    
    // Atualizar interface
    atualizarStatusHoje();
    await carregarHistorico();
    atualizarHistoricoDia();
    
    // Adicionar marcador no mapa se tiver localização
    if (currentMap && currentLocation && currentLocation.lat && currentLocation.lng) {
      const icon = L.divIcon({
        html: `<div style="background: #007bff; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>`,
        iconSize: [12, 12],
        className: 'custom-marker'
      });
      L.marker([currentLocation.lat, currentLocation.lng], { icon })
        .addTo(currentMap)
        .bindPopup(`${tipoOperacao} - ${new Date().toLocaleTimeString()}`)
        .openPopup();
    }
    
    return true;
  } else {
    exibirFeedback('error', `❌ Erro ao registrar ${tipoOperacao}: ${resultado.error}`, 5000);
    return false;
  }
}

// Atualizar status do dia
function atualizarStatusHoje() {
  const entrada = document.getElementById('status-entrada');
  const saidaAlmoco = document.getElementById('status-saida-almoco');
  const voltaAlmoco = document.getElementById('status-volta-almoco');
  const saida = document.getElementById('status-saida');
  const proxBtn = document.getElementById('proxima-batida-label');
  
  // Limpar
  if (entrada) entrada.textContent = '--:--';
  if (saidaAlmoco) saidaAlmoco.textContent = '--:--';
  if (voltaAlmoco) voltaAlmoco.textContent = '--:--';
  if (saida) saida.textContent = '--:--';
  
  // Se temos dados de hoje, preencher
  if (todayEntry) {
    if (todayEntry.check_in && entrada) {
      const horaEntrada = new Date(todayEntry.check_in).toLocaleTimeString('pt-BR', { 
        hour: '2-digit', 
        minute: '2-digit' 
      });
      entrada.textContent = horaEntrada;
    }
    
    if (todayEntry.check_out && saida) {
      const horaSaida = new Date(todayEntry.check_out).toLocaleTimeString('pt-BR', { 
        hour: '2-digit', 
        minute: '2-digit' 
      });
      saida.textContent = horaSaida;
    }
  }
  
  // Atualizar botão
  if (proxBtn) {
    if (!todayEntry || todayEntry.check_out) {
      proxBtn.textContent = 'Registrar Entrada';
    } else {
      proxBtn.textContent = 'Registrar Saída';
    }
  }
}

// Atualizar lista de histórico do dia
function atualizarHistoricoDia() {
  const listaEl = document.getElementById('historico-hoje-lista');
  if (!listaEl) return;
  
  if (!entryHistory || entryHistory.length === 0) {
    listaEl.innerHTML = '<li style="text-align:center; color:#999;">Nenhuma batida hoje</li>';
    return;
  }
  
  // Filtrar apenas de hoje
  const hoje = new Date().toDateString();
  const batidaHoje = entryHistory.filter(entry => {
    const dataEntry = new Date(entry.check_in).toDateString();
    return dataEntry === hoje;
  });
  
  if (batidaHoje.length === 0) {
    listaEl.innerHTML = '<li style="text-align:center; color:#999;">Nenhuma batida hoje</li>';
    return;
  }
  
  listaEl.innerHTML = batidaHoje.map(entry => {
    const hora = new Date(entry.check_in).toLocaleTimeString('pt-BR', { 
      hour: '2-digit', 
      minute: '2-digit' 
    });
    const tipo = entry.check_out ? 'Saída' : 'Entrada';
    
    return `
      <li>
        <span>${tipo}</span>
        <span><strong>${hora}</strong></span>
        <span style="font-size: 11px; color: green;">✓</span>
      </li>
    `;
  }).join('');
}

// Inicializar mapa (referência visual)
function initMap() {
  if (currentMap) {
    currentMap.remove();
  }
  
  currentMap = L.map('mapa').setView([AREA_PERMITIDA.latitude, AREA_PERMITIDA.longitude], 16);
  
  // Tile layer gratuito
  L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OSM</a> & CartoDB',
    subdomains: 'abcd',
    maxZoom: 19
  }).addTo(currentMap);
  
  // Adicionar marcador da empresa (referência)
  areaMarker = L.marker([AREA_PERMITIDA.latitude, AREA_PERMITIDA.longitude])
    .addTo(currentMap)
    .bindPopup(`
      <strong>${AREA_PERMITIDA.nome}</strong><br>
      ${AREA_PERMITIDA.endereco}
    `)
    .openPopup();
  
  // Atualizar endereço no HTML
  const matrizEndereco = document.getElementById('matriz-endereco');
  if (matrizEndereco) {
    matrizEndereco.innerHTML = `
      <strong>📍 Empresa (Referência):</strong><br>
      ${AREA_PERMITIDA.nome}<br>
      ${AREA_PERMITIDA.endereco}
    `;
  }
  
  if (currentLocation?.lat && currentLocation?.lng) {
    adicionarMarcadorUsuario(currentLocation.lat, currentLocation.lng);
  }
}

// Adicionar marcador do usuário
function adicionarMarcadorUsuario(lat, lng) {
  if (!currentMap) return;
  
  const icon = L.divIcon({
    html: `<div style="background: #007bff; width: 16px; height: 16px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 5px rgba(0,0,0,0.5);"></div>`,
    iconSize: [16, 16],
    className: 'user-marker'
  });
  
  L.marker([lat, lng], { icon })
    .addTo(currentMap)
    .bindPopup(`
      <strong>Sua localização</strong><br>
      ${lat.toFixed(6)}, ${lng.toFixed(6)}
    `)
    .openPopup();
}

// Obter localização do usuário (opcional - não obrigatório)
function obterLocalizacao() {
  const feedbackLoc = document.getElementById('feedback-localizacao');
  const statusArea = document.getElementById('status-area');
  
  if (!navigator.geolocation) {
    if (feedbackLoc) feedbackLoc.innerHTML = '❌ Seu navegador não suporta geolocalização';
    return;
  }
  
  if (feedbackLoc) feedbackLoc.innerHTML = '🔄 Buscando localização...';
  
  navigator.geolocation.getCurrentPosition(
    async (position) => {
      const { latitude, longitude } = position.coords;
      
      currentLocation = { 
        lat: latitude, 
        lng: longitude, 
        endereco: `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`
      };
      
      // Atualizar feedback
      if (feedbackLoc) {
        feedbackLoc.innerHTML = `✅ Localização capturada`;
        feedbackLoc.style.color = '#155724';
      }
      
      // Atualizar status da área
      if (statusArea) {
        statusArea.innerHTML = `<span style="color: green;">✅ Localização ativa</span>`;
      }
      
      // Centralizar mapa e adicionar marcador
      if (currentMap) {
        currentMap.setView([latitude, longitude], 17);
        adicionarMarcadorUsuario(latitude, longitude);
      } else {
        initMap();
        setTimeout(() => adicionarMarcadorUsuario(latitude, longitude), 500);
      }
      
      localStorage.setItem('ultima_localizacao', JSON.stringify(currentLocation));
    },
    (error) => {
      let msg = '📍 Localização não disponível. ';
      switch(error.code) {
        case error.PERMISSION_DENIED: msg += 'Permissão negada.'; break;
        case error.POSITION_UNAVAILABLE: msg += 'Sinal não disponível.'; break;
        case error.TIMEOUT: msg += 'Tempo esgotado.'; break;
        default: msg += 'Tente novamente.';
      }
      if (feedbackLoc) feedbackLoc.innerHTML = msg;
      if (!currentMap) initMap();
    },
    { enableHighAccuracy: true, timeout: 10000 }
  );
}

// Exibir feedback
function exibirFeedback(tipo, mensagem, tempo = 3000) {
  const feedback = document.getElementById('global-feedback');
  if (!feedback) return;
  feedback.textContent = mensagem;
  feedback.className = `feedback-msg ${tipo}`;
  setTimeout(() => {
    if (feedback.textContent === mensagem) {
      feedback.className = 'feedback-msg';
      feedback.textContent = '';
    }
  }, tempo);
}

// Carregar histórico completo
function carregarHistoricoCompleto() {
  const container = document.getElementById('historico-completo-list');
  if (!container) return;
  
  if (!entryHistory || entryHistory.length === 0) {
    container.innerHTML = '<div class="historico-item">Nenhum registro encontrado</div>';
    return;
  }
  
  const registrosOrdenados = [...entryHistory].reverse();
  
  container.innerHTML = registrosOrdenados.map(entry => {
    const data = new Date(entry.check_in).toLocaleDateString('pt-BR');
    const hora = new Date(entry.check_in).toLocaleTimeString('pt-BR', { 
      hour: '2-digit', 
      minute: '2-digit' 
    });
    const tipo = entry.check_out ? 'Saída' : 'Entrada';
    
    return `
      <div class="historico-item">
        <div class="historico-data">📅 ${data} - ${tipo}</div>
        <div class="historico-detalhe">
          <span>⏰ ${hora}</span>
          <span>🔔 Status: ${entry.status || 'Não definido'}</span>
          <span style="color: green">✓ Registrado no servidor</span>
        </div>
      </div>
    `;
  }).join('');
}

// Gerar relatório
function gerarRelatorio() {
  const container = document.getElementById('relatorio-conteudo');
  if (!container) return;
  
  if (!entryHistory || entryHistory.length === 0) {
    container.innerHTML = '<div class="historico-item">Nenhum registro para gerar relatório</div>';
    return;
  }
  
  const porData = {};
  entryHistory.forEach(entry => {
    const data = new Date(entry.check_in).toLocaleDateString('pt-BR');
    if (!porData[data]) porData[data] = [];
    porData[data].push(entry);
  });
  
  let html = '';
  for (const [data, entries] of Object.entries(porData).sort().reverse()) {
    const totalHoras = entries.reduce((sum, e) => sum + (e.worked_hours || 0), 0);
    html += `<div class="historico-item" style="margin-bottom:15px;">
      <div class="historico-data">📆 ${data} (${totalHoras.toFixed(2)}h)</div>`;
    entries.forEach(entry => {
      const hora = new Date(entry.check_in).toLocaleTimeString('pt-BR', { 
        hour: '2-digit', 
        minute: '2-digit' 
      });
      const tipo = entry.check_out ? 'Saída' : 'Entrada';
      html += `<div class="historico-detalhe">
        🔹 ${tipo}: ${hora} - Status: ${entry.status || 'N/A'}
      </div>`;
    });
    html += `</div>`;
  }
  
  container.innerHTML = html;
}

// Alternar páginas
function showPage(pageId) {
  document.querySelectorAll('.page-content').forEach(page => {
    page.style.display = 'none';
  });
  
  const selected = document.getElementById(`page-${pageId}`);
  if (selected) selected.style.display = 'block';
  
  document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.classList.remove('active');
    if (btn.dataset.page === pageId) btn.classList.add('active');
  });
  
  if (pageId === 'registro') {
    atualizarStatusHoje();
    atualizarHistoricoDia();
    if (!currentMap) initMap();
    if (currentLocation) {
      adicionarMarcadorUsuario(currentLocation.lat, currentLocation.lng);
    }
  } else if (pageId === 'historico') {
    carregarHistoricoCompleto();
  } else if (pageId === 'relatorio') {
    gerarRelatorio();
  } else if (pageId === 'config') {
    carregarConfiguracoes();
  }
}

// Configurações
function carregarConfiguracoes() {
  const user = localStorage.getItem('user');
  if (!user) return;
  
  try {
    const userData = JSON.parse(user);
    const nomeInput = document.getElementById('config-nome');
    if (nomeInput) nomeInput.value = userData.name || '';
  } catch(e) {}
  
  const notifCheck = document.getElementById('config-notificacoes');
  if (notifCheck) notifCheck.value = 'true';
}

function salvarConfiguracoes() {
  exibirFeedback('success', 'Configurações salvas com sucesso!');
}

// Logout
async function fazerLogout() {
  await logout();
  localStorage.removeItem('token');
  localStorage.removeItem('user');
  window.location.href = 'login.html';
}

// ============================================
// Inicialização (DOMContentLoaded)
// ============================================

document.addEventListener('DOMContentLoaded', async () => {
  const user = localStorage.getItem('user');
  if (!user) {
    window.location.href = 'login.html';
    return;
  }
  
  // Exibir nome do usuário
  try {
    const userData = JSON.parse(user);
    const welcomeSpan = document.getElementById('user-name-welcome');
    if (welcomeSpan) welcomeSpan.textContent = userData.name || 'Usuário';
  } catch(e) {}
  
  // Carregar dados iniciais do servidor
  await carregarBatidaHoje();
  await carregarHistorico();
  atualizarStatusHoje();
  atualizarHistoricoDia();
  initMap();
  
  // Setup de event listeners
  const btnRegistrar = document.getElementById('btn-registrar-ponto');
  if (btnRegistrar) {
    btnRegistrar.addEventListener('click', registrarBatida);
  }
  
  const btnLocalizacao = document.getElementById('btn-ativar-localizacao');
  if (btnLocalizacao) {
    btnLocalizacao.addEventListener('click', obterLocalizacao);
  }
  
  document.querySelectorAll('.nav-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      showPage(btn.dataset.page);
    });
  });
  
  const btnSair = document.getElementById('btn-sair-footer');
  if (btnSair) {
    btnSair.addEventListener('click', fazerLogout);
  }
  
  const btnSalvarConfig = document.getElementById('btn-salvar-config');
  if (btnSalvarConfig) {
    btnSalvarConfig.addEventListener('click', salvarConfiguracoes);
  }
  
  showPage('registro');
  setTimeout(obterLocalizacao, 500);
});