@php
    // Any flash message set by a controller is shown as a confirmation modal
    // (not a bottom-right toast) on whatever page renders next.
    $vsFlashType = null;
    $vsFlashMessage = null;

    foreach (['success' => 'success', 'error' => 'error', 'warning' => 'warning', 'status' => 'info'] as $vsSessionKey => $vsType) {
        if (session()->has($vsSessionKey)) {
            $vsFlashType = $vsType;
            $vsFlashMessage = session($vsSessionKey);
            break;
        }
    }
@endphp

@once
    @if ($vsFlashMessage)
        <script>
            window.vsFlash = @json([
                'type' => $vsFlashType,
                'message' => $vsFlashMessage,
            ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        </script>
    @endif

    <div id="vsNotifyModal" class="vs-notify" hidden>
        <div class="vs-notify__backdrop" data-vs-notify-dismiss></div>
        <div class="vs-notify__dialog" role="dialog" aria-modal="true"
             aria-labelledby="vsNotifyTitle" aria-describedby="vsNotifyMessage">
            <span class="vs-notify__icon" id="vsNotifyIcon" aria-hidden="true"></span>
            <h2 class="vs-notify__title" id="vsNotifyTitle">Notice</h2>
            <p class="vs-notify__message" id="vsNotifyMessage"></p>
            <div class="vs-notify__actions">
                <button type="button" class="vs-notify__ok" id="vsNotifyOk">OK</button>
            </div>
        </div>
    </div>
@endonce
