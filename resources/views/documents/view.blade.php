<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $document->file_original_name }}</title>
    <style>
        html, body {
            margin: 0;
            height: 100%;
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
    <iframe src="{{ route('documents.show', $document->id) }}"></iframe>
</body>
</html>
