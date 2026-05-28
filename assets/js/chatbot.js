/*
  Chatbot da Sete Jr
  - Mostra mensagens na tela
  - Envia mensagens para backend/chatbot_response.php
  - Encerra conversa em backend/close_conversation.php
*/

const defaultMessages = {
    manual: "Olá! Preencha nome, telefone e e-mail acima para iniciar. Depois escolha: 1 - Tecnologia, 2 - Finanças, 3 - Empreendedorismo, 4 - Falar com a equipe.",
    ai: "Olá! Preencha nome, telefone e e-mail acima para iniciar. Depois pergunte sobre tecnologia, finanças, empreendedorismo ou contato com a equipe."
};

function getCurrentMode() {
    return chatbotMode && chatbotMode.value === "ai" ? "ai" : "manual";
}

function getConversationStorageKey(mode = getCurrentMode()) {
    return `sete_chat_conversation_id_${mode}`;
}

function getStoredConversationId(mode = getCurrentMode()) {
    return Number(localStorage.getItem(getConversationStorageKey(mode)) || 0);
}

const chatbotForm = document.getElementById("chatbot-form");
const chatbotInput = document.getElementById("chatbot-input");
const chatbotMessages = document.getElementById("chatbot-messages");
const chatbotMode = document.getElementById("chatbot-mode");
const chatbotEnd = document.getElementById("chatbot-end");
const chatbotClear = document.getElementById("chatbot-clear");
const visitorName = document.getElementById("visitor-name");
const visitorPhone = document.getElementById("visitor-phone");
const visitorEmail = document.getElementById("visitor-email");

let conversationId = getStoredConversationId();
let manualUnknownCount = 0;

function hasVisitorInfo() {
    return Boolean(
        visitorName && visitorName.value.trim()
        && visitorPhone && visitorPhone.value.trim()
        && visitorEmail && visitorEmail.value.trim()
    );
}

function focusFirstMissingVisitorField() {
    [visitorName, visitorPhone, visitorEmail].find((field) => field && !field.value.trim())?.focus();
}

function addChatMessage(text, sender = "bot") {
    if (!chatbotMessages) return;

    const message = document.createElement("div");
    message.className = `chat-message ${sender}`;
    message.textContent = text;
    chatbotMessages.appendChild(message);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

function resetChatMessages(mode = getCurrentMode()) {
    if (!chatbotMessages) return;

    chatbotMessages.innerHTML = "";
    addChatMessage(defaultMessages[mode] || defaultMessages.manual, mode === "ai" ? "ai" : "bot");
}

function addWhatsappRedirectMessage() {
    const whatsappUrl = "https://wa.me/5571984825330?text=Ol%C3%A1%2C%20vim%20pelo%20site%20e%20preciso%20de%20atendimento";
    addChatMessage(`Não consegui entender bem. Fale direto com a equipe pelo WhatsApp: ${whatsappUrl}`, "bot");
}

async function sendChatMessage(messageText) {
    const payload = {
        conversation_id: conversationId,
        message: messageText,
        mode: getCurrentMode(),
        visitor_name: visitorName ? visitorName.value : "",
        visitor_phone: visitorPhone ? visitorPhone.value : "",
        visitor_email: visitorEmail ? visitorEmail.value : ""
    };

    const response = await fetch("backend/chatbot_response.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(payload)
    });

    const data = await response.json();

    if (!response.ok || data.error) {
        throw new Error(data.error || "Erro ao comunicar com o chatbot.");
    }

    return data;
}

async function closeConversationById(id) {
    if (!id) return;

    await fetch("backend/close_conversation.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ conversation_id: id })
    });
}

if (chatbotForm) {
    chatbotForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const messageText = chatbotInput.value.trim();
        if (!messageText) return;

        if (!conversationId && !hasVisitorInfo()) {
            addChatMessage("Antes de continuar, preencha seu nome, telefone e e-mail nos campos acima.", "bot");
            focusFirstMissingVisitorField();
            return;
        }

        addChatMessage(messageText, "user");
        chatbotInput.value = "";
        chatbotInput.disabled = true;

        try {
            const data = await sendChatMessage(messageText);

            if (data.conversation_id) {
                conversationId = Number(data.conversation_id);
                localStorage.setItem(getConversationStorageKey(), String(conversationId));
            }

            const reply = data.reply || "Não consegui responder agora. Tente novamente em instantes.";
            addChatMessage(reply, data.reply_sender || data.sender || "bot");

            if (getCurrentMode() === "manual") {
                if (data.not_understood) {
                    manualUnknownCount += 1;

                    if (manualUnknownCount >= 3) {
                        addWhatsappRedirectMessage();
                        manualUnknownCount = 0;
                    }
                } else {
                    manualUnknownCount = 0;
                }
            }
        } catch (error) {
            addChatMessage("Não foi possível conectar ao atendimento. Verifique se o PHP e o MySQL estão configurados.", "bot");
        } finally {
            chatbotInput.disabled = false;
            chatbotInput.focus();
        }
    });
}

if (chatbotEnd) {
    chatbotEnd.addEventListener("click", async () => {
        if (!conversationId) {
            addChatMessage("A conversa ainda não foi iniciada.", "bot");
            return;
        }

        try {
            await closeConversationById(conversationId);
        } finally {
            localStorage.removeItem(getConversationStorageKey());
            conversationId = 0;
            manualUnknownCount = 0;
            resetChatMessages();
            addChatMessage("Conversa encerrada. Quando quiser, é só enviar uma nova mensagem.", "bot");
        }
    });
}

if (chatbotClear) {
    chatbotClear.addEventListener("click", async () => {
        const manualConversationId = getStoredConversationId("manual");
        const aiConversationId = getStoredConversationId("ai");

        try {
            await Promise.all([
                closeConversationById(manualConversationId),
                closeConversationById(aiConversationId)
            ]);
        } finally {
            localStorage.removeItem(getConversationStorageKey("manual"));
            localStorage.removeItem(getConversationStorageKey("ai"));
            conversationId = 0;
            manualUnknownCount = 0;
            resetChatMessages();
            addChatMessage("Conversa limpa nos modos Manual e IA.", "bot");
            if (chatbotInput) {
                chatbotInput.focus();
            }
        }
    });
}

if (chatbotMode) {
    chatbotMode.addEventListener("change", async () => {
        conversationId = getStoredConversationId();
        manualUnknownCount = 0;
        resetChatMessages();
    });
}

resetChatMessages();
