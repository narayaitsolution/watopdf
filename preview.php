<?php
session_start();

if (!isset($_SESSION['chat_data'])) {
    header('Location: index.php');
    exit;
}

$messages = $_SESSION['chat_data'];
$uploadDir = $_SESSION['upload_dir'];

// Determine the "owner" of the chat (usually the one who is not the sender of the first message, or we can just alternate colors based on name)
// For simplicity, let's assume the first person to speak is "received" and the second is "sent" if it's a 1-on-1.
// Or better: assign colors/sides based on names.
$senders = [];
foreach ($messages as $msg) {
    if ($msg['type'] === 'message') {
        $senders[$msg['sender']] = true;
    }
}
$senderNames = array_keys($senders);
$myUser = isset($senderNames[1]) ? $senderNames[1] : (isset($senderNames[0]) ? $senderNames[0] : 'Me'); // Guessing logic

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Preview</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background-color: #d1d7db; display: block;">

    <div class="chat-container">
        <div class="chat-header">
            <div style="width: 40px; height: 40px; background: #ccc; border-radius: 50%; margin-right: 10px; display: flex; align-items: center; justify-content: center; color: #555;">
                <!-- Avatar placeholder -->
                <svg viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 2.5a5.5 5.5 0 0 1 3.096 10.047 6.001 6.001 0 0 1-5.192 9.113H9.9a6.001 6.001 0 0 1-5.192-9.113A5.5 5.5 0 0 1 12 2.5zM12 16.5c1.464 0 2.755.71 3.58 1.814A4.496 4.496 0 0 0 12 17.5a4.496 4.496 0 0 0-3.58.814A4.496 4.496 0 0 1 12 16.5z"/></svg>
            </div>
            <div>
                <h2>WhatsApp Chat Export</h2>
                <p style="font-size: 0.8rem; opacity: 0.8; margin: 0;">Tap here for contact info</p>
            </div>
        </div>

        <div class="chat-body">
            <?php 
            $lastDate = '';
            foreach ($messages as $msg): 
                // Date Divider
                // Robust date extraction
                $cleanDate = str_replace(['[', ']'], '', isset($msg['date']) ? $msg['date'] : '');
                // Split by comma or space to get the date part
                $dateParts = preg_split('/[, ]+/', $cleanDate);
                $msgDate = isset($dateParts[0]) ? $dateParts[0] : '';

                if ($msgDate && $msgDate !== $lastDate) {
                    echo '<div class="date-divider">' . htmlspecialchars($msgDate) . '</div>';
                    $lastDate = $msgDate;
                }

                if ($msg['type'] === 'system'): ?>
                    <div class="system-message">
                        <?php echo htmlspecialchars($msg['message']); ?>
                    </div>
                <?php else: 
                    $isSent = ($msg['sender'] === $myUser); // Simple logic, might need adjustment
                    $class = $isSent ? 'sent' : 'received';
                ?>
                    <div class="message <?php echo $class; ?>">
                        <?php if (!$isSent): ?>
                            <span class="message-sender"><?php echo htmlspecialchars($msg['sender']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (isset($msg['is_media']) && $msg['is_media'] && isset($msg['media_file'])): ?>
                            <div class="media-attachment">
                                <?php 
                                $ext = strtolower(pathinfo($msg['media_file'], PATHINFO_EXTENSION));
                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                    <img src="<?php echo $uploadDir . $msg['media_file']; ?>" alt="Image">
                                <?php elseif (in_array($ext, ['mp4', '3gp', 'mov'])): ?>
                                    <div style="background: #eee; padding: 10px; border-radius: 5px; text-align: center;">
                                        🎥 Video: <?php echo htmlspecialchars($msg['media_file']); ?>
                                    </div>
                                <?php elseif (in_array($ext, ['opus', 'mp3', 'aac'])): ?>
                                    <div style="background: #eee; padding: 10px; border-radius: 5px; text-align: center;">
                                        🎤 Audio: <?php echo htmlspecialchars($msg['media_file']); ?>
                                    </div>
                                <?php else: ?>
                                    <div style="background: #eee; padding: 10px; border-radius: 5px; text-align: center;">
                                        📄 File: <?php echo htmlspecialchars($msg['media_file']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($msg['is_location']) && $msg['is_location']): ?>
                            <div class="location-attachment" style="margin-bottom: 5px;">
                                <div style="width: 100%; height: 200px; background: #e9e9eb; border-radius: 6px; overflow: hidden; position: relative;">
                                    <?php if (isset($msg['location_lat']) && isset($msg['location_lng'])): ?>
                                        <iframe 
                                            width="100%" 
                                            height="100%" 
                                            frameborder="0" 
                                            scrolling="no" 
                                            marginheight="0" 
                                            marginwidth="0" 
                                            src="https://maps.google.com/maps?q=<?php echo $msg['location_lat']; ?>,<?php echo $msg['location_lng']; ?>&hl=id&z=15&output=embed">
                                        </iframe>
                                    <?php else: ?>
                                        <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #777;">
                                            <p>Map not available (No coordinates found)</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo htmlspecialchars(strip_tags($msg['message'])); ?>" target="_blank" style="display: block; margin-top: 5px; color: #007bff; text-decoration: none; font-size: 12px;">View on Google Maps</a>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($msg['is_contact']) && $msg['is_contact']): ?>
                            <div class="contact-card">
                                <div class="contact-header">
                                    <div class="contact-avatar">
                                        <svg viewBox="0 0 24 24"><path d="M12 2.5a5.5 5.5 0 0 1 3.096 10.047 6.001 6.001 0 0 1-5.192 9.113H9.9a6.001 6.001 0 0 1-5.192-9.113A5.5 5.5 0 0 1 12 2.5zM12 16.5c1.464 0 2.755.71 3.58 1.814A4.496 4.496 0 0 0 12 17.5a4.496 4.496 0 0 0-3.58.814A4.496 4.496 0 0 1 12 16.5z"/></svg>
                                    </div>
                                    <div class="contact-info">
                                        <div class="contact-name"><?php echo htmlspecialchars($msg['contact_name']); ?></div>
                                    </div>
                                </div>
                                <div class="contact-footer">
                                    Message
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="message-text">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>
                        <span class="message-time">
                            <?php 
                            // Extract time from date string if possible
                            // Updated to support dot (.) as separator
                            if (preg_match('/(\d{1,2}[:.]\d{2}(?:[:.]\d{2})?(?: [AP]M)?)/', $msg['date'], $timeMatch)) {
                                echo $timeMatch[1];
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="pdf-controls">
        <a href="reset.php" class="btn-reset">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 12"/><path d="M3 3v9h9"/></svg>
            Reset
        </a>
        <a href="generate_pdf.php" class="btn-download">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            Download PDF
        </a>
    </div>

</body>
</html>
