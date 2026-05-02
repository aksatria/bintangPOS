<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="page-kicker">Pelanggan</p>
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-900">{{ $isEdit ? 'Edit Pelanggan' : 'Tambah Pelanggan' }}</h2>
            </div>
            <a href="{{ $isEdit ? route('customers.show', $customer) : route('customers.index') }}" class="btn-danger-lite customer-btn customer-btn--ghost">Kembali</a>
        </div>
    </x-slot>

    <div class="page-shell">
        <form method="POST" action="{{ $isEdit ? route('customers.update', $customer) : route('customers.store') }}" class="panel-card p-5 grid grid-cols-1 md:grid-cols-2 gap-4">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif
            <div>
                <label class="label-ui">Nama</label>
                <input type="text" name="name" value="{{ old('name', $customer->name) }}" class="input-ui" required>
            </div>
            <div>
                <label class="label-ui">No. HP</label>
                <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}" class="input-ui">
            </div>
            <div>
                <label class="label-ui">Email</label>
                <input type="email" name="email" value="{{ old('email', $customer->email) }}" class="input-ui">
            </div>
            <div>
                <label class="label-ui">Status</label>
                <select name="is_active" class="input-ui">
                    <option value="1" @selected((int) old('is_active', $customer->is_active ? 1 : 0) === 1)>Aktif</option>
                    <option value="0" @selected((int) old('is_active', $customer->is_active ? 1 : 0) === 0)>Nonaktif</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="label-ui">Alamat</label>
                <textarea name="address" class="input-ui" rows="3">{{ old('address', $customer->address) }}</textarea>
            </div>
            <div class="md:col-span-2">
                <label class="label-ui">Catatan Internal</label>
                <textarea name="internal_note" class="input-ui" rows="4" placeholder="Contoh: preferensi customer, catatan layanan, atau info follow-up.">{{ old('internal_note', $customer->internal_note) }}</textarea>
            </div>
            <div class="md:col-span-2 flex gap-2">
                <button class="btn-primary customer-btn customer-btn--primary">{{ $isEdit ? 'Simpan Perubahan' : 'Simpan Pelanggan' }}</button>
                <a href="{{ route('customers.index') }}" class="btn-danger-lite customer-btn customer-btn--ghost">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>
