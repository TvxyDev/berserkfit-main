/* 
  Chatbot BerserkFit - Personal Trainer AI
  Integração com Google Gemini API
*/

const chatBox = document.getElementById('chat-box');
const userInput = document.getElementById('user-input');
const sendBtn = document.getElementById('send-btn');
const typingIndicator = document.getElementById('typing-indicator');

// API Key do Google Gemini
const API_KEY = 'AIzaSyD3f9wqJc6NxZ4QNdKO_vxZVPt5VEk-lCI';

// Histórico da conversa para manter contexto
let conversationHistory = [
    {
        role: "user",
        parts: [{
            text: `Tu és um Personal Trainer virtual profissional, motivador e seguro chamado BerserkFit AI. 

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

Mantenha as respostas concisas, formatadas e fáceis de ler no chat. Use markdown para formatar (negrito, listas, etc).`
        }]
    },
    {
        role: "model",
        parts: [{
            text: "Entendido! Sou o BerserkFit AI, o teu Personal Trainer virtual. Estou aqui para te ajudar a alcançar os teus objetivos com treinos personalizados e muita motivação! 💪🔥"
        }]
    }
];

// Função simples para converter Markdown em HTML
function parseMarkdown(text) {
    // Remove o bloco JSON se existir para exibição
    let html = text.replace(/```json_treino[\s\S]*?```/g, '');

    // Headers (## Titulo)
    html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
    html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
    html = html.replace(/^# (.*$)/gim, '<h1>$1</h1>');

    // Negrito (**texto**)
    html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

    // Itálico (*texto*)
    html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');

    // Listas não ordenadas (- item)
    html = html.replace(/^\s*-\s+(.*)/gm, '<li>$1</li>');
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

// Função para exportar treino para o banco de dados
async function exportWorkout(data, btnElement) {
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

// Auto-focus no input quando a página carrega
window.addEventListener('load', () => {
    userInput.focus();
});
