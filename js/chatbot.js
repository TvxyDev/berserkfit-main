/* 
  Chatbot BerserkFit - Personal Trainer AI
  Integração com Google Gemini API
*/

const chatBox = document.getElementById('chat-box');
const userInput = document.getElementById('user-input');
const sendBtn = document.getElementById('send-btn');
const typingIndicator = document.getElementById('typing-indicator');

// API Key do Google Gemini
const API_KEY = 'AIzaSyCq9rrvuI2P-vf9IuQfTEId31IPUgv_frY';

// Variáveis Globais Injetadas pelo PHP (History)
let currentSessionId = (typeof PHP_LOADED_SESSION_ID !== 'undefined') ? PHP_LOADED_SESSION_ID : null;

// Histórico da conversa para manter contexto (Default)
let conversationHistory = [
    {
        role: "user",
        parts: [{
            text: `Tu és um Personal Trainer virtual profissional, inteligente, motivador e seguro chamado Oráculo. 

O teu papel no chat é:
1. Fazer perguntas sobre: Objetivo, Experiência atual, Limitações físicas, Idade, peso, altura, Dias disponíveis para treinar, Equipamentos disponíveis.

2. Criar treinos personalizados. AO CRIAR TREINOS, PRIORIZA AS SEGUINTES DIVISÕES (SPLITS) DE TREINO, a menos que o utilizador peça algo muito diferente:
   - Opção A: Peito, Tríceps e Ombro
   - Opção B: Costa e Bíceps
   - Opção C: Pernas (Completo)
   - Opção D: Braços Completo (Bíceps e Tríceps)
   - Opção E: Peito e Ombro

   Quando o utilizador pedir um plano de treino, DEVES GERAR O PLANO COMPLETO para todos os dias solicitados. NÃO RESUMAS.

   IMPORTANTE: SEMPRE que gerares um plano de treino, DEVES incluir no final da tua resposta um bloco JSON estritamente formatado contendo os dados do treino para que eu possa guardar na base de dados.
   
   O formato do JSON deve ser EXATAMENTE assim, dentro de um bloco de código triplo com a tag 'json_treino':

   \`\`\`json_treino
   {
     "treinos": [
       {
         "nome": "Treino A - Peito, Tríceps e Ombro",
         "foco": "Hipertrofia",
         "exercicios": [
           { "nome": "Supino Reto", "series": 4, "repeticoes": 12, "grupo_muscular": "Peito" },
           { "nome": "Tríceps Corda", "series": 3, "repeticoes": 15, "grupo_muscular": "Tríceps" }
         ]
       },
       {
         "nome": "Treino B - Costa e Bíceps",
         "foco": "Força",
         "exercicios": [
            ...
         ]
       }
     ]
   }
   \`\`\`

   Não te esqueças de fechar o bloco JSON. O utilizador NÃO deve ver esse JSON, eu vou processá-lo via código. Apenas fornece o texto explicativo do treino antes do JSON.

3. Dar orientações SEGURAS:
   - NUNCA prescrever dietas médicas ou tratar doenças
   - Sempre sugerir consultar um profissional se houver problemas de saúde
   - Priorizar técnicas corretas, aquecimento e postura
   - Alertar sobre sinais de overtraining

4. Ser motivacional e amigável:
   - Encorajar o utilizador com entusiasmo
   - Celebrar pequenas vitórias
   - Dar dicas de motivação
   - Usar emojis ocasionalmente 💪🔥

5. Responder SEMPRE em português de Portugal (PT-PT), usando termos como “ginásio” em vez de “academia”, “ecrã” em vez de “tela”, etc.

6. PROIBIDO usar nomes de exercícios em inglês (ex: em vez de "Curl Scott" usa "Rosca Scott", em vez de "Bench Press" usa "Supino"). Todas as tuas prescrições devem estar em português correto e técnico.

Mantenha as respostas concisas, formatadas e fáceis de ler no chat. Use markdown para formatar (negrito, listas, etc).`
        }]
    },
    {
        role: "model",
        parts: [{
            text: "Olá! Sou o Oráculo, o teu Personal Trainer virtual criado com IA. 💪<br><br>Estou aqui para desenhar o treino perfeito para ti ou tirar todas as tuas dúvidas. O que pretendes atacar hoje?"
        }]
    }
];

// Re-popula histórico se existe
if (typeof PHP_LOADED_HISTORY !== 'undefined' && PHP_LOADED_HISTORY && Array.isArray(PHP_LOADED_HISTORY)) {
    conversationHistory = PHP_LOADED_HISTORY;
}

// Função para gravar sessão na DB
async function syncChatToDB() {
    try {
        let title = "Nova Conversa";
        if (conversationHistory.length > 2) {
            title = conversationHistory[2].parts[0].text.substring(0, 30);
            if (conversationHistory[2].parts[0].text.length > 30) title += "...";
        }

        const payload = {
            id_sessao: currentSessionId,
            titulo: title,
            history: conversationHistory
        };

        const res = await fetch('save_chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (data.success && data.id_sessao) {
            currentSessionId = data.id_sessao;
            // Altera o URL para refletir a sessão gravada sem dar reload, útil caso atualize a pág
            if (window.history.replaceState) {
                window.history.replaceState(null, null, "?id=" + currentSessionId);
            }
        }
    } catch (e) {
        console.error("Erro ao sincronizar histórico: ", e);
    }
}

// Função simples para converter Markdown em HTML
function parseMarkdown(text) {
    // Remove blocos JSON de treino (ambos os formatos) para exibição limpa
    let html = text.replace(/```json_treino[\s\S]*?```/g, '');
    html = html.replace(/```json[\s\S]*?```/g, '');

    // Headers (## Titulo)
    html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
    html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
    html = html.replace(/^# (.*$)/gim, '<h1>$1</h1>');

    // Negrito (**texto**)
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    // Itálico (*texto*)
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

    // Listas não ordenadas (- item)
    // ADICIONADO: Deteção de exercícios para colocar botão de vídeo
    html = html.replace(/^\s*-\s+(.*)/gm, (match, content) => {
        // Tenta isolar o nome do exercício (antes de hífen, parênteses ou números)
        let nameMatch = content.match(/^([^:\-\(0-9]*)/);
        let exName = nameMatch ? nameMatch[0].trim() : content;

        if (exName.length > 3 && exName.length < 40) {
            return `<li>${content} <button class="btn-demo-inline" onclick="openExerciseDemo('${exName.replace(/'/g, "\\'")}')" title="Ver demonstração"><i class="fas fa-play-circle"></i></button></li>`;
        }
        return `<li>${content}</li>`;
    });

    // Envolve itens de lista em <ul> (simplificado)
    html = html.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');

    // Quebras de linha
    html = html.replace(/\n/g, '<br>');

    return html;
}

// Função para adicionar mensagens na tela
function addMessage(text, sender, workoutData = null) {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('message', sender === 'user' ? 'user-message' : 'bot-message');

    // Usa o parser de markdown
    messageDiv.innerHTML = parseMarkdown(text);

    // Se houver dados de treino, adiciona os botões
    if (workoutData && sender === 'bot') {
        const buttonsDiv = document.createElement('div');
        buttonsDiv.className = 'chat-buttons';
        buttonsDiv.style.marginTop = '15px';
        buttonsDiv.style.display = 'flex';
        buttonsDiv.style.gap = '10px';

        // Botão PDF
        const pdfBtn = document.createElement('button');
        pdfBtn.innerHTML = '<i class="fas fa-file-pdf"></i> Descarregar PDF';
        pdfBtn.className = 'btn-action btn-pdf';
        pdfBtn.style.padding = '8px 15px';
        pdfBtn.style.border = 'none';
        pdfBtn.style.borderRadius = '5px';
        pdfBtn.style.backgroundColor = '#dc3545';
        pdfBtn.style.color = 'white';
        pdfBtn.style.cursor = 'pointer';
        pdfBtn.onclick = () => generatePDF(messageDiv.innerText); // Passa texto limpo para PDF

        // Botão Exportar
        const exportBtn = document.createElement('button');
        exportBtn.innerHTML = '<i class="fas fa-save"></i> Guardar Treino';
        exportBtn.className = 'btn-action btn-export';
        exportBtn.style.padding = '8px 15px';
        exportBtn.style.border = 'none';
        exportBtn.style.borderRadius = '5px';
        exportBtn.style.backgroundColor = '#28a745';
        exportBtn.style.color = 'white';
        exportBtn.style.cursor = 'pointer';
        exportBtn.onclick = () => exportWorkout(workoutData, exportBtn);

        buttonsDiv.appendChild(pdfBtn);
        buttonsDiv.appendChild(exportBtn);
        messageDiv.appendChild(buttonsDiv);
    }

    chatBox.appendChild(messageDiv);
    chatBox.scrollTop = chatBox.scrollHeight;
}

// Função para gerar PDF
function generatePDF(content) {
    // Cria um elemento temporário para o PDF
    const element = document.createElement('div');
    element.innerHTML = `
        <div style="padding: 20px; font-family: Arial, sans-serif;">
            <h1 style="color: #1c0c3b; text-align: center;">Plano de Treino - BerserkFit AI</h1>
            <hr>
            <div style="margin-top: 20px; line-height: 1.6; white-space: pre-wrap;">
                ${content}
            </div>
            <div style="margin-top: 30px; text-align: center; font-size: 0.8em; color: #666;">
                Gerado por BerserkFit AI em ${new Date().toLocaleDateString()}
            </div>
        </div>
    `;

    const opt = {
        margin: 10,
        filename: 'meu-treino-berserkfit.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2 },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    // Usa a biblioteca html2pdf
    if (window.html2pdf) {
        html2pdf().set(opt).from(element).save();
    } else {
        alert('Erro: Biblioteca PDF não carregada.');
    }
}

// Função auxiliar — abre o modal e devolve uma Promise com a escolha do utilizador
function abrirModalGuardar() {
    return new Promise((resolve) => {
        const modal = document.getElementById('modalGuardarTreino');
        if (!modal) { resolve(null); return; }

        modal.classList.add('active');

        const btnSubstituir = document.getElementById('btn-substituir');
        const btnAdicionar  = document.getElementById('btn-adicionar');
        const btnCancelar   = document.getElementById('btn-cancelar-guardar');

        function fechar(resultado) {
            modal.classList.remove('active');
            // Limpa os listeners para evitar duplicados
            btnSubstituir.onclick = null;
            btnAdicionar.onclick  = null;
            btnCancelar.onclick   = null;
            resolve(resultado);
        }

        btnSubstituir.onclick = () => fechar('replace');
        btnAdicionar.onclick  = () => fechar('append');
        btnCancelar.onclick   = () => fechar(null);

        // Fecha ao clicar fora
        modal.onclick = (e) => { if (e.target === modal) fechar(null); };
    });
}

// Função para exportar treino para o banco de dados
async function exportWorkout(data, btnElement) {
    // Abre o modal personalizado e aguarda a escolha
    const escolha = await abrirModalGuardar();

    if (escolha === null) return; // Cancelado

    data.actionType = escolha; // 'replace' ou 'append'

    const originalText = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> A guardar...';
    btnElement.disabled = true;

    try {
        const response = await fetch('exportar_treino.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            btnElement.innerHTML = '<i class="fas fa-check"></i> Guardado!';
            btnElement.style.backgroundColor = '#198754';
            setTimeout(() => {
                btnElement.innerHTML = '<i class="fas fa-save"></i> Guardado';
            }, 2000);
        } else {
            alert('Erro ao guardar: ' + result.message);
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('Erro de ligação ao guardar treino.');
        btnElement.innerHTML = originalText;
        btnElement.disabled = false;
    }
}

// Função principal de enviar mensagem
async function sendMessage() {
    const text = userInput.value.trim();
    if (!text) return;

    // Desabilita input e botão durante o processamento
    userInput.disabled = true;
    sendBtn.disabled = true;

    // Mostra mensagem do usuário
    addMessage(text, 'user');
    userInput.value = '';

    // Mostra indicador de digitação
    typingIndicator.style.display = 'flex';
    chatBox.scrollTop = chatBox.scrollHeight;

    // Adiciona ao histórico
    conversationHistory.push({
        role: "user",
        parts: [{ text: text }]
    });

    try {
        // Chamada para API do Gemini (v1beta com gemini-flash-latest)
        const response = await fetch(
            `https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=${API_KEY}`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    contents: conversationHistory,
                    generationConfig: {
                        temperature: 0.7,
                        topK: 40,
                        topP: 0.95,
                        maxOutputTokens: 4096, // Aumentado ainda mais para garantir resposta completa
                    }
                })
            }
        );

        const data = await response.json();

        // Processa a resposta
        if (data.candidates && data.candidates[0]?.content) {
            const botReply = data.candidates[0].content.parts[0].text;

            // Verifica se há JSON de treino na resposta (procura por json_treino ou apenas json)
            let workoutData = null;

            // Tenta encontrar o bloco específico primeiro
            let jsonMatch = botReply.match(/```json_treino([\s\S]*?)```/);

            // Se não encontrar, tenta encontrar qualquer bloco json que pareça ter "treinos"
            if (!jsonMatch) {
                jsonMatch = botReply.match(/```json([\s\S]*?)```/);
            }

            if (jsonMatch && jsonMatch[1]) {
                try {
                    const potentialJson = JSON.parse(jsonMatch[1]);
                    // Validação simples para ver se tem a estrutura esperada
                    if (potentialJson.treinos) {
                        workoutData = potentialJson;
                        console.log("Dados de treino extraídos:", workoutData);
                    }
                } catch (e) {
                    console.error("Erro ao fazer parse do JSON de treino:", e);
                }
            }

            // Adiciona resposta do bot (passando os dados do treino se houver)
            addMessage(botReply, 'bot', workoutData);

            // Salva no histórico (o texto completo, incluindo o JSON, para manter contexto)
            conversationHistory.push({
                role: "model",
                parts: [{ text: botReply }]
            });

            // Sincronizar com a Base de Dados!
            await syncChatToDB();

        } else if (data.error) {
            addMessage(`❌ Erro da API: ${data.error.message}`, 'bot');
            console.error("Erro API:", data);
        } else {
            addMessage("❌ Erro: Resposta inesperada da API. Por favor, tente novamente.", 'bot');
            console.error("Resposta inesperada:", data);
        }

    } catch (error) {
        addMessage("❌ Erro de ligação. Verifica a tua internet e tenta novamente.", 'bot');
        console.error("Erro de ligação:", error);
    } finally {
        // Restaura o input
        typingIndicator.style.display = 'none';
        userInput.disabled = false;
        sendBtn.disabled = false;
        userInput.focus();
    }
}

// Event Listeners
sendBtn.addEventListener('click', () => {
    sendMessage();
});

userInput.addEventListener('keypress', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

// Funções do Modal de Demonstração (Chatbot)
async function openExerciseDemo(name) {
    const modal = document.getElementById('demoExercicioModal');
    const body = document.getElementById('demoModalBody');
    const title = document.getElementById('demoModalTitle');

    title.textContent = name;
    modal.classList.add('active');
    body.innerHTML = '<p style="text-align:center; opacity:0.6;">A procurar vídeo...</p>';

    try {
        const response = await fetch(`proxy_exercicios.php?q=${encodeURIComponent(name)}`);
        const data = await response.json();

        if (data && data.length > 0) {
            const ex = data[0]; // Pega o primeiro resultado
            body.innerHTML = `
                <img src="${ex.gifUrl}" class="demo-gif" alt="${ex.name}">
                <div style="background:rgba(255,255,255,0.05); padding:15px; border-radius:8px;">
                    <p style="margin-bottom:10px;"><strong>Equipamento:</strong> ${ex.equipment}</p>
                    <p><strong>Músculo Alvo:</strong> ${ex.target}</p>
                </div>
            `;
        } else {
            body.innerHTML = '<p style="text-align:center;">Não encontrámos um vídeo específico para este exercício.</p>';
        }
    } catch (err) {
        body.innerHTML = '<p style="text-align:center; color:#f87171;">Erro ao ligar à biblioteca.</p>';
    }
}

function closeDemoModal() {
    document.getElementById('demoExercicioModal').classList.remove('active');
}

// Auto-focus no input quando a página carrega e Renderização Inicial do Histórico!
window.addEventListener('load', () => {
    userInput.focus();

    // Se carregámos um histórico da BD, limpar a msg default do HTML e desenhar tudo!
    if (typeof PHP_LOADED_HISTORY !== 'undefined' && PHP_LOADED_HISTORY && Array.isArray(PHP_LOADED_HISTORY)) {
        chatBox.innerHTML = '';
        for (let i = 1; i < conversationHistory.length; i++) {
            let role = conversationHistory[i].role === 'user' ? 'user' : 'bot';
            // Validar se tem dados de treino para desenhar pdf, extraindo
            let text = conversationHistory[i].parts[0].text;
            let workoutData = null;
            let jsonMatch = text.match(/```json_treino([\s\S]*?)```/) || text.match(/```json([\s\S]*?)```/);
            if (jsonMatch && role === 'bot') {
                try {
                    const potentialJson = JSON.parse(jsonMatch[1]);
                    if (potentialJson.treinos) workoutData = potentialJson;
                } catch (e) { }
            }
            addMessage(text, role, workoutData);
        }
    }
});
