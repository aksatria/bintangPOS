@props([
    'id',
    'title' => 'Approval',
    'note' => '',
    'titleId' => null,
    'noteId' => null,
    'reasonId' => 'approval-reason',
    'emailId' => 'approval-email',
    'passwordId' => 'approval-password',
    'reasonErrorId' => null,
    'emailErrorId' => null,
    'passwordErrorId' => null,
    'errorId' => 'approval-error',
    'cancelId' => 'approval-cancel',
    'submitId' => 'approval-submit',
    'submitLabel' => 'Lanjut',
    'panelRef' => null,
    'reasonRef' => null,
    'emailRef' => null,
    'passwordRef' => null,
    'primaryButtonRef' => null,
    'reasonLabel' => 'Alasan',
    'emailLabel' => 'Email Manager',
    'passwordLabel' => 'Password Manager',
])

<div id="{{ $id }}" class="report-approval-modal" aria-hidden="true" {{ $attributes }}>
    <div class="report-approval-panel" @if($panelRef) x-ref="{{ $panelRef }}" @endif>
        <h3 class="report-approval-title" @if($titleId) id="{{ $titleId }}" @endif>{{ $title }}</h3>
        @if($note !== '')
            <p class="report-approval-note" @if($noteId) id="{{ $noteId }}" @endif>{{ $note }}</p>
        @endif
        <div class="report-approval-grid">
            <div>
                <label class="label-ui">{{ $reasonLabel }}</label>
                <input id="{{ $reasonId }}" type="text" class="input-ui" placeholder="Contoh: Audit bulanan owner" @if($reasonRef) x-ref="{{ $reasonRef }}" @endif>
                @if($reasonErrorId)
                    <p id="{{ $reasonErrorId }}" class="report-inline-error" style="display:none;"></p>
                @endif
            </div>
            <div>
                <label class="label-ui">{{ $emailLabel }}</label>
                <input id="{{ $emailId }}" type="email" class="input-ui" placeholder="manager@domain.com" @if($emailRef) x-ref="{{ $emailRef }}" @endif>
                @if($emailErrorId)
                    <p id="{{ $emailErrorId }}" class="report-inline-error" style="display:none;"></p>
                @endif
            </div>
            <div>
                <label class="label-ui">{{ $passwordLabel }}</label>
                <input id="{{ $passwordId }}" type="password" class="input-ui" placeholder="Password approval" @if($passwordRef) x-ref="{{ $passwordRef }}" @endif>
                @if($passwordErrorId)
                    <p id="{{ $passwordErrorId }}" class="report-inline-error" style="display:none;"></p>
                @endif
            </div>
        </div>
        <p id="{{ $errorId }}" class="report-inline-error" style="display:none;"></p>
        <div class="report-approval-actions">
            <button type="button" id="{{ $cancelId }}" class="report-btn report-btn--density">Batal</button>
            <button type="button" id="{{ $submitId }}" class="report-btn report-btn--primary" @if($primaryButtonRef) x-ref="{{ $primaryButtonRef }}" @endif>{{ $submitLabel }}</button>
        </div>
    </div>
</div>
