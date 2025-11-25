<?php
session_start();
require 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['chat_data'])) {
    header('Location: index.php');
    exit;
}

$messages = $_SESSION['chat_data'];
$uploadDir = $_SESSION['upload_dir'];
$absoluteUploadDir = __DIR__ . '/' . $uploadDir;

// Determine senders logic (same as preview)
$senders = [];
foreach ($messages as $msg) {
    if ($msg['type'] === 'message') {
        $senders[$msg['sender']] = true;
    }
}
$senderNames = array_keys($senders);
$myUser = isset($senderNames[1]) ? $senderNames[1] : (isset($senderNames[0]) ? $senderNames[0] : 'Me');

// Handle background image for PDF (base64)
$bgPath = __DIR__ . '/wa-bg.png';
$bgBase64 = '';
if (file_exists($bgPath)) {
    $type = pathinfo($bgPath, PATHINFO_EXTENSION);
    $data = file_get_contents($bgPath);
    $bgBase64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
}

// Buffer the HTML content
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        /* PDF-optimized CSS - Dompdf has limited flexbox support, using floats and tables */
        @page {
            margin: 0;
            padding: 0;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            background-color: #e5ddd5;
            margin: 0;
            padding: 0;
            <?php if ($bgBase64): ?>
            background-image: url('<?php echo $bgBase64; ?>');
            <?php endif; ?>
        }
        
        .chat-container {
            width: 100%;
            background-color: #e5ddd5;
            <?php if ($bgBase64): ?>
            background-image: url('<?php echo $bgBase64; ?>');
            <?php endif; ?>
        }
        
        .chat-header {
            background-color: #008069;
            color: white;
            padding: 10px 15px;
        }
        
        .chat-header h2 {
            font-size: 16px;
            margin: 0 0 2px 0;
        }
        
        .chat-header p {
            font-size: 11px;
            margin: 0;
            opacity: 0.8;
        }
        
        .chat-body {
            padding: 20px;
        }
        
        .clearfix {
            clear: both;
        }
        
        /* Date divider */
        .date-divider {
            text-align: center;
            margin: 8px 0;
            clear: both;
        }
        
        .date-divider-inner {
            display: inline-block;
            background-color: rgba(225, 245, 254, 0.92);
            color: #111b21;
            font-size: 11px;
            padding: 5px 12px;
            border-radius: 7.5px;
            box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
            text-transform: uppercase;
        }
        
        /* System message */
        .system-message {
            text-align: center;
            margin: 8px 0;
            clear: both;
        }
        
        .system-message-inner {
            display: inline-block;
            background-color: rgba(225, 245, 254, 0.92);
            color: #111b21;
            font-size: 11px;
            padding: 5px 12px;
            border-radius: 7.5px;
            box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
        }
        
        /* Messages */
        .message {
            max-width: 65%;
            margin-bottom: 8px;
            padding: 6px 7px 8px 9px;
            border-radius: 7.5px;
            font-size: 13px;
            line-height: 19px;
            box-shadow: 0 1px 0.5px rgba(0,0,0,0.13);
            word-wrap: break-word;
            page-break-inside: avoid;
        }
        
        .message.received {
            background-color: #FFFFFF;
            float: left;
            clear: both;
            border-top-left-radius: 0;
        }
        
        .message.sent {
            background-color: #DCF8C6;
            float: right;
            clear: both;
            border-top-right-radius: 0;
        }
        
        .message-sender {
            font-size: 12px;
            font-weight: 500;
            color: #e542a3;
            margin-bottom: 4px;
            display: block;
        }
        
        .message-text {
            color: #111b21;
        }
        
        .message-time {
            float: right;
            font-size: 10px;
            color: rgba(17, 27, 33, 0.5);
            margin-top: 4px;
            margin-left: 10px;
        }
        
        /* Media attachment */
        .media-attachment {
            margin-bottom: 5px;
        }
        
        .media-attachment img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 6px;
            margin-bottom: 4px;
        }
        
        /* Location */
        .location-attachment {
            margin-bottom: 5px;
        }
        
        .location-map {
            width: 100%;
            max-height: 150px;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .location-link {
            display: block;
            margin-top: 5px;
            color: #007bff;
            font-size: 11px;
            text-decoration: none;
        }
        
        /* Contact card */
        .contact-card {
            background-color: rgba(0,0,0,0.05);
            border-radius: 6px;
            overflow: hidden;
            margin: 5px 0;
        }
        
        .contact-header {
            padding: 8px;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
        
        .contact-avatar {
            width: 30px;
            height: 30px;
            background-color: #ccc;
            border-radius: 50%;
            float: left;
            margin-right: 8px;
            text-align: center;
            line-height: 30px;
            color: white;
            font-size: 18px;
        }
        
        .contact-name {
            font-size: 13px;
            color: #007bff;
            font-weight: 500;
            padding-top: 6px;
        }
        
        .contact-footer {
            padding: 6px;
            text-align: center;
            color: #007bff;
            font-size: 12px;
            font-weight: 500;
            background-color: rgba(255,255,255,0.3);
            clear: both;
        }
    </style>
</head>
<body>

    <div class="chat-container">
        <div class="chat-header">
            <h2>WhatsApp Chat Export</h2>
            <p>Tap here for contact info</p>
        </div>

        <div class="chat-body">
            <?php 
            $lastDate = '';
            foreach ($messages as $msg): 
                // Date Divider
                $cleanDate = str_replace(['[', ']'], '', isset($msg['date']) ? $msg['date'] : '');
                $dateParts = preg_split('/[, ]+/', $cleanDate);
                $msgDate = isset($dateParts[0]) ? $dateParts[0] : '';

                if ($msgDate && $msgDate !== $lastDate):
                    echo '<div class="clearfix"></div>';
                    echo '<div class="date-divider"><span class="date-divider-inner">' . htmlspecialchars($msgDate) . '</span></div>';
                    $lastDate = $msgDate;
                endif;

                if ($msg['type'] === 'system'): ?>
                    <div class="clearfix"></div>
                    <div class="system-message">
                        <span class="system-message-inner"><?php echo htmlspecialchars($msg['message']); ?></span>
                    </div>
                <?php else: 
                    $isSent = ($msg['sender'] === $myUser);
                    $class = $isSent ? 'sent' : 'received';
                ?>
                    <div class="message <?php echo $class; ?>">
                        <?php if (!$isSent): ?>
                            <span class="message-sender"><?php echo htmlspecialchars($msg['sender']); ?></span>
                        <?php endif; ?>
                        
                        <?php if (isset($msg['is_media']) && $msg['is_media'] && isset($msg['media_file']) && !isset($msg['is_contact'])): ?>
                            <div class="media-attachment">
                                <?php 
                                $ext = strtolower(pathinfo($msg['media_file'], PATHINFO_EXTENSION));
                                $localPath = $absoluteUploadDir . $msg['media_file'];
                                
                                if (file_exists($localPath) && in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                    <img src="<?php echo $localPath; ?>" alt="Image">
                                <?php elseif (in_array($ext, ['mp4', '3gp', 'mov'])): ?>
                                    <div style="background: #eee; padding: 8px; border-radius: 5px; text-align: center; font-size: 11px;">
                                        🎥 Video: <?php echo htmlspecialchars($msg['media_file']); ?>
                                    </div>
                                <?php elseif (in_array($ext, ['opus', 'mp3', 'aac'])): ?>
                                    <div style="background: #eee; padding: 8px; border-radius: 5px; text-align: center; font-size: 11px;">
                                        🎤 Audio: <?php echo htmlspecialchars($msg['media_file']); ?>
                                    </div>
                                <?php else: ?>
                                    <div style="background: #eee; padding: 8px; border-radius: 5px; text-align: center; font-size: 11px;">
                                        📄 File: <?php echo htmlspecialchars($msg['media_file']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($msg['is_location']) && $msg['is_location']): ?>
                            <div class="location-attachment">
                                <?php if (isset($msg['location_lat']) && isset($msg['location_lng'])): 
                                    $lat = $msg['location_lat'];
                                    $lng = $msg['location_lng'];
                                    $mapUrl = "https://static-maps.yandex.ru/1.x/?lang=en_US&ll={$lng},{$lat}&z=15&l=map&size=400,200&pt={$lng},{$lat},pm2rdm";
                                ?>
                                    <img src="<?php echo $mapUrl; ?>" alt="Map" class="location-map">
                                <?php else: ?>
                                    <div style="background: #e9e9eb; height: 100px; text-align: center; line-height: 100px; color: #777; border-radius: 6px; font-size: 11px;">
                                        Map not available
                                    </div>
                                <?php endif; ?>
                                <a href="<?php echo htmlspecialchars(strip_tags($msg['message'])); ?>" class="location-link">View on Google Maps</a>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($msg['is_contact']) && $msg['is_contact']): ?>
                            <div class="contact-card">
                                <div class="contact-header">
                                    <div class="contact-avatar">👤</div>
                                    <div class="contact-name"><?php echo htmlspecialchars($msg['contact_name']); ?></div>
                                    <div class="clearfix"></div>
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
                            if (preg_match('/(\d{1,2}[:.]\d{2}(?:[:.]\d{2})?(?: [AP]M)?)/', $msg['date'], $timeMatch)) {
                                echo $timeMatch[1];
                            }
                            ?>
                        </span>
                        <div class="clearfix"></div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="clearfix"></div>
        </div>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("whatsapp-chat.pdf", ["Attachment" => true]);
