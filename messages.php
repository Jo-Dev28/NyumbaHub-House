<?php
require_once 'includes/config.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$conversation_id = isset($_GET['conversation']) ? (int)$_GET['conversation'] : 0;
$property_id = isset($_GET['property']) ? (int)$_GET['property'] : 0;
$other_user_id = isset($_GET['user']) ? (int)$_GET['user'] : 0;

// Get all conversations for the user
$stmt = $pdo->prepare("SELECT DISTINCT 
                        CASE 
                            WHEN m.sender_id = ? THEN m.receiver_id
                            ELSE m.sender_id
                        END as other_user_id,
                        u.full_name, u.profile_image,
                        MAX(m.created_at) as last_message,
                        (SELECT message FROM messages WHERE 
                            (sender_id = ? AND receiver_id = other_user_id) OR 
                            (sender_id = other_user_id AND receiver_id = ?)
                         ORDER BY created_at DESC LIMIT 1) as last_message_text,
                        (SELECT COUNT(*) FROM messages WHERE 
                            receiver_id = ? AND sender_id = other_user_id AND is_read = 0) as unread_count
                       FROM messages m
                       JOIN users u ON (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) = u.id
                       WHERE m.sender_id = ? OR m.receiver_id = ?
                       GROUP BY other_user_id
                       ORDER BY last_message DESC");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll();

// Get messages for specific conversation
$messages = [];
if($conversation_id > 0) {
    $stmt = $pdo->prepare("SELECT m.*, u.full_name, u.profile_image 
                           FROM messages m
                           JOIN users u ON m.sender_id = u.id
                           WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
                           ORDER BY m.created_at ASC");
    $stmt->execute([$user_id, $conversation_id, $conversation_id, $user_id]);
    $messages = $stmt->fetchAll();
    
    // Mark messages as read
    $stmt = $pdo->prepare("UPDATE messages SET is_read = 1, read_at = NOW() 
                           WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
    $stmt->execute([$user_id, $conversation_id]);
} elseif($other_user_id > 0) {
    // Start new conversation
    $conversation_id = $other_user_id;
    $stmt = $pdo->prepare("SELECT m.*, u.full_name, u.profile_image 
                           FROM messages m
                           JOIN users u ON m.sender_id = u.id
                           WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
                           ORDER BY m.created_at ASC");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    $messages = $stmt->fetchAll();
}

$page_title = 'Messages';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <?php include 'includes/user-sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4">
                    <h4 class="mb-0"><i class="fas fa-comment-dots text-primary"></i> Messages</h4>
                </div>
                <div class="card-body p-0">
                    <div class="row g-0">
                        <!-- Conversations List -->
                        <div class="col-md-4 border-end">
                            <div class="conversations-list" style="height: 500px; overflow-y: auto;">
                                <?php if(count($conversations) > 0): ?>
                                    <?php foreach($conversations as $conv): ?>
                                    <a href="?conversation=<?php echo $conv['other_user_id']; ?>" 
                                       class="conversation-item d-flex align-items-center p-3 text-decoration-none border-bottom <?php echo $conversation_id == $conv['other_user_id'] ? 'active' : ''; ?>">
                                        <img src="<?php echo SITE_URL . 'uploads/profiles/' . $conv['profile_image']; ?>" 
                                             class="rounded-circle me-3" width="50" height="50" alt="Avatar">
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="mb-0"><?php echo htmlspecialchars($conv['full_name']); ?></h6>
                                                <small class="text-muted"><?php echo timeAgo($conv['last_message']); ?></small>
                                            </div>
                                            <p class="small text-muted mb-0"><?php echo htmlspecialchars(substr($conv['last_message_text'], 0, 40)); ?></p>
                                        </div>
                                        <?php if($conv['unread_count'] > 0): ?>
                                            <span class="badge bg-primary rounded-pill"><?php echo $conv['unread_count']; ?></span>
                                        <?php endif; ?>
                                    </a>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p>No conversations yet.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Chat Area -->
                        <div class="col-md-8">
                            <?php if($conversation_id > 0 && count($messages) > 0): ?>
                                <div class="chat-header p-3 border-bottom">
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo SITE_URL . 'uploads/profiles/' . $messages[0]['profile_image']; ?>" 
                                             class="rounded-circle me-3" width="40" height="40" alt="Avatar">
                                        <div>
                                            <h6 class="mb-0"><?php echo htmlspecialchars($messages[0]['full_name']); ?></h6>
                                            <small class="text-muted">Online</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="chat-messages p-3" style="height: 400px; overflow-y: auto;">
                                    <?php foreach($messages as $message): ?>
                                        <div class="message mb-3 <?php echo $message['sender_id'] == $user_id ? 'text-end' : ''; ?>">
                                            <div class="d-inline-block <?php echo $message['sender_id'] == $user_id ? 'bg-primary text-white' : 'bg-light'; ?> rounded-3 p-2 px-3" 
                                                 style="max-width: 70%;">
                                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                            </div>
                                            <br>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($message['created_at'])); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="chat-input p-3 border-top">
                                    <form id="messageForm" class="d-flex gap-2">
                                        <input type="hidden" name="receiver_id" value="<?php echo $conversation_id; ?>">
                                        <?php if($property_id): ?>
                                            <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                                        <?php endif; ?>
                                        <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php elseif($conversation_id > 0 && count($messages) == 0): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-comment fa-3x text-muted mb-3"></i>
                                    <h5>Start a conversation</h5>
                                    <p class="text-muted">Send a message to start chatting</p>
                                    <div class="chat-input p-3">
                                        <form id="messageForm" class="d-flex gap-2">
                                            <input type="hidden" name="receiver_id" value="<?php echo $conversation_id; ?>">
                                            <?php if($property_id): ?>
                                                <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                                            <?php endif; ?>
                                            <input type="text" name="message" class="form-control" placeholder="Type your message..." required>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                                    <h5>Your Messages</h5>
                                    <p class="text-muted">Select a conversation to start chatting</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.conversation-item {
    transition: all 0.3s ease;
}
.conversation-item:hover {
    background-color: #f8f9fa;
}
.conversation-item.active {
    background-color: #e7f1ff;
}
.chat-messages {
    background-color: #f8f9fa;
}
.message {
    animation: fadeIn 0.3s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
$(document).ready(function() {
    // Scroll to bottom of messages
    $('.chat-messages').scrollTop($('.chat-messages')[0].scrollHeight);
    
    // Send message
    $('#messageForm').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        
        $.ajax({
            url: 'ajax/send-message.php',
            method: 'POST',
            data: formData,
            success: function(response) {
                var data = JSON.parse(response);
                if(data.success) {
                    location.reload();
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            }
        });
    });
    
    // Auto-refresh messages every 10 seconds
    if(<?php echo $conversation_id > 0 ? 'true' : 'false'; ?>) {
        setInterval(function() {
            $.ajax({
                url: 'ajax/get-messages.php',
                method: 'POST',
                data: {conversation_id: <?php echo $conversation_id; ?>},
                success: function(response) {
                    var messages = JSON.parse(response);
                    if(messages.length > 0) {
                        // Update messages without reloading
                        location.reload();
                    }
                }
            });
        }, 10000);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>