<?php
require_once '../db.php';

// ===============================
// 1. SESSION & AUTH CHECK
// ===============================
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['id'])) {
    header("Location: ../login.html");
    exit();
}

$user_id = $_SESSION['id'];
$user_name = !empty($_SESSION['name']) ? $_SESSION['name'] : 'User';

// ===============================
// 2. PROFILE IMAGE LOGIC
// ===============================
$default_image = '../uploads/default.png';
$image_path = $default_image;

$stmt = $conn->prepare("SELECT image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($db_image);
if ($stmt->fetch() && !empty($db_image)) {
    $db_image = ltrim($db_image, '/'); // remove leading slash
    $full_path = "../" . $db_image;
    if (file_exists($full_path)) $image_path = $full_path;
}
$stmt->close();

$user_image_path = $image_path . '?v=' . time(); // cache-busting

// ===============================
// 3. UPDATE ACTIVITY
// ===============================
$stmt = $conn->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->close();

// ===============================
// 4. DETERMINE USER ONLINE STATUS
// ===============================
$user_status = 'Offline';
$timeout_seconds = 120; // 2 minutes

$stmt = $conn->prepare("SELECT last_active FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($last_active);
if ($stmt->fetch() && !empty($last_active)) {
    $last_time = strtotime($last_active);
    if ($last_time !== false && (time() - $last_time) <= $timeout_seconds) {
        $user_status = 'Online';
    }
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ChillSpot Chat</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Inter', sans-serif; }
#chatBox { overflow-y: auto; flex:1; padding:1rem; display:flex; flex-direction:column; gap:0.75rem;
           background: linear-gradient(to bottom right, #f8fafc, #eef2ff); scroll-behavior: smooth; }
.user-item:hover { background-color: #e0f2fe; cursor: pointer; }
.user-item.active { background-color: #ccfbf1; border-left: 4px solid #14b8a6; }
.message { max-width:70%; padding:0.6rem 1rem; border-radius:1rem; word-wrap: break-word; font-size:0.95rem;
           box-shadow:0 1px 2px rgba(0,0,0,0.1); }
.message.sent { align-self:flex-end; background:#14b8a6; color:white; border-bottom-right-radius:0.2rem; }
.message.received { align-self:flex-start; background:white; border-bottom-left-radius:0.2rem; }
::-webkit-scrollbar { width:6px; }
::-webkit-scrollbar-thumb { background-color:#94a3b8; border-radius:3px; }
</style>
</head>
<body class="bg-gray-100">
<div class="flex h-screen">

    <!-- Sidebar -->
    <div class="w-1/4 bg-white border-r flex flex-col shadow-md">
        <div class="p-4 border-b flex justify-between items-center 
                    bg-gradient-to-r from-emerald-500 to-teal-500 text-white">
            <div class="flex items-center space-x-3">
                <img src="<?php echo htmlspecialchars($user_image_path); ?>" 
                     alt="Profile Picture" 
                     class="w-10 h-10 rounded-full object-cover border-2 border-white shadow-sm cursor-pointer" 
                     onclick="openModal('<?php echo $user_image_path; ?>')">
                <div class="flex flex-col leading-tight">
                    <span class="text-lg font-semibold">👋 Hi, <?php echo htmlspecialchars($user_name); ?>!</span>
                    <div class="flex items-center space-x-1 text-sm opacity-90">
                        <div class="h-2 w-2 rounded-full <?php echo $user_status==='Online'?'bg-green-400 animate-pulse':'bg-gray-400'; ?>"></div>
                        <span><?php echo $user_status; ?></span>
                    </div>
                </div>
            </div>
            <a href="../backkk.html" class="text-sm bg-white/20 px-3 py-1 rounded-md hover:bg-white/30 transition">Home</a>
        </div>
        <div id="userList" class="divide-y overflow-y-auto flex-1 p-2 bg-gray-50"></div>
    </div>

    <!-- Chat area -->
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

<!-- Full Image Modal -->
<div id="imageModal" class="hidden fixed inset-0 bg-black/80 flex items-center justify-center z-50">
  <div class="relative">
    <img id="modalImage" src="" class="max-h-[90vh] max-w-[90vw] rounded-2xl shadow-2xl border-4 border-white" alt="Full Image">
    <button id="closeModal" class="absolute -top-4 -right-4 bg-white text-gray-800 rounded-full w-8 h-8 text-lg font-bold shadow-md hover:bg-gray-200">×</button>
  </div>
</div>

<script>
let currentReceiverId = null;
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
const chatBox = document.getElementById('chatBox');

async function fetchText(url, options={}) {
    try { const res=await fetch(url,options); if(!res.ok)throw new Error(res.status); return await res.text(); }
    catch(e){ console.error('Fetch error', e); return null; }
}

async function loadUsers() {
    const html = await fetchText('get_users.php');
    document.getElementById('userList').innerHTML = html || '<div class="p-4 text-center text-red-500">Failed to load users list.</div>';
}

function openChat(userId, userName, userStatus) {
    currentReceiverId = userId;
    document.querySelectorAll('.user-item').forEach(el=>el.classList.remove('active'));
    const activeEl = document.querySelector(`.user-item[data-id="${userId}"]`);
    if(activeEl) activeEl.classList.add('active');

    document.getElementById('chatHeader').innerHTML = `
    <span class="text-emerald-600 font-bold">${userName}</span>`;

    
    messageInput.disabled = false;
    sendBtn.disabled = false;
    messageInput.placeholder = `Message ${userName}...`;
    messageInput.focus();
    loadMessages();
}

async function loadMessages() {
    if(!currentReceiverId) return;
    const html = await fetchText(`get_messages.php?receiver_id=${currentReceiverId}`);
    chatBox.innerHTML = html || '<div class="text-gray-400 text-sm italic text-center py-4">No messages yet.</div>';
    chatBox.scrollTop = chatBox.scrollHeight;
}

async function sendMessage() {
    const message = messageInput.value.trim();
    if(!message || !currentReceiverId) return;
    const formData = new URLSearchParams({receiver_id:currentReceiverId,message});
    try{
        const res = await fetch('send_message.php',{method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:formData});
        const r = await res.json();
        if(r?.status==='success'){ messageInput.value=''; loadMessages(); } else console.error(r?.message);
    } catch(e){ console.error(e); }
}

sendBtn.addEventListener('click',sendMessage);
messageInput.addEventListener('keypress', e=>{if(e.key==='Enter'&&!e.shiftKey){ e.preventDefault(); sendMessage(); }});

window.addEventListener('load',()=>{
    loadUsers();
    setInterval(loadUsers,10000);
    setInterval(loadMessages,3000);
    setInterval(()=>fetch('update_activity.php'),30000);
});

// Image modal functions
function openModal(src){
    document.getElementById('modalImage').src = src.split('?')[0];
    document.getElementById('imageModal').classList.remove('hidden');
}
document.getElementById("closeModal").addEventListener("click", ()=>document.getElementById("imageModal").classList.add("hidden"));
document.getElementById("imageModal").addEventListener("click", e=>{if(e.target.id==="imageModal") e.currentTarget.classList.add("hidden");});
</script>
</body>
</html>
