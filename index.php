<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp to PDF Converter</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Helvetica+Neue:wght@400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>WhatsApp Chat to PDF</h1>
            <p>Convert your exported WhatsApp chat (ZIP) to a beautiful PDF.</p>
        </div>
        
        <div class="card">
            <form action="process.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="chat_zip">Upload WhatsApp Export (.zip or .txt)</label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="chat_zip" id="chat_zip" accept=".zip,.txt" required>
                        <div class="file-upload-placeholder">
                            <span>Choose file or drag & drop</span>
                            <span class="file-name">No file chosen</span>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-primary">Convert to PDF</button>
            </form>
        </div>
        
        <div class="instructions">
            <h3>How to export chat?</h3>
            <ol>
                <li>Open WhatsApp chat</li>
                <li>Tap on the contact name / three dots</li>
                <li>Select <strong>Export Chat</strong></li>
                <li>Select <strong>Attach Media</strong> (optional, but better for PDF)</li>
                <li>Save the ZIP file and upload it here</li>
            </ol>
        </div>
    </div>

    <script>
        document.getElementById('chat_zip').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
            document.querySelector('.file-name').textContent = fileName;
        });
    </script>
</body>
</html>
