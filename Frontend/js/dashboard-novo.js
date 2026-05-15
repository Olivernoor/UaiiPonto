// dashboard.js - Refatorado para suportar 4 tipos de batida
// Entrada, Saída Almoço, Volta Almoço, Saída Final

let currentMap = null;
let currentLocation = null;
let areaCircle = null;
let areaMarker = null;
let todayStatus = null; // Dados consolidados de hoje {check_in, lunch_out, lunch_in, check_out}
let entryHistory = []; // Histórico de batidas

// Configuração da área permitida - FAEX Faculdade de Extrema (MATRIZ)
const AREA_PERMITIDA = {
  nome: "FAEX - Faculdade de Extrema (MATRIZ)",
  endereco: "Estrada Mun. Pedro Rosa d' Alivia, 303 – Extrema, MG, Brasil",
  latitude: -22.8515,
  longitude: -46.3178,
  raioMetros: 200  // 200 metros de raio
};

// Mapeamento de tipos de batida
const TIPOS_BATIDA = {
  'check_in': { label: 'Entrada', proximoTipo: 'lunch_out', emoji: '🟢' },
  'lunch_out': { label: 'Saída Almoço', proximoTipo: 'lunch_in', emoji: '🟡' },
  'lunch_in': { label: 'Volta Almoço', proximoTipo: 'check_out', emoji: '🟠' },
  'check_out': { label: 'Saída Final', proximoTipo: null, emoji: '🔴' },
};

// ============================================
// Funções de Integração com Servidor
// ============================================

// Carregar dados de batida de hoje
async function carregarBatidaHoje() {
  const resultado = await getTodayEntry();
  
  if (resultado.success && resultado.data) {
    // todayEntry agora é um objeto com .status contendo os dados de cada tipo
    todayStatus = resultado.data.status || {};
    atualizarStatusHoje();
  } else {
    todayStatus = null;
    atualizarStatusHoje();
  }
}

// Carregar histórico
async function carregarHistorico() {
  const resultado = await getHistory(30, 1, 100);
  
  if (resultado.success && resultado.data) {
    // Se vem paginado, extrair os dados
    entryHistory = resultado.data.data ? resultado.data.data : resultado.data;
    if (!Array.isArray(entryHistory)) {
      entryHistory = [];
    }
  } else {
    entryHistory = [];
  }
  
  atualizarHistoricoDia();
}

// Registrar batida - suporta os 4 tipos
async function registrarBatida() {
  // Determinar o próximo tipo de batida
  let proximoTipo = 'check_in'; // Padrão
  
  if (todayStatus) {
    if (todayStatus.check_in && !todayStatus.lunch_out) {
      proximoTipo = 'lunch_out';
    } else if (todayStatus.lunch_out && !todayStatus.lunch_in) {
      proximoTipo = 'lunch_in';
    } else if (todayStatus.lunch_in && !todayStatus.check_out) {
      proximoTipo = 'check_out';
    } else if (todayStatus.check_out) {
      exibirFeedback('error', 'Você já finalizou o expediente hoje!');
      return;
    }
  }

  // Obter localização
  let latitude = null;
  let longitude = null;
  let location = 'Não informada';

  if (currentLocation) {
    latitude = currentLocation.latitude;
    longitude = currentLocation.longitude;
    location = `${latitude.toFixed(4)}, ${longitude.toFixed(4)}`;
  }

  // Fazer requisição à API
  const resultado = await registrarPonto(proximoTipo, location, latitude, longitude);

  if (resultado.success) {
    exibirFeedback('success', `${TIPOS_BATIDA[proximoTipo].label} registrado com sucesso às ${new Date().toLocaleTimeString('pt-BR')}`);
    
    // Recarregar dados
    setTimeout(() => {
      carregarBatidaHoje();
      carregarHistorico();
    }, 500);
  } else {
    exibirFeedback('error', `Erro ao registrar ${TIPOS_BATIDA[proximoTipo].label}: ${resultado.error}`);
  }
}

// Função para registrar um tipo de batida via API
async function registrarPonto(tipo, location, latitude, longitude) {
  return apiRequest(API_CONFIG.ENDPOINTS.CHECK_IN, {
    method: 'POST',
    body: JSON.stringify({
      type: tipo,
      location: location,
      latitude: latitude,
      longitude: longitude,
      notes: `${TIPOS_BATIDA[tipo].label} - ${new Date().toLocaleString('pt-BR')}`
    }),
  });
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

// Atualizar Status de Hoje - Mostra os 4 tipos
function atualizarStatusHoje() {
  const entrada = document.getElementById('status-entrada');
  const saidaAlmoco = document.getElementById('status-saida-almoco');
  const voltaAlmoco = document.getElementById('status-volta-almoco');
  const saida = document.getElementById('status-saida');
  const proxBtn = document.getElementById('btn-registrar-ponto');
  
  // Limpar
  if (entrada) entrada.textContent = '--:--';
  if (saidaAlmoco) saidaAlmoco.textContent = '--:--';
  if (voltaAlmoco) voltaAlmoco.textContent = '--:--';
  if (saida) saida.textContent = '--:--';
  
  // Preencher com dados de hoje
  if (todayStatus) {
    if (todayStatus.check_in && entrada) {
      entrada.textContent = todayStatus.check_in.time;
    }
    if (todayStatus.lunch_out && saidaAlmoco) {
      saidaAlmoco.textContent = todayStatus.lunch_out.time;
    }
    if (todayStatus.lunch_in && voltaAlmoco) {
      voltaAlmoco.textContent = todayStatus.lunch_in.time;
    }
    if (todayStatus.check_out && saida) {
      saida.textContent = todayStatus.check_out.time;
    }
  }
  
  // Atualizar botão e label
  if (proxBtn) {
    let proximoTipo = 'check_in';
    let proximoLabel = '📝 Registrar Entrada';

    if (todayStatus) {
      if (todayStatus.check_in && !todayStatus.lunch_out) {
        proximoTipo = 'lunch_out';
        proximoLabel = '📝 Registrar Saída Almoço';
      } else if (todayStatus.lunch_out && !todayStatus.lunch_in) {
        proximoTipo = 'lunch_in';
        proximoLabel = '📝 Registrar Volta Almoço';
      } else if (todayStatus.lunch_in && !todayStatus.check_out) {
        proximoTipo = 'check_out';
        proximoLabel = '📝 Registrar Saída Final';
      } else if (todayStatus.check_out) {
        proximoLabel = '✅ Expediente Finalizado';
        proxBtn.disabled = true;
      }
    }

    proxBtn.textContent = proximoLabel;
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
  
  // Renderizar lista
  listaEl.innerHTML = batidaHoje.map(entry => {
    const hora = new Date(entry.check_in).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
    const tipo = TIPOS_BATIDA[entry.type]?.label || entry.type;
    const emoji = TIPOS_BATIDA[entry.type]?.emoji || '⏱️';
    return `
      <li>
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <div>
            <strong>${tipo}</strong><br>
            <small style="color: #666;">${hora}</small>
          </div>
          <span>${emoji}</span>
        </div>
      </li>
    `;
  }).join('');
}

// ============================================
// Relatórios e Exportação
// ============================================

// Gerar relatório em Excel
async function gerarRelatorioExcel() {
  const resultado = await apiRequest('/time-entries/export/excel', {
    method: 'GET'
  });

  if (!resultado.success) {
    alert('Erro ao gerar relatório: ' + resultado.error);
    return;
  }

  const dados = resultado.data;

  // Usar biblioteca XLSX se disponível, senão usar CSV
  if (typeof XLSX !== 'undefined') {
    gerarExcelComXLSX(dados);
  } else {
    gerarCSV(dados);
  }
}

// Gerar Excel usando XLSX
function gerarExcelComXLSX(dados) {
  // Criar workbook
  const wb = XLSX.utils.book_new();
  
  // Preparar dados
  const wsData = [
    ['RELATÓRIO DE BATIDA DE PONTO'],
    [],
    ['Usuário:', dados.usuario.nome],
    ['Email:', dados.usuario.email],
    ['Organização:', dados.usuario.organização],
    ['Data de Exportação:', dados.data_exportacao],
    [],
    ['Período'],
    ['Início:', dados.periodo.inicio],
    ['Fim:', dados.periodo.fim],
    [],
    // Headers da tabela
    ['Data', 'Tipo', 'Hora', 'Localização', 'Latitude', 'Longitude', 'Horas', 'Status', 'Notas']
  ];

  // Adicionar dados
  dados.dados.forEach(row => {
    wsData.push([
      row['Data'],
      row['Tipo'],
      row['Hora'],
      row['Localização'],
      row['Latitude'],
      row['Longitude'],
      row['Horas Trabalhadas'],
      row['Status'],
      row['Notas']
    ]);
  });

  const ws = XLSX.utils.aoa_to_sheet(wsData);
  XLSX.utils.book_append_sheet(wb, ws, 'Relatório');
  
  // Baixar
  XLSX.writeFile(wb, `relatorio_ponto_${dados.usuario.nome}_${new Date().toISOString().split('T')[0]}.xlsx`);
}

// Gerar CSV como fallback
function gerarCSV(dados) {
  let csv = 'Data,Tipo,Hora,Localização,Latitude,Longitude,Horas,Status,Notas\n';
  
  dados.dados.forEach(row => {
    csv += `"${row['Data']}","${row['Tipo']}","${row['Hora']}","${row['Localização']}",${row['Latitude']},${row['Longitude']},"${row['Horas Trabalhadas']}","${row['Status']}","${row['Notas']}"\n`;
  });

  const blob = new Blob([csv], { type: 'text/csv' });
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `relatorio_ponto_${dados.usuario.nome}_${new Date().toISOString().split('T')[0]}.csv`;
  a.click();
}

// ============================================
// Mapa e Localização
// ============================================

// Inicializar mapa
function initMap() {
  currentMap = L.map('mapa').setView([AREA_PERMITIDA.latitude, AREA_PERMITIDA.longitude], 15);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(currentMap);
  
  // Marcador da empresa
  areaMarker = L.marker([AREA_PERMITIDA.latitude, AREA_PERMITIDA.longitude]).addTo(currentMap);
  areaMarker.bindPopup(`<strong>${AREA_PERMITIDA.nome}</strong><br>${AREA_PERMITIDA.endereco}`);
  
  // Círculo de raio permitido
  areaCircle = L.circle([AREA_PERMITIDA.latitude, AREA_PERMITIDA.longitude], {
    radius: AREA_PERMITIDA.raioMetros,
    color: '#2196F3',
    fill: true,
    fillOpacity: 0.1
  }).addTo(currentMap);
}

// Ativar localização
function ativarLocalizacao() {
  const msgDiv = document.querySelector('[data-localizacao-status]') || document.getElementById('localizacao-status');
  
  if (!navigator.geolocation) {
    if (msgDiv) msgDiv.textContent = '❌ Geolocalização não suportada';
    return;
  }

  if (msgDiv) msgDiv.textContent = '📍 Obtendo localização...';

  navigator.geolocation.getCurrentPosition(
    (position) => {
      currentLocation = {
        latitude: position.coords.latitude,
        longitude: position.coords.longitude,
        accuracy: position.coords.accuracy
      };

      const dentro = estaDentroAreaPermitida(currentLocation.latitude, currentLocation.longitude);
      
      if (msgDiv) {
        msgDiv.textContent = dentro 
          ? `✅ Você está na área permitida (${currentLocation.accuracy.toFixed(0)}m)`
          : `⚠️ Você está fora da área (${currentLocation.accuracy.toFixed(0)}m)`;
      }

      // Adicionar marcador no mapa
      if (currentMap) {
        L.marker([currentLocation.latitude, currentLocation.longitude], {
          icon: L.icon({
            iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34]
          })
        }).addTo(currentMap).bindPopup('Você está aqui');
      }
    },
    (error) => {
      if (msgDiv) msgDiv.textContent = `❌ Permissão negada ou erro: ${error.message}`;
    }
  );
}

// ============================================
// Inicialização
// ============================================

document.addEventListener('DOMContentLoaded', async () => {
  // Elementos do DOM
  const btnRegistrar = document.getElementById('btn-registrar-ponto');
  const btnAtivarlocalização = document.getElementById('btn-ativar-localizacao');
  const btnRelatorio = document.getElementById('btn-relatorio');
  const btnLogout = document.getElementById('btn-logout');
  const msgFeedback = document.getElementById('feedback') || document.querySelector('.feedback');

  // Verificar autenticação
  const token = localStorage.getItem(API_CONFIG.STORAGE_KEYS.TOKEN);
  if (!token) {
    window.location.href = '/pages/login.html';
    return;
  }

  // Carregar dados do usuário
  const userResult = await getMe();
  if (userResult.success) {
    const userSpan = document.querySelector('span[data-user-name]') || 
                     document.querySelector('.user-name') ||
                     document.querySelector('h2');
    if (userSpan) {
      userSpan.textContent = `Olá, ${userResult.data.name}`;
    }
  }

  // Inicializar mapa
  if (document.getElementById('mapa')) {
    initMap();
  }

  // Carregar dados de hoje
  carregarBatidaHoje();
  carregarHistorico();

  // Event listeners
  if (btnRegistrar) {
    btnRegistrar.addEventListener('click', registrarBatida);
  }

  if (btnAtivarlocalização) {
    btnAtivarlocalização.addEventListener('click', ativarLocalizacao);
  }

  if (btnRelatorio) {
    btnRelatorio.addEventListener('click', gerarRelatorioExcel);
  }

  if (btnLogout) {
    btnLogout.addEventListener('click', async () => {
      const result = await logout();
      if (result.success) {
        localStorage.clear();
        window.location.href = '/pages/login.html';
      }
    });
  }

  // Recarregar a cada 30 segundos
  setInterval(() => {
    carregarBatidaHoje();
    carregarHistorico();
  }, 30000);
});

// ============================================
// Funções Auxiliares
// ============================================

function showFeedback(element, message, type) {
  if (!element) return;
  
  element.textContent = (type === 'error' ? '❌ ' : '✅ ') + message;
  element.style.color = type === 'error' ? '#d32f2f' : '#388e3c';
  element.style.display = 'block';

  setTimeout(() => {
    element.style.display = 'none';
  }, 5000);
}

// Exibir feedback usando o elemento global
function exibirFeedback(tipo, mensagem, tempo = 3000) {
  const feedback = document.getElementById('global-feedback');
  if (!feedback) return;
  
  feedback.textContent = mensagem;
  feedback.className = `feedback-msg ${tipo}`;
  feedback.style.display = 'block';

  setTimeout(() => {
    feedback.style.display = 'none';
  }, tempo);
}

// ============================================
// Navegação de Abas
// ============================================

// Event listener para navegação de abas
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('nav-btn')) {
    const page = e.target.getAttribute('data-page');
    if (!page) return;
    
    // Remover ativo de todos os botões
    document.querySelectorAll('.nav-btn').forEach(btn => btn.classList.remove('active'));
    
    // Ativar o botão clicado
    e.target.classList.add('active');
    
    // Ocultar todas as páginas
    document.querySelectorAll('.page-content').forEach(page_elem => {
      page_elem.style.display = 'none';
    });
    
    // Mostrar a página selecionada
    const targetPage = document.querySelector(`#page-${page}`);
    if (targetPage) {
      targetPage.style.display = 'block';
      
      // Se é a página de relatórios e o botão de relatório foi clicado
      if (page === 'relatorio') {
        // Adicionar event listener ao botão de relatório se não existir
        const btnRelatorio = document.querySelector('#btn-relatorio');
        if (btnRelatorio && !btnRelatorio.dataset.listenerAdded) {
          btnRelatorio.addEventListener('click', gerarRelatorioExcel);
          btnRelatorio.dataset.listenerAdded = 'true';
        }
      }
    }
  }
});

// Adicionar event listener ao botão logout
document.addEventListener('DOMContentLoaded', () => {
  const btnLogout = document.querySelector('#btn-logout');
  if (btnLogout) {
    btnLogout.addEventListener('click', () => {
      logout().then(() => {
        window.location.href = '/pages/login.html';
      });
    });
  }
});
