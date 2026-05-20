<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Tabel Nilai {{ $student->name }}</title>
    <style>
        body {
            background: #e5e7eb;
            color: #111111;
            font-family: "Times New Roman", Georgia, serif;
            margin: 0;
            padding: 24px;
        }

    </style>
    @include('grades.student-control-screen-style')
</head>
<body>
    @include('grades.student-control-table', ['logoSrc' => asset('images/logo.PNG')])
</body>
</html>
