<section>
    <header>
        <h2 class="text-lg font-medium" style="color:#1C1916;font-family:'Cormorant Garamond',serif;font-size:1.3rem;font-weight:400;">
            Informations du profil
        </h2>
        <p class="mt-1 text-sm" style="color:#5C5449;">Modifiez le nom et l'adresse e-mail associés à votre compte.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">@csrf</form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nom" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2" style="color:#1C1916;">
                        Votre adresse e-mail n'est pas vérifiée.
                        <button form="send-verification" class="underline text-sm focus:outline-none" style="color:#5C5449;">
                            Cliquez ici pour renvoyer l'e-mail de vérification.
                        </button>
                    </p>
                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm" style="color:#9B7A2A;">Un nouveau lien a été envoyé.</p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Enregistrer</x-primary-button>
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm" style="color:#9B7A2A;">Enregistré.</p>
            @endif
        </div>
    </form>
</section>
