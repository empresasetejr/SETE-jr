/*
  Chatbot da Sete Jr
  - Mostra mensagens na tela
  - Envia mensagens para backend/chatbot_response.php
  - Carrega histórico em backend/get_messages.php
  - Encerra conversa em backend/close_conversation.php
*/

let conversationId = Number(localStorage.getItem("sete_chat_conversation_id") || 0);

const chatbotForm = document.getElementById("chatbot-form");
const chatbotInput = document.getElementById("chatbot-input");
const chatbotMessages = document.getElementById("chatbot-messages");
const chatbotMode = document.getElementById("chatbot-mode");
const chatbotEnd = document.getElementById("chatbot-end");
const visitorName = document.getElementById("visitor-name");
const visitorPhone = document.getElementById("visitor-phone");
const visitorEmail = document.getElementById("visitor-email");

function addChatMessage(text, sender = "bot") {
    if (!chatbotMessages) return;

    const message = document.createElement("div");
    message.className = `chat-message ${sender}`;
    message.textContent = text;
    chatbotMessages.appendChild(message);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
}

async function loadChatHistory() {
    if (!conversationId || !chatbotMessages) return;

    try {
        const response = await fetch(`backend/get_messages.php?conversation_id=${conversationId}`);
        const data = await response.json();

        if (!Array.isArray(data.messages) || data.messages.length === 0) return;

        chatbotMessages.innerHTML = "";
        data.messages.forEach((message) => addChatMessage(message.message, message.sender));
    } catch (error) {
        addChatMessage("Não foi possível carregar o histórico da conversa.", "bot");
    }
}

async function sendChatMessage(messageText) {
    const payload = {
        conversation_id: conversationId,
        message: messageText,
        mode: chatbotMode ? chatbotMode.value : "manual",
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

if (chatbotForm) {
    chatbotForm.addEventListener("submit", async (event) => {
        event.preventDefault();

        const messageText = chatbotInput.value.trim();
        if (!messageText) return;

        addChatMessage(messageText, "user");
        chatbotInput.value = "";
        chatbotInput.disabled = true;

        try {
            const data = await sendChatMessage(messageText);

            if (data.conversation_id) {
                conversationId = Number(data.conversation_id);
                localStorage.setItem("sete_chat_conversation_id", String(conversationId));
            }

            addChatMessage(
                data.reply || "Não consegui responder agora. Tente novamente em instantes.",
                data.reply_sender || data.sender || "bot"
            );
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
            await fetch("backend/close_conversation.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ conversation_id: conversationId })
            });
        } finally {
            localStorage.removeItem("sete_chat_conversation_id");
            conversationId = 0;
            addChatMessage("Conversa encerrada. Quando quiser, é só enviar uma nova mensagem.", "bot");
        }
    });
}

loadChatHistory();

