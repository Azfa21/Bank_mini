<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Bank Mini</title>
</head>
<body>
    <h1>Login Bank Mini Sekolah</h1>

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ route('login.submit') }}">
        @csrf
        <div>
            <label for="identifier">Username / No. Siswa</label>
            <input type="text" id="identifier" name="identifier" value="{{ old('identifier') }}" required>
        </div>
        <div>
            <label for="secret">Password / PIN</label>
            <input type="password" id="secret" name="secret" required>
        </div>
        <button type="submit">Masuk</button>
    </form>
</body>
</html>
