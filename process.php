<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['chat_zip'])) {
    header('Location: index.php');
    exit;
}

$file = $_FILES['chat_zip'];
$uploadDir = 'uploads/' . session_id() . '/';

// Clean up old session dir if exists
if (is_dir($uploadDir)) {
    // Simple recursive delete function
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }
    rmdir($uploadDir);
}

mkdir($uploadDir, 0777, true);

$fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$targetPath = $uploadDir . $file['name'];

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    
    $chatFile = '';

    if ($fileType === 'zip') {
        $zip = new ZipArchive;
        if ($zip->open($targetPath) === TRUE) {
            $zip->extractTo($uploadDir);
            $zip->close();
            
            // Find _chat.txt
            $chatFile = $uploadDir . '_chat.txt';
            if (!file_exists($chatFile)) {
                // Try to find any .txt file if _chat.txt doesn't exist
                $txtFiles = glob($uploadDir . '*.txt');
                if (count($txtFiles) > 0) {
                    $chatFile = $txtFiles[0];
                } else {
                    die("Error: _chat.txt not found in the ZIP file.");
                }
            }
        } else {
            die("Error: Failed to open ZIP file.");
        }
    } elseif ($fileType === 'txt') {
        $chatFile = $targetPath;
    } else {
        die("Error: Unsupported file type. Please upload a .zip or .txt file.");
    }

    if (file_exists($chatFile)) {
        // Parse the chat file
        $content = file_get_contents($chatFile);
        $lines = explode("\n", $content);
        $messages = [];
        
        // Regex to extract date and the rest of the line
        // Updated to support dot (.) as time separator (e.g. 13.52)
        $mainRegex = '/^\[?(\d{1,2}[\/.-]\d{1,2}[\/.-]\d{2,4}[,.]? \d{1,2}[:.]\d{2}(?:[:.]\d{2})?(?: [AP]M)?)\]? (?:- )?(.*)/';

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Extract timestamp and rest of the line
            if (preg_match($mainRegex, $line, $matches)) {
                $date = $matches[1];
                $content = $matches[2];
                
                // Check for specific system phrases that might be mistaken for messages or just to be sure
                if (strpos($content, 'mematikan pesan sementara') !== false || 
                    strpos($content, 'Kode keamanan') !== false ||
                    strpos($content, 'telah berubah') !== false ||
                    strpos($content, 'pesan sementara') !== false ||
                    strpos($content, 'Messages to this chat') !== false ||
                    strpos($content, 'security code') !== false ||
                    strpos($content, 'added') !== false ||
                    strpos($content, 'removed') !== false ||
                    strpos($content, 'left') !== false ||
                    strpos($content, 'created group') !== false) {
                    
                    $messages[] = [
                        'type' => 'system',
                        'date' => $date,
                        'message' => $content
                    ];
                }
                // Check for standard message format: "Sender: Message"
                elseif (preg_match('/^([^:]+): (.*)/', $content, $msgMatches)) {
                    $messages[] = [
                        'type' => 'message',
                        'date' => $date,
                        'sender' => trim($msgMatches[1]),
                        'message' => trim($msgMatches[2]),
                        'is_media' => false
                    ];
                }
                // Fallback to system message
                else {
                    $messages[] = [
                        'type' => 'system',
                        'date' => $date,
                        'message' => $content
                    ];
                }
            }
            else {
                // Multiline message: append to previous message
                if (!empty($messages)) {
                    $lastIndex = count($messages) - 1;
                    $messages[$lastIndex]['message'] .= "\n" . $line;
                }
            }
        }

        // Post-process to identify media
        foreach ($messages as &$msg) {
            if ($msg['type'] === 'message') {
                // Common media attachments text
                if (strpos($msg['message'], ' (file attached)') !== false || 
                    strpos($msg['message'], '<attached:') !== false ||
                    strpos(strtolower($msg['message']), '.vcf') !== false ||
                    preg_match('/IMG-\d{8}-WA\d{4}\.jpg/', $msg['message']) ||
                    preg_match('/VID-\d{8}-WA\d{4}\.mp4/', $msg['message']) ||
                    preg_match('/PTT-\d{8}-WA\d{4}\.opus/', $msg['message'])
                   ) {
                    
                    $msg['is_media'] = true;
                    // Try to extract filename
                    // Example: "IMG-20210101-WA0001.jpg (file attached)"
                    // or just "IMG-20210101-WA0001.jpg"
                    
                    // Clean up " (file attached)"
                    $cleanMsg = str_replace(' (file attached)', '', $msg['message']);
                    $cleanMsg = trim($cleanMsg);
                    
                    if (file_exists($uploadDir . $cleanMsg)) {
                        $msg['media_file'] = $cleanMsg;
                    }
                    
                    // Check for Contact (VCF)
                    if (strpos(strtolower($cleanMsg), '.vcf') !== false) {
                        $msg['is_contact'] = true;
                        $msg['contact_name'] = str_replace(['.vcf', '.VCF'], '', $cleanMsg);
                    }
                }
                // Location detection
                elseif (strpos($msg['message'], 'maps.google.com') !== false || strpos($msg['message'], 'Location:') !== false) {
                    $msg['is_location'] = true;
                    // Try to extract coordinates if present in link
                    if (preg_match('/q=(-?\d+\.\d+),(-?\d+\.\d+)/', $msg['message'], $coords)) {
                        $msg['location_lat'] = $coords[1];
                        $msg['location_lng'] = $coords[2];
                    }
                }
            }
        }

        $_SESSION['chat_data'] = $messages;
        $_SESSION['upload_dir'] = $uploadDir;
        
        header('Location: preview.php');
        exit;

    } else {
        die("Error: Failed to process chat file.");
    }
} else {
    die("Error: Failed to move uploaded file.");
}
