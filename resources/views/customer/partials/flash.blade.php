@if(session('success'))
<div class="flash flash-success" id="flash-msg">
    <span class="flash-icon">✓</span>
    {{ session('success') }}
    <button class="flash-close" onclick="this.parentElement.remove()" style="background:transparent; border:none; cursor:pointer; color:inherit; font-size:16px;">×</button>
</div>
@endif

@if(session('error') || $errors->any())
<div class="flash flash-error" id="flash-msg">
    <span class="flash-icon">!</span>
    {{ session('error') ?? 'There was a problem updating your profile.' }}
    <button class="flash-close" onclick="this.parentElement.remove()" style="background:transparent; border:none; cursor:pointer; color:inherit; font-size:16px;">×</button>
</div>
@endif
