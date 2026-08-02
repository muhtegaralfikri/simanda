@if (session('success'))
    <div class="alert alert-success">
        <div><strong>Sukses:</strong> {{ session('success') }}</div>
        <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:inherit;font-weight:bold;">&times;</button>
    </div>
@endif

@if (session('error'))
    <div class="alert alert-danger">
        <div><strong>Gagal:</strong> {{ session('error') }}</div>
        <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:inherit;font-weight:bold;">&times;</button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <div>
            <strong>Terjadi kesalahan input:</strong>
            <ul style="margin-left: 18px; margin-top: 4px;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;cursor:pointer;color:inherit;font-weight:bold;">&times;</button>
    </div>
@endif
