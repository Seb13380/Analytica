<x-guest-layout>
    <div class="mb-5 text-sm" style="color:#5C5449;line-height:1.75;">
        Mot de passe oublié ? Renseignez votre adresse e-mail ci-dessous et nous vous enverrons un lien pour le réinitialiser.
    </div>

    <!-- Statut -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Adresse e-mail" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                Envoyer le lien de réinitialisation
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
