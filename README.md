# WhatsApp to PDF Converter

A simple PHP application to convert WhatsApp chat exports (ZIP files) into a PDF document with a WhatsApp-like interface.

## Features
- **Upload ZIP**: Supports standard WhatsApp export ZIP files (with or without media).
- **Preview**: View the chat in a browser before converting.
- **PDF Generation**: Download the chat as a PDF file.
- **Media Support**: Displays images in the preview and PDF.
- **Privacy**: Uses session-based temporary storage.

## Requirements
- PHP 7.4 or higher
- Composer
- `dompdf` library (installed via composer)

## Installation
1. Clone or download this repository.
2. Run `composer install` to install dependencies.
3. Make sure the `uploads` directory is writable (the script attempts to create it).

## Usage
1. Open `index.php` in your browser.
2. Upload your WhatsApp export `.zip` file.
3. Preview the chat.
4. Click "Download PDF".

## Note
The parsing logic is based on common WhatsApp export formats. If your chat format is different (e.g., different date format), you might need to adjust the regex in `process.php`.
