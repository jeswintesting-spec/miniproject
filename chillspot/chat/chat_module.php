<?php
require_once '../db.php';

// ✅ Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Redirect if not logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['name'])) {
    header("Location: ../login.html");
    exit();
}

// ✅ Update current user's last_active timestamp
$user_id = $_SESSION['id'];
$conn->query("UPDATE users SET last_active = NOW() WHERE id = $user_id");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ChillSpot Chat</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
body {
    font-family: 'Inter', sans-serif;
}
#chatBox {
    overflow-y: auto;
    flex: 1;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    background: linear-gradient(to bottom right, #f8fafc, #eef2ff);
    scroll-behavior: smooth;
}
.user-item:hover {
    background-color: #e0f2fe;
    cursor: pointer;
}
.user-item.active {
    background-color: #ccfbf1;
    border-left: 4px solid #14b8a6;
}
.message {
    max-width: 70%;
    padding: 0.6rem 1rem;
    border-radius: 1rem;
    word-wrap: break-word;
    font-size: 0.95rem;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
.message.sent {
    align-self: flex-end;
    background: #14b8a6;
    color: white;
    border-bottom-right-radius: 0.2rem;
}
.message.received {
    align-self: flex-start;
    background: white;
    border-bottom-left-radius: 0.2rem;
}
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-thumb {
    background-color: #94a3b8;
    border-radius: 3px;
}
</style>
</head>

<body class="bg-gray-100">

<div class="flex h-screen">
    <!-- Sidebar -->
    <div class="w-1/4 bg-white border-r flex flex-col shadow-md">
        <div class="p-4 border-b flex justify-between items-center bg-gradient-to-r from-emerald-500 to-teal-500 text-white">
            <div>
                <h2 class="text-lg font-semibold">👋 Hi, <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
                <div class="flex items-center space-x-1 text-sm opacity-90">
                    <div class="h-2 w-2 bg-green-400 rounded-full animate-pulse"></div>
                    <span>Online</span>
                </div>
            </div>
            <a href="../backkk.html" class="text-sm bg-white/20 px-3 py-1 rounded-md hover:bg-white/30 transition">Home</a>
        </div>
        <div id="userList" class="divide-y overflow-y-auto flex-1 p-2 bg-gray-50"></div>
    </div>

    <!-- Chat Area -->
    <div class="flex-1 flex flex-col relative">
        <div id="chatHeader" class="p-4 border-b bg-white text-lg font-semibold shadow-sm sticky top-0 z-10 text-gray-800 flex items-center space-x-2">
            <span class="text-gray-500">💬</span>
            <span>Select a user to start chatting</span>
        </div>
        <div id="chatBox" class="relative"></div>
        <div class="p-4 bg-white border-t flex items-center shadow-inner">
            <input id="messageInput" type="text" placeholder="Type a message..." 
                   class="flex-1 border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500 shadow-sm transition disabled:bg-gray-100"
                   disabled>
            <button id="sendBtn" 
                    class="ml-3 bg-emerald-600 text-white px-5 py-2 rounded-full hover:bg-emerald-700 transition duration-200 shadow-md disabled:opacity-50"
                    disabled>Send</button>
        </div>
    </div>
</div>

<script>
let currentReceiverId = null;
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
const chatBox = document.getElementById('chatBox');

// ✅ Fetch helper
async function fetchText(url, options={}) {
    try {
        const r = await fetch(url, options);
        if (!r.ok) throw new Error(r.status);
        return await r.text();
    } catch(e) {
        console.error(e);
        return null;
    }
}

// ✅ Load users
async function loadUsers() {
    const html = await fetchText('get_users.php');
    document.getElementById('userList').innerHTML = html || '<div class="p-2 text-red-500">Failed to load users.</div>';
}

// ✅ Open chat
function openChat(id, name) {
    currentReceiverId = id;

    document.querySelectorAll('.user-item').forEach(u => u.classList.remove('active'));
    const activeUser = document.querySelector(`.user-item[data-id="${id}"]`);
    if (activeUser) activeUser.classList.add('active');

    // ✅ Get actual status
    const status = activeUser?.getAttribute('data-status') || 'Offline';

    document.getElementById('chatHeader').innerHTML = `
        <span class="text-emerald-600 font-bold">${name}</span>
        <span class="text-sm text-gray-500 ml-2">• ${status}</span>
    `;

    messageInput.disabled = false;
    sendBtn.disabled = false;
    messageInput.focus();
    loadMessages();
}


// ✅ Load messages
async function loadMessages() {
    if (!currentReceiverId) return;

    const html = await fetchText(`get_messages.php?receiver_id=${currentReceiverId}`);

    // ✅ If html is empty, show friendly placeholder for new chat
    chatBox.innerHTML = html ? html : '<div class="text-gray-400 text-sm italic text-center py-4">No messages yet. Start the conversation!</div>';

    chatBox.scrollTop = chatBox.scrollHeight;
}


// ✅ Send message
async function sendMessage() {
    const msg = messageInput.value.trim();
    if (!msg || !currentReceiverId) return;

    const params = new URLSearchParams({ receiver_id: currentReceiverId, message: msg });
    const res = await fetch('send_message.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params.toString()
    }).then(r => r.json()).catch(console.error);

    if (res?.status === 'success') {
        messageInput.value = '';
        loadMessages();
    }
}

sendBtn.addEventListener('click', sendMessage);
messageInput.addEventListener('keypress', e => {
    if (e.key === 'Enter') {
        e.preventDefault();
        sendMessage();
    }
});

// ✅ Initial load and refresh intervals
window.addEventListener('load', () => {
    loadUsers();
    setInterval(loadUsers, 10000);       // refresh users
    setInterval(loadMessages, 3000);     // refresh messages

    // ✅ Update only logged-in user's last_active
    setInterval(() => { fetch('update_activity.php'); }, 30000);
});
</script>
</body>
</html>